<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\FuzzyAdaptiveEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuzzyAdaptiveEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_evidence_produces_additional_diagnostic_with_explainable_fuzzy_metadata(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $this->mastery($student, $concept, 90, 1);

        $decision = app(FuzzyAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('additional_diagnostic', $decision->action);
        $this->assertSame($concept->id, $decision->targetConceptId);
        $this->assertSame('fuzzy_mamdani_centroid', $decision->reasonCode);
        $this->assertSame('fuzzy-v2', $decision->algorithmCode);
        $this->assertSame('2.0.0-experimental', $decision->algorithmVersion);
        $this->assertSame('mamdani', $decision->metadata['inference_method']);
        $this->assertSame('centroid', $decision->metadata['defuzzification']);
        $this->assertSame(1.0, $decision->metadata['memberships']['evidence_count']['low']);
        $this->assertNotEmpty($decision->metadata['fired_rules']);
        $this->assertGreaterThan(0, $decision->metadata['crisp_output']);
    }

    public function test_low_mastery_with_sufficient_evidence_produces_remediation(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $this->mastery($student, $concept, 20, 3);

        $decision = app(FuzzyAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('remediation', $decision->action);
        $this->assertSame($concept->id, $decision->targetConceptId);
        $this->assertEqualsWithDelta(45.0, $decision->metadata['crisp_output'], 0.6);
        $this->assertSame('remediation', $decision->metadata['selected_output']);
    }

    public function test_weak_prerequisite_produces_prerequisite_remediation_targeting_weakest_concept(): void
    {
        [$student, $course] = $this->context();
        $prerequisite = $this->concept($course, 'C01', 1);
        $concept = $this->concept($course, 'C02', 2);
        $this->attachPrerequisite($concept, $prerequisite);
        $this->mastery($student, $prerequisite, 30, 3);
        $this->mastery($student, $concept, 90, 3);

        $decision = app(FuzzyAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('prerequisite_remediation', $decision->action);
        $this->assertSame($prerequisite->id, $decision->targetConceptId);
        $this->assertSame('C01', $decision->metadata['target_concept_code']);
        $this->assertSame('weakest_prerequisite', $decision->metadata['target_resolution']);
        $this->assertSame(30.0, $decision->inputs['prerequisite_mastery']);
    }

    public function test_medium_mastery_produces_reinforcement(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $this->mastery($student, $concept, 60, 3);

        $decision = app(FuzzyAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('reinforcement', $decision->action);
        $this->assertSame($concept->id, $decision->targetConceptId);
        $this->assertEqualsWithDelta(60.0, $decision->metadata['crisp_output'], 0.6);
    }

    public function test_high_mastery_with_sufficient_evidence_moves_to_next_ready_concept_deterministically(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $nextConcept = $this->concept($course, 'C02', 2);
        $this->attachPrerequisite($nextConcept, $concept);
        $this->mastery($student, $concept, 90, 3);

        $engine = app(FuzzyAdaptiveEngine::class);
        $first = $engine->decide($student, $concept);
        $second = $engine->decide($student, $concept);

        $this->assertSame('next_concept', $first->action);
        $this->assertSame($nextConcept->id, $first->targetConceptId);
        $this->assertSame('next_ready_concept', $first->metadata['target_resolution']);
        $this->assertSame($first->toArray(), $second->toArray());
    }

    public function test_high_mastery_with_high_evidence_produces_enrichment(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C06', 6);
        $this->mastery($student, $concept, 90, 5);

        $decision = app(FuzzyAdaptiveEngine::class)->decide($student, $concept);

        $this->assertSame('enrichment', $decision->action);
        $this->assertSame($concept->id, $decision->targetConceptId);
        $this->assertGreaterThan(90, $decision->metadata['crisp_output']);
        $this->assertSame('enrichment', $decision->metadata['selected_output']);
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
