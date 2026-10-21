<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningMaterial;
use App\Models\LearningObjective;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningObjectiveAlignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $otherInstructor;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();

        $this->instructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);
        $this->otherInstructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);
        $this->student = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
        ]);
    }

    public function test_instructor_dashboard_surfaces_learning_objective_workspace(): void
    {
        $this->actingAs($this->instructor)
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Learning Objectives')
            ->assertSee(route('instructor.learning-objectives.index'), false);
    }

    public function test_instructor_can_open_learning_objective_workspace_for_own_course(): void
    {
        $course = $this->courseFor($this->instructor, 'Pemrograman Dasar');
        $otherCourse = $this->courseFor($this->otherInstructor, 'Kursus Milik Guru Lain');

        $this->actingAs($this->instructor)
            ->get(route('instructor.learning-objectives.index', ['course_id' => $course->id]))
            ->assertOk()
            ->assertSee('Learning Objectives &amp; Alignment', false)
            ->assertSee($course->title)
            ->assertDontSee($otherCourse->title);
    }

    public function test_instructor_can_create_and_update_objective_with_course_scoped_code(): void
    {
        $course = $this->courseFor($this->instructor);

        $this->actingAs($this->instructor)
            ->post(route('instructor.learning-objectives.store', $course), [
                'code' => 'lo-01',
                'title' => 'Menjelaskan variabel dan tipe data',
                'description' => 'Konsep variabel dan tipe data dasar.',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $objective = LearningObjective::firstOrFail();
        $this->assertSame('LO-01', $objective->code);
        $this->assertSame($course->id, $objective->course_id);

        $this->actingAs($this->instructor)
            ->put(route('instructor.learning-objectives.update', $objective), [
                'code' => 'LO-01',
                'title' => 'Menjelaskan variabel, konstanta, dan tipe data',
                'description' => 'Revisi scope objective.',
                'order' => 2,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_objectives', [
            'id' => $objective->id,
            'title' => 'Menjelaskan variabel, konstanta, dan tipe data',
            'order' => 2,
        ]);
    }

    public function test_objective_code_must_be_unique_inside_course_but_can_repeat_across_courses(): void
    {
        $courseA = $this->courseFor($this->instructor, 'Course A');
        $courseB = $this->courseFor($this->instructor, 'Course B');

        LearningObjective::create([
            'course_id' => $courseA->id,
            'code' => 'LO-01',
            'title' => 'Objective A',
            'order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs($this->instructor)
            ->post(route('instructor.learning-objectives.store', $courseA), [
                'code' => 'LO-01',
                'title' => 'Duplikat',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('code');

        $this->actingAs($this->instructor)
            ->post(route('instructor.learning-objectives.store', $courseB), [
                'code' => 'LO-01',
                'title' => 'Objective B',
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, LearningObjective::where('code', 'LO-01')->count());
    }

    public function test_instructor_cannot_modify_objective_owned_by_other_instructor(): void
    {
        $course = $this->courseFor($this->otherInstructor);
        $objective = $this->objectiveFor($course);

        $this->actingAs($this->instructor)
            ->put(route('instructor.learning-objectives.update', $objective), [
                'code' => 'LO-99',
                'title' => 'Unauthorized update',
                'order' => 1,
                'is_active' => 1,
            ])
            ->assertForbidden();
    }

    public function test_material_alignment_accepts_only_objectives_from_same_course(): void
    {
        $course = $this->courseFor($this->instructor, 'Course A');
        $otherCourse = $this->courseFor($this->instructor, 'Course B');
        $material = $this->materialFor($course);
        $objective = $this->objectiveFor($course, 'LO-01');
        $foreignObjective = $this->objectiveFor($otherCourse, 'LO-99');

        $this->actingAs($this->instructor)
            ->put(route('instructor.materials.learning-objectives.sync', $material), [
                'learning_objective_ids' => [$objective->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_material_learning_objective', [
            'learning_material_id' => $material->id,
            'learning_objective_id' => $objective->id,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.materials.learning-objectives.sync', $material), [
                'learning_objective_ids' => [$foreignObjective->id],
            ])
            ->assertSessionHasErrors('learning_objective_ids.0');

        $this->assertDatabaseMissing('learning_material_learning_objective', [
            'learning_material_id' => $material->id,
            'learning_objective_id' => $foreignObjective->id,
        ]);
    }

    public function test_question_alignment_accepts_only_objectives_from_question_bank_course(): void
    {
        $course = $this->courseFor($this->instructor, 'Course A');
        $otherCourse = $this->courseFor($this->instructor, 'Course B');
        $question = $this->questionFor($course);
        $objective = $this->objectiveFor($course, 'LO-01');
        $foreignObjective = $this->objectiveFor($otherCourse, 'LO-99');

        $this->actingAs($this->instructor)
            ->put(route('instructor.questions.learning-objectives.sync', $question), [
                'learning_objective_ids' => [$objective->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('learning_objective_question', [
            'learning_objective_id' => $objective->id,
            'question_id' => $question->id,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.questions.learning-objectives.sync', $question), [
                'learning_objective_ids' => [$foreignObjective->id],
            ])
            ->assertSessionHasErrors('learning_objective_ids.0');

        $this->assertDatabaseMissing('learning_objective_question', [
            'learning_objective_id' => $foreignObjective->id,
            'question_id' => $question->id,
        ]);
    }

    public function test_global_question_bank_cannot_be_aligned_without_course_context(): void
    {
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
            'course_id' => null,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);
        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'difficulty' => Question::DIFFICULTY_EASY,
            'points' => 1,
            'order' => 1,
            'status' => Question::STATUS_APPROVED,
            'answer_source' => Question::SOURCE_MANUAL,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.questions.learning-objectives.sync', $question), [])
            ->assertSessionHasErrors('learning_objective_ids');
    }

    public function test_deleting_objective_cascades_material_and_question_alignments(): void
    {
        $course = $this->courseFor($this->instructor);
        $material = $this->materialFor($course);
        $question = $this->questionFor($course);
        $objective = $this->objectiveFor($course);

        $material->learningObjectives()->sync([$objective->id]);
        $question->learningObjectives()->sync([$objective->id]);

        $this->actingAs($this->instructor)
            ->delete(route('instructor.learning-objectives.destroy', $objective))
            ->assertRedirect();

        $this->assertDatabaseMissing('learning_objectives', ['id' => $objective->id]);
        $this->assertDatabaseMissing('learning_material_learning_objective', [
            'learning_objective_id' => $objective->id,
        ]);
        $this->assertDatabaseMissing('learning_objective_question', [
            'learning_objective_id' => $objective->id,
        ]);
    }

    public function test_student_cannot_access_learning_objective_workspace(): void
    {
        $this->actingAs($this->student)
            ->get(route('instructor.learning-objectives.index'))
            ->assertRedirect();
    }

    private function courseFor(User $instructor, string $title = 'Pemrograman Dasar'): Course
    {
        return Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => $title,
            'status' => Course::STATUS_DRAFT,
        ]);
    }

    private function materialFor(Course $course): LearningMaterial
    {
        $section = CourseSection::factory()->create(['course_id' => $course->id]);

        return LearningMaterial::factory()->create(['section_id' => $section->id]);
    }

    private function questionFor(Course $course): Question
    {
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $course->instructor_id,
            'course_id' => $course->id,
            'status' => QuestionBank::STATUS_ACTIVE,
        ]);

        return Question::factory()->create([
            'question_bank_id' => $bank->id,
            'difficulty' => Question::DIFFICULTY_MEDIUM,
            'points' => 1,
            'order' => 1,
            'status' => Question::STATUS_APPROVED,
            'answer_source' => Question::SOURCE_MANUAL,
        ]);
    }

    private function objectiveFor(Course $course, string $code = 'LO-01'): LearningObjective
    {
        return LearningObjective::create([
            'course_id' => $course->id,
            'code' => $code,
            'title' => 'Objective '.$code,
            'order' => 1,
            'is_active' => true,
        ]);
    }
}
