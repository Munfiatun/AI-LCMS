<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveRecommendationPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_explainable_versioned_recommendations(): void
    {
        [$student, $course, $concept] = $this->context();
        $this->mastery($student, $concept, 33.33, 3);

        $recommendations = app(AdaptiveRecommendationService::class)->generateForCourse($student, $course);

        $this->assertCount(1, $recommendations);

        $recommendation = $recommendations->firstOrFail()->load('algorithmVersion');

        $this->assertSame('remediation', $recommendation->action);
        $this->assertSame('mastery_low', $recommendation->reason_code);
        $this->assertSame(AdaptiveRecommendation::STATUS_ACTIVE, $recommendation->status);
        $this->assertSame($concept->id, $recommendation->source_concept_id);
        $this->assertSame($concept->id, $recommendation->target_concept_id);
        $this->assertSame('rule-based-v1', $recommendation->algorithmVersion->code);
        $this->assertSame('1.0.0', $recommendation->algorithmVersion->version);
        $this->assertSame(33.33, $recommendation->inputs['mastery_score']);
        $this->assertSame('C01', $recommendation->metadata['target_concept_code']);
    }

    public function test_regenerating_same_state_is_idempotent(): void
    {
        [$student, $course, $concept] = $this->context();
        $this->mastery($student, $concept, 33.33, 3);

        $service = app(AdaptiveRecommendationService::class);
        $first = $service->generateForCourse($student, $course)->firstOrFail();
        $second = $service->generateForCourse($student, $course)->firstOrFail();

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('adaptive_recommendations', 1);
        $this->assertDatabaseHas('adaptive_recommendations', [
            'id' => $first->id,
            'status' => AdaptiveRecommendation::STATUS_ACTIVE,
        ]);
    }

    public function test_changed_mastery_supersedes_previous_recommendation_and_preserves_history(): void
    {
        [$student, $course, $concept] = $this->context();
        $mastery = $this->mastery($student, $concept, 33.33, 3);

        $service = app(AdaptiveRecommendationService::class);
        $previous = $service->generateForCourse($student, $course)->firstOrFail();

        $mastery->update([
            'mastery_score' => 90,
            'evidence_count' => 4,
            'last_evidence_at' => now()->addMinute(),
        ]);

        $current = $service->generateForCourse($student, $course)->firstOrFail();

        $this->assertNotSame($previous->id, $current->id);
        $this->assertSame('enrichment', $current->action);
        $this->assertSame('mastery_high_no_next_ready', $current->reason_code);
        $this->assertSame(AdaptiveRecommendation::STATUS_ACTIVE, $current->status);
        $this->assertSame(AdaptiveRecommendation::STATUS_SUPERSEDED, $previous->refresh()->status);
        $this->assertNotNull($previous->refresh()->superseded_at);
        $this->assertDatabaseCount('adaptive_recommendations', 2);
    }

    /** @return array{User, Course, LearningConcept} */
    private function context(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
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
