<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\MasteryHistory;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearnerModelAcceptanceAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $instructor;

    private User $studentA;

    private User $studentB;

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
        $this->studentA = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
            'name' => 'Siswa A',
        ]);
        $this->studentB = User::factory()->create([
            'role_id' => $studentRole->id,
            'is_active' => true,
            'name' => 'Siswa B',
        ]);
    }

    public function test_audit_remains_pending_when_only_one_complete_student_profile_exists(): void
    {
        [$course, $concepts] = $this->courseWithConcepts();
        $this->seedProfile($this->studentA, $concepts, [25, 50]);

        $this->actingAs($this->instructor)
            ->get(route('instructor.mastery.audit', ['course_id' => $course->id]))
            ->assertOk()
            ->assertSee('BELUM PASS — Lengkapi Evidence')
            ->assertSee('1 siswa memiliki evidence pada seluruh concept aktif.')
            ->assertSee('Siswa A')
            ->assertSee('Profil lengkap')
            ->assertSee('Explainable');
    }

    public function test_audit_passes_when_two_complete_explainable_profiles_are_different(): void
    {
        [$course, $concepts] = $this->courseWithConcepts();
        $this->seedProfile($this->studentA, $concepts, [25, 50], 101);
        $this->seedProfile($this->studentB, $concepts, [75, 100], 202);

        $this->actingAs($this->instructor)
            ->get(route('instructor.mastery.audit', ['course_id' => $course->id]))
            ->assertOk()
            ->assertSee('PASS — Learner Model Siap')
            ->assertSee('4/4 gate terpenuhi')
            ->assertSee('2 profil mastery unik terdeteksi')
            ->assertSee('2 dari 2 profil lengkap memiliki diagnostic history')
            ->assertSee('Siswa A')
            ->assertSee('Siswa B');
    }

    public function test_audit_rejects_identical_complete_profiles_as_not_distinct(): void
    {
        [$course, $concepts] = $this->courseWithConcepts();
        $this->seedProfile($this->studentA, $concepts, [50, 50], 301);
        $this->seedProfile($this->studentB, $concepts, [50, 50], 401);

        $this->actingAs($this->instructor)
            ->get(route('instructor.mastery.audit', ['course_id' => $course->id]))
            ->assertOk()
            ->assertSee('BELUM PASS — Lengkapi Evidence')
            ->assertSee('1 profil mastery unik terdeteksi')
            ->assertSee('3/4 gate terpenuhi');
    }

    /** @return array{Course, \Illuminate\Support\Collection<int, LearningConcept>} */
    private function courseWithConcepts(): array
    {
        $course = Course::factory()->create([
            'instructor_id' => $this->instructor->id,
            'title' => 'Pemrograman Dasar',
        ]);

        $concepts = collect([
            LearningConcept::factory()->create([
                'course_id' => $course->id,
                'code' => 'C01',
                'name' => 'Variabel dan Tipe Data',
                'order' => 1,
                'is_active' => true,
            ]),
            LearningConcept::factory()->create([
                'course_id' => $course->id,
                'code' => 'C02',
                'name' => 'Operator dan Ekspresi',
                'order' => 2,
                'is_active' => true,
            ]),
        ]);

        return [$course, $concepts];
    }

    /** @param \Illuminate\Support\Collection<int, LearningConcept> $concepts */
    private function seedProfile(User $student, $concepts, array $scores, int $attemptBase = 1): void
    {
        foreach ($concepts->values() as $index => $concept) {
            LearnerMastery::create([
                'student_id' => $student->id,
                'concept_id' => $concept->id,
                'mastery_score' => $scores[$index],
                'status' => 'observed',
                'evidence_count' => 2,
                'last_evidence_at' => now(),
            ]);

            MasteryHistory::create([
                'student_id' => $student->id,
                'concept_id' => $concept->id,
                'before_score' => null,
                'after_score' => $scores[$index],
                'evidence_count' => 2,
                'evidence_type' => 'diagnostic_quiz_attempt',
                'evidence_id' => $attemptBase + $index,
                'model_version' => 'diagnostic-weighted-v1',
                'evidence_payload' => [
                    'quiz_id' => 10,
                    'calculation' => 'weighted_correctness',
                ],
            ]);
        }
    }
}
