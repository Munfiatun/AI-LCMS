<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\Role;
use App\Models\Slidebook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSlidebookAccessRegressionTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    private Slidebook $slidebook;

    private Course $course;

    private LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();

        $instructorRole = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $studentRole = Role::firstOrCreate(['name' => Role::ROLE_STUDENT], ['label' => 'Student']);

        $instructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $this->student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);

        $this->course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $section = CourseSection::factory()->create([
            'course_id' => $this->course->id,
            'status' => 'active',
        ]);
        $this->material = LearningMaterial::factory()->create([
            'section_id' => $section->id,
            'status' => LearningMaterial::STATUS_PUBLISHED,
        ]);
        $this->slidebook = Slidebook::create([
            'material_id' => $this->material->id,
            'title' => 'Slidebook untuk dipelajari kembali',
            'status' => Slidebook::STATUS_PUBLISHED,
            'version' => 1,
            'created_by' => $instructor->id,
            'published_at' => now(),
        ]);
        $this->slidebook->slides()->create([
            'title' => 'Ulangi konsep utama',
            'content' => 'Materi pembelajaran yang sudah diselesaikan.',
            'order' => 1,
            'status' => 'active',
        ]);
    }

    public function test_completed_student_can_revisit_published_material_and_its_slidebook(): void
    {
        CourseEnrollment::create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->get(route('student.materials.show', [$this->course, $this->material]))
            ->assertOk();

        $this->get(route('student.slidebooks.show', $this->slidebook))
            ->assertOk()
            ->assertSee('Slidebook untuk dipelajari kembali')
            ->assertSee('Ulangi konsep utama');
    }

    public function test_student_without_enrollment_cannot_open_published_slidebook(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.slidebooks.show', $this->slidebook))
            ->assertForbidden();
    }

    public function test_completed_student_cannot_open_unpublished_slidebook(): void
    {
        CourseEnrollment::create([
            'course_id' => $this->course->id,
            'student_id' => $this->student->id,
            'status' => 'completed',
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
        $this->slidebook->update(['status' => Slidebook::STATUS_REVIEW]);

        $this->actingAs($this->student)
            ->get(route('student.slidebooks.show', $this->slidebook))
            ->assertForbidden();
    }
}
