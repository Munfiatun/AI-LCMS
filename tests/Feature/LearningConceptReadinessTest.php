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

class LearningConceptReadinessTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $this->instructor = User::factory()->create([
            'role_id' => $instructorRole->id,
            'is_active' => true,
        ]);
    }

    public function test_readiness_audit_reports_incomplete_domain_model(): void
    {
        $course = $this->courseForInstructor();
        $objective = $this->objectiveFor($course);

        foreach (['C01', 'C02', 'C03'] as $index => $code) {
            LearningConcept::factory()->create([
                'course_id' => $course->id,
                'learning_objective_id' => $objective->id,
                'code' => $code,
                'order' => $index + 1,
                'is_active' => true,
            ]);
        }

        $this->actingAs($this->instructor)
            ->get(route('instructor.learning-concepts.index', [
                'course_id' => $course->id,
                'readiness' => 1,
            ]))
            ->assertOk()
            ->assertSee('R1.4 — Acceptance Audit')
            ->assertSee('BELUM PASS — Lengkapi Checklist')
            ->assertSee('3 concept aktif saat ini.')
            ->assertSee('Belum ada soal pada bank soal yang terhubung ke course.');
    }

    public function test_readiness_audit_passes_when_domain_model_gate_is_complete(): void
    {
        $course = $this->courseForInstructor();
        $objective = $this->objectiveFor($course);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $bank = QuestionBank::factory()->create([
            'instructor_id' => $this->instructor->id,
            'course_id' => $course->id,
        ]);

        foreach (['C01', 'C02', 'C03', 'C04'] as $index => $code) {
            $concept = LearningConcept::factory()->create([
                'course_id' => $course->id,
                'learning_objective_id' => $objective->id,
                'code' => $code,
                'order' => $index + 1,
                'is_active' => true,
            ]);

            $material = LearningMaterial::factory()->create([
                'section_id' => $section->id,
                'order' => $index + 1,
            ]);
            $material->learningConcepts()->attach($concept->id, [
                'relevance_weight' => 1.00,
            ]);

            $question = Question::factory()->create([
                'question_bank_id' => $bank->id,
                'difficulty' => Question::DIFFICULTY_MEDIUM,
            ]);
            $question->learningConcepts()->attach($concept->id, [
                'weight' => 1.00,
                'difficulty_level' => Question::DIFFICULTY_MEDIUM,
            ]);
        }

        $this->actingAs($this->instructor)
            ->get(route('instructor.learning-concepts.index', [
                'course_id' => $course->id,
                'readiness' => 1,
            ]))
            ->assertOk()
            ->assertSee('PASS — Domain Model Siap')
            ->assertSee('4/4 gate terpenuhi')
            ->assertSee('Semua 4 soal course sudah memiliki concept mapping.');
    }

    private function courseForInstructor(): Course
    {
        return Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Pemrograman Dasar',
        ]);
    }

    private function objectiveFor(Course $course): LearningObjective
    {
        return LearningObjective::create([
            'course_id' => $course->id,
            'code' => 'LO-01',
            'title' => 'Menerapkan struktur kontrol pemrograman',
            'order' => 1,
            'is_active' => true,
        ]);
    }
}
