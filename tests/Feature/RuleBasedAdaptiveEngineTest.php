<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleBasedAdaptiveEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requests_additional_diagnostic_when_current_evidence_is_insufficient(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $this->mastery($student, $concept, 90, 1);

        $decision = app(RuleBasedAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('additional_diagnostic', $decision->action);
        $this->assertSame('current_evidence_insufficient', $decision->reasonCode);
        $this->assertSame($concept->id, $decision->targetConceptId);
        $this->assertSame('rule-based-v1', $decision->algorithmCode);
        $this->assertSame('1.0.0', $decision->algorithmVersion);
    }

    public function test_it_requests_prerequisite_diagnostic_before_remediation_when_prerequisite_evidence_is_missing(): void
    {
        [$student, $course] = $this->context();
        $prerequisite = $this->concept($course, 'C01', 1);
        $concept = $this->concept($course, 'C02', 2);
        $this->attachPrerequisite($concept, $prerequisite);
        $this->mastery($student, $concept, 60, 2);

        $decision = app(RuleBasedAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('additional_diagnostic', $decision->action);
        $this->assertSame('prerequisite_evidence_insufficient', $decision->reasonCode);
        $this->assertSame($prerequisite->id, $decision->targetConceptId);
    }

    public function test_it_prioritizes_prerequisite_remediation_when_prerequisite_is_below_threshold(): void
    {
        [$student, $course] = $this->context();
        $prerequisite = $this->concept($course, 'C01', 1);
        $concept = $this->concept($course, 'C02', 2);
        $this->attachPrerequisite($concept, $prerequisite);
        $this->mastery($student, $prerequisite, 50, 2);
        $this->mastery($student, $concept, 90, 2);

        $decision = app(RuleBasedAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('prerequisite_remediation', $decision->action);
        $this->assertSame('prerequisite_below_threshold', $decision->reasonCode);
        $this->assertSame($prerequisite->id, $decision->targetConceptId);
    }

    public function test_it_returns_remediation_for_low_mastery(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $this->mastery($student, $concept, 33.33, 3);

        $decision = app(RuleBasedAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('remediation', $decision->action);
        $this->assertSame('mastery_low', $decision->reasonCode);
        $this->assertSame($concept->id, $decision->targetConceptId);
    }

    public function test_it_returns_reinforcement_for_medium_mastery(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $this->mastery($student, $concept, 66.67, 3);

        $decision = app(RuleBasedAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('reinforcement', $decision->action);
        $this->assertSame('mastery_medium', $decision->reasonCode);
    }

    public function test_high_mastery_moves_to_next_ready_concept_deterministically(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $nextConcept = $this->concept($course, 'C02', 2);
        $this->attachPrerequisite($nextConcept, $concept);
        $this->mastery($student, $concept, 90, 2);

        $engine = app(RuleBasedAdaptiveEngine::class);
        $first = $engine->decide($student, $concept);
        $second = $engine->decide($student, $concept);

        $this->assertSame('next_concept', $first->action);
        $this->assertSame('mastery_high_next_ready', $first->reasonCode);
        $this->assertSame($nextConcept->id, $first->targetConceptId);
        $this->assertSame($first->toArray(), $second->toArray());
    }

    public function test_high_mastery_on_final_concept_returns_enrichment(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C06', 6);
        $this->mastery($student, $concept, 100, 2);

        $decision = app(RuleBasedAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('enrichment', $decision->action);
        $this->assertSame('mastery_high_no_next_ready', $decision->reasonCode);
        $this->assertSame($concept->id, $decision->targetConceptId);
    }

    /** @return array{User, Course} */
    private function context(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);

        return [$student, $course];
    }

    private function concept(Course $course, string $code, int $order): LearningConcept
    {
        return LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => $code,
            'name' => $code,
            'order' => $order,
            'is_active' => true,
        ]);
    }

    private function attachPrerequisite(LearningConcept $concept, LearningConcept $prerequisite): void
    {
        $concept->prerequisites()->attach($prerequisite->id, [
            'weight' => 1.00,
            'order' => 1,
        ]);
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
