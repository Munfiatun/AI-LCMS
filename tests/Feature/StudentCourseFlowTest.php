<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\MaterialProgress;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentCourseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_loads_categories_for_multiple_courses_without_n_plus_one(): void
    {
        [$student, $course] = $this->courseFlow();
        $secondCourse = Course::factory()->create(['instructor_id' => $course->instructor_id, 'category_id' => $course->category_id, 'title' => 'Kursus Kedua']);
        $secondCourse->enrollments()->create(['student_id' => $student->id, 'status' => 'active']);
        $thirdCourse = Course::factory()->create(['instructor_id' => $course->instructor_id, 'category_id' => null, 'title' => 'Tanpa Kategori']);
        $thirdCourse->enrollments()->create(['student_id' => $student->id, 'status' => 'active']);
        $this->assertTrue(Model::preventsLazyLoading());
        DB::enableQueryLog();

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()->assertSee('Kategori Sains')->assertSee('Kursus Kedua')->assertSee('Uncategorized')
            ->assertSee(route('courses.show', $course->slug))->assertSee('0% Selesai');
        $queries = collect(DB::getQueryLog());
        $this->assertCount(1, $queries->filter(fn (array $query): bool => str_contains($query['query'], 'from "categories"')));
        $this->assertTrue(Model::preventsLazyLoading());
    }

    public function test_student_navigates_course_materials_and_persists_progress_after_refresh(): void
    {
        [$student, $course, $materials] = $this->courseFlow();
        $slidebook = Slidebook::create(['material_id' => $materials[0]->id, 'title' => 'Slidebook Sains', 'status' => 'published', 'created_by' => $course->instructor_id]);
        $slidebook->slides()->create(['title' => 'Slide 1', 'content' => 'Isi pembelajaran.', 'order' => 1]);
        $this->actingAs($student)->get(route('courses.show', $course->slug))->assertOk()
            ->assertSee('Silabus Kursus')->assertSee('Bab 2')
            ->assertSee(route('student.materials.show', [$course, $materials[0]]))
            ->assertSee('Progress: 0% Selesai');
        $this->get(route('student.courses.continue', $course))->assertRedirect(route('student.materials.show', [$course, $materials[0]]));
        foreach ($materials as $material) {
            $this->get(route('student.materials.show', [$course, $material]))->assertOk()->assertSee($material->title)
                ->assertSee(route('student.materials.show', [$course, $materials[1]]))
                ->assertSee(route('student.materials.show', [$course, $materials[2]]))
                ->assertSee(route('student.courses.progress', $course))->assertSee('Tandai Selesai');
        }
        $this->get(route('student.slidebooks.show', $slidebook))->assertOk();
        $url = route('student.materials.show', [$course, $materials[1]]);
        $this->from($url)->post(route('student.materials.complete', $materials[1]))->assertRedirect($url);
        $this->assertDatabaseHas('material_progress', ['learning_material_id' => $materials[1]->id, 'student_id' => $student->id, 'status' => 'completed']);
        $this->assertSame('33.33', $course->enrollments()->where('student_id', $student->id)->sole()->progress_percentage);
        $this->get($url)->assertOk()->assertSee('Materi Telah Selesai')->assertDontSee('Tandai Selesai');
        $this->get($url)->assertOk()->assertSee('Materi Telah Selesai');
        $this->post(route('student.materials.complete', $materials[1]))->assertRedirect();
        $this->assertSame(1, MaterialProgress::where('learning_material_id', $materials[1]->id)->where('student_id', $student->id)->count());
        $this->get(route('courses.show', $course->slug))->assertSee('Progress: 33% Selesai');
        $this->get(route('student.dashboard'))->assertOk()->assertSee('33% Selesai');
        $this->assertTrue(Model::preventsLazyLoading());
    }

    public function test_material_navigation_loads_progress_once_and_hides_draft_materials(): void
    {
        [$student, $course, $materials] = $this->courseFlow();
        LearningMaterial::factory()->create(['section_id' => $materials[0]->section_id, 'status' => 'draft', 'title' => 'Materi Draft Rahasia']);
        MaterialProgress::create(['student_id' => $student->id, 'learning_material_id' => $materials[1]->id, 'status' => 'completed']);
        DB::enableQueryLog();

        $response = $this->actingAs($student)->get(route('student.materials.show', [$course, $materials[0]]));

        $response->assertOk()->assertDontSee('Materi Draft Rahasia')->assertViewHas('completedMaterialIds', [$materials[1]->id]);
        $queries = collect(DB::getQueryLog());
        $this->assertCount(1, $queries->filter(fn (array $query): bool => str_contains($query['query'], 'from "material_progress"')));
        $this->assertCount(1, $queries->filter(fn (array $query): bool => str_contains($query['query'], 'from "learning_materials"') && str_contains($query['query'], ' in (')));
    }

    public function test_material_access_and_completion_require_enrollment_and_correct_course(): void
    {
        [$student, $course, $materials] = $this->courseFlow();
        $otherCourse = Course::factory()->create(['instructor_id' => $course->instructor_id]);
        $otherSection = CourseSection::factory()->create(['course_id' => $otherCourse->id]);
        $otherMaterial = LearningMaterial::factory()->create(['section_id' => $otherSection->id]);
        $draft = LearningMaterial::factory()->create(['section_id' => $materials[0]->section_id, 'status' => 'draft']);
        $this->actingAs($student)->get(route('student.materials.show', [$course, $otherMaterial]))->assertNotFound();
        $this->get(route('student.materials.show', [$otherCourse, $otherMaterial]))->assertNotFound();
        $this->post(route('student.materials.complete', $otherMaterial))->assertNotFound();
        $this->get(route('student.materials.show', [$course, $draft]))->assertForbidden();
        $this->post(route('student.materials.complete', $draft))->assertForbidden();
        $this->assertDatabaseCount('material_progress', 0);
    }

    public function test_continue_skips_empty_chapters_and_draft_materials(): void
    {
        [$student, $course, $materials] = $this->courseFlow();
        $emptySection = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 0]);
        LearningMaterial::factory()->create(['section_id' => $emptySection->id, 'status' => 'draft']);
        $this->actingAs($student)->get(route('student.courses.continue', $course))->assertRedirect(route('student.materials.show', [$course, $materials[0]]));
    }

    /** @return array{User, Course, list<LearningMaterial>} */
    private function courseFlow(): array
    {
        $teacherRole = Role::create(['name' => 'instructor', 'label' => 'Instructor']);
        $studentRole = Role::create(['name' => 'student', 'label' => 'Student']);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id, 'is_active' => true]);
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $category = Category::create(['name' => 'Kategori Sains', 'slug' => 'sains']);
        $course = Course::factory()->create(['instructor_id' => $teacher->id, 'category_id' => $category->id, 'title' => 'Kursus Sains', 'status' => 'published']);
        $section = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 1, 'title' => 'Bab 1']);
        $secondSection = CourseSection::factory()->create(['course_id' => $course->id, 'order' => 2, 'title' => 'Bab 2']);
        $materials = [
            LearningMaterial::factory()->create(['section_id' => $section->id, 'order' => 1, 'title' => 'Materi 1']),
            LearningMaterial::factory()->create(['section_id' => $section->id, 'order' => 2, 'title' => 'Materi 2']),
            LearningMaterial::factory()->create(['section_id' => $secondSection->id, 'order' => 1, 'title' => 'Materi 3']),
        ];
        $course->enrollments()->create(['student_id' => $student->id, 'status' => 'active', 'progress_percentage' => 0]);

        return [$student, $course, $materials];
    }
}
