<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveEngineAcceptanceAuditService;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveEngineAcceptanceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_passes_when_all_r3_gates_are_satisfied(): void
    {
        [$student, $course, $concept] = $this->context();
        $this->mastery($student, $concept, 20, 3);

        app(AdaptiveRecommendationService::class)->generateForCourse($student, $course);

        $result = app(AdaptiveEngineAcceptanceAuditService::class)->audit($student, $course);

        $this->assertTrue($result['summary']['passed']);
        $this->assertSame(6, $result['summary']['passed_gates']);
        $this->assertSame(6, $result['summary']['total_gates']);
        $this->assertTrue(collect($result['gates'])->every(fn (array $gate): bool => $gate['passed']));
        $this->assertSame(100.0, $result['comparison']['full_agreement_rate']);
    }

    public function test_audit_fails_when_baseline_recommendation_has_not_been_persisted(): void
    {
        [$student, $course, $concept] = $this->context();
        $this->mastery($student, $concept, 20, 3);

        $result = app(AdaptiveEngineAcceptanceAuditService::class)->audit($student, $course);
        $persistenceGate = collect($result['gates'])->firstWhere(
            'title',
            'Recommendation baseline tersimpan dan dapat ditelusuri'
        );

        $this->assertFalse($result['summary']['passed']);
        $this->assertFalse($persistenceGate['passed']);
        $this->assertSame(5, $result['summary']['passed_gates']);
    }

    public function test_audit_detects_stale_recommendation_after_mastery_changes(): void
    {
        [$student, $course, $concept] = $this->context();
        $mastery = $this->mastery($student, $concept, 20, 3);

        app(AdaptiveRecommendationService::class)->generateForCourse($student, $course);

        $mastery->update([
            'mastery_score' => 66.67,
            'evidence_count' => 4,
            'last_evidence_at' => now()->addMinute(),
        ]);

        $result = app(AdaptiveEngineAcceptanceAuditService::class)->audit($student, $course);
        $row = $result['rows']->first();

        $this->assertFalse($result['summary']['passed']);
        $this->assertFalse($row['recommendation_traceable']);
        $this->assertSame('reinforcement', $row['rule_action']);

        app(AdaptiveRecommendationService::class)->generateForCourse($student, $course);
        $refreshed = app(AdaptiveEngineAcceptanceAuditService::class)->audit($student, $course);

        $this->assertTrue($refreshed['summary']['passed']);
        $this->assertTrue($refreshed['rows']->first()['recommendation_traceable']);
    }

    /** @return array{User, Course, LearningConcept} */
    private function context(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create(['name' => 'Audit Student']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Pemrograman Dasar',
        ]);
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'C01',
            'name' => 'Variabel dan Tipe Data',
            'order' => 1,
            'is_active' => true,
        ]);

        return [$student, $course, $concept];
    }

    private function mastery(User $student, LearningConcept $concept, float $score, int $evidenceCount): LearnerMastery
    {
        return LearnerMastery::create([
            'student_id' => $student->id,
            'concept_id' => $concept->id,
            'mastery_score' => $score,
            'status' => 'observed',
            'evidence_count' => $evidenceCount,
            'last_evidence_at' => now(),
        ]);
    }
}
