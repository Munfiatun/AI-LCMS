<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\LearningConcept;
use App\Models\LearningMaterial;
use App\Models\LearningObjective;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningConceptWorkspaceTest extends TestCase
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

    public function test_instructor_can_open_domain_model_workspace_for_own_course_only(): void
    {
        $course = $this->courseFor($this->instructor, 'Pemrograman Dasar');
        $otherCourse = $this->courseFor($this->otherInstructor, 'Kursus Guru Lain');

        $this->actingAs($this->instructor)
            ->get(route('instructor.learning-concepts.index', ['course_id' => $course->id]))
            ->assertOk()
            ->assertSee('Konsep &amp; Mapping', false)
            ->assertSee($course->title)
            ->assertDontSee($otherCourse->title);
    }

    public function test_instructor_can_create_concept_with_learning_objective_from_same_course(): void
    {
        $course = $this->courseFor($this->instructor);
        $objective = $this->objectiveFor($course);

        $this->actingAs($this->instructor)
            ->post(route('instructor.learning-concepts.store', $course), [
                'learning_objective_id' => $objective->id,
                'code' => 'c03',
                'name' => 'Percabangan',
                'description' => 'Pengambilan keputusan menggunakan kondisi.',
                'is_active' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('learning_concepts', [
            'course_id' => $course->id,
            'learning_objective_id' => $objective->id,
            'code' => 'C03',
            'name' => 'Percabangan',
        ]);
    }

    public function test_concept_rejects_learning_objective_from_another_course(): void
    {
        $course = $this->courseFor($this->instructor, 'Course A');
        $otherCourse = $this->courseFor($this->instructor, 'Course B');
        $foreignObjective = $this->objectiveFor($otherCourse, 'LO-99');

        $this->actingAs($this->instructor)
            ->post(route('instructor.learning-concepts.store', $course), [
                'learning_objective_id' => $foreignObjective->id,
                'code' => 'C01',
                'name' => 'Concept Invalid',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('learning_objective_id');

        $this->assertDatabaseMissing('learning_concepts', [
            'course_id' => $course->id,
            'code' => 'C01',
        ]);
    }

    public function test_prerequisite_mapping_rejects_self_reference_and_cycles(): void
    {
        $course = $this->courseFor($this->instructor);
        $objective = $this->objectiveFor($course);
        $variables = $this->conceptFor($course, $objective, 'C01', 'Variabel');
        $branching = $this->conceptFor($course, $objective, 'C03', 'Percabangan');

        $this->actingAs($this->instructor)
            ->put(route('instructor.learning-concepts.prerequisites.sync', $branching), [
                'learning_concept_ids' => [$branching->id],
            ])
            ->assertSessionHasErrors('learning_concept_ids');

        $this->actingAs($this->instructor)
            ->put(route('instructor.learning-concepts.prerequisites.sync', $branching), [
                'learning_concept_ids' => [$variables->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('concept_prerequisites', [
            'concept_id' => $branching->id,
            'prerequisite_concept_id' => $variables->id,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.learning-concepts.prerequisites.sync', $variables), [
                'learning_concept_ids' => [$branching->id],
            ])
            ->assertSessionHasErrors('learning_concept_ids');

        $this->assertDatabaseMissing('concept_prerequisites', [
            'concept_id' => $variables->id,
            'prerequisite_concept_id' => $branching->id,
        ]);
    }

    public function test_material_mapping_accepts_only_concepts_from_same_course(): void
    {
        $course = $this->courseFor($this->instructor, 'Course A');
        $otherCourse = $this->courseFor($this->instructor, 'Course B');
        $concept = $this->conceptFor($course, null, 'C01', 'Variabel');
        $foreignConcept = $this->conceptFor($otherCourse, null, 'C99', 'Foreign Concept');
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.materials.learning-concepts.sync', $material), [
                'learning_concept_ids' => [$concept->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('material_concepts', [
            'learning_material_id' => $material->id,
            'learning_concept_id' => $concept->id,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.materials.learning-concepts.sync', $material), [
                'learning_concept_ids' => [$foreignConcept->id],
            ])
            ->assertSessionHasErrors('learning_concept_ids.0');
    }

    public function test_question_mapping_stores_question_difficulty_and_rejects_foreign_concept(): void
    {
        $course = $this->courseFor($this->instructor, 'Course A');
        $otherCourse = $this->courseFor($this->instructor, 'Course B');
        $concept = $this->conceptFor($course, null, 'C04', 'Perulangan');
        $foreignConcept = $this->conceptFor($otherCourse, null, 'C99', 'Foreign Concept');
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
            'course_id' => $course->id,
        ]);
        $question = Question::factory()->create([
            'question_bank_id' => $bank->id,
            'difficulty' => Question::DIFFICULTY_MEDIUM,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.questions.learning-concepts.sync', $question), [
                'learning_concept_ids' => [$concept->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('question_concepts', [
            'question_id' => $question->id,
            'learning_concept_id' => $concept->id,
            'difficulty_level' => Question::DIFFICULTY_MEDIUM,
        ]);

        $this->actingAs($this->instructor)
            ->put(route('instructor.questions.learning-concepts.sync', $question), [
                'learning_concept_ids' => [$foreignConcept->id],
            ])
            ->assertSessionHasErrors('learning_concept_ids.0');
    }

    public function test_student_is_redirected_to_own_dashboard_when_opening_domain_model_workspace(): void
    {
        $this->actingAs($this->student)
            ->get(route('instructor.learning-concepts.index'))
            ->assertRedirect(route('student.dashboard'));
    }

    private function courseFor(User $instructor, string $title = 'Pemrograman Dasar'): Course
    {
        return Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => $title,
        ]);
    }

    private function objectiveFor(Course $course, string $code = 'LO-01'): LearningObjective
    {
        return LearningObjective::create([
            'course_id' => $course->id,
            'code' => $code,
            'title' => 'Menerapkan struktur kontrol pemrograman',
            'order' => 1,
            'is_active' => true,
        ]);
    }

    private function conceptFor(
        Course $course,
        ?LearningObjective $objective,
        string $code,
        string $name,
    ): LearningConcept {
        return LearningConcept::factory()->create([
            'course_id' => $course->id,
            'learning_objective_id' => $objective?->id,
            'code' => $code,
            'name' => $name,
        ]);
    }
}
