<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\FuzzyAdaptiveEngine;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Numerical regression checks using explicitly tabulated reference cases.
 *
 * These tests verify implementation behaviour, not pedagogical validity.
 * The reference centroids were independently calculated from the frozen
 * Mamdani min/max rules with discrete x = 0..100, step = 1.
 */
class AdaptiveNumericBoundaryValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_rule_based_mastery_boundaries_match_configured_categories(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $engine = app(RuleBasedAdaptiveEngine::class);

        $cases = [
            [39.99, 'remediation', 'mastery_low'],
            [40.00, 'reinforcement', 'mastery_medium'],
            [79.99, 'reinforcement', 'mastery_medium'],
            [80.00, 'enrichment', 'mastery_high_no_next_ready'],
        ];

        foreach ($cases as [$score, $action, $reason]) {
            $this->mastery($student, $concept, $score, 2);
            $decision = $engine->decide($student, $concept);

            $this->assertSame($action, $decision->action, "Rule-Based action at mastery {$score}");
            $this->assertSame($reason, $decision->reasonCode, "Rule-Based reason at mastery {$score}");
            $this->assertSame($concept->id, $decision->targetConceptId);
        }
    }

    public function test_rule_based_evidence_and_prerequisite_boundaries_are_inclusive_as_specified(): void
    {
        [$student, $course] = $this->context();
        $prerequisite = $this->concept($course, 'C01', 1);
        $concept = $this->concept($course, 'C02', 2);
        $concept->prerequisites()->attach($prerequisite->id, ['weight' => 1.0, 'order' => 1]);
        $engine = app(RuleBasedAdaptiveEngine::class);

        $this->mastery($student, $concept, 90.0, 1);
        $this->mastery($student, $prerequisite, 69.99, 2);
        $this->assertSame('current_evidence_insufficient', $engine->decide($student, $concept)->reasonCode);

        $this->mastery($student, $concept, 90.0, 2);
        $this->mastery($student, $prerequisite, 70.0, 1);
        $decision = $engine->decide($student, $concept);
        $this->assertSame('additional_diagnostic', $decision->action);
        $this->assertSame('prerequisite_evidence_insufficient', $decision->reasonCode);

        $this->mastery($student, $prerequisite, 69.99, 2);
        $decision = $engine->decide($student, $concept);
        $this->assertSame('prerequisite_remediation', $decision->action);
        $this->assertSame($prerequisite->id, $decision->targetConceptId);

        $this->mastery($student, $prerequisite, 70.0, 2);
        $decision = $engine->decide($student, $concept);
        $this->assertSame('enrichment', $decision->action);
        $this->assertSame('mastery_high_no_next_ready', $decision->reasonCode);
    }

    public function test_fuzzy_centroids_match_independently_calculated_reference_cases(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $engine = app(FuzzyAdaptiveEngine::class);

        // No prerequisite: prerequisite_mastery defaults to 100.
        // Expected crisp outputs calculated independently (discrete centroid 0..100).
        // The selected fuzzy output is checked separately from target-resolution fallback.
        $cases = [
            [20.0, 3, 45.0000, 'remediation'],
            [40.0, 3, 50.8528, 'remediation'],
            [60.0, 3, 60.0000, 'reinforcement'],
            [80.0, 3, 77.3333, 'next_concept'],
            [90.0, 1, 8.1111, 'additional_diagnostic'],
            [90.0, 4, 77.2222, 'next_concept'],
            [90.0, 5, 93.4444, 'enrichment'],
        ];

        foreach ($cases as [$score, $evidenceCount, $expectedCentroid, $expectedOutput]) {
            $this->mastery($student, $concept, $score, $evidenceCount);
            $decision = $engine->decide($student, $concept);
            $label = "Fuzzy mastery={$score}, evidence={$evidenceCount}";

            $this->assertEqualsWithDelta(
                $expectedCentroid,
                (float) $decision->metadata['crisp_output'],
                0.00015,
                $label.' centroid'
            );
            $this->assertSame($expectedOutput, $decision->metadata['selected_output'], $label.' label');
            $this->assertNotEmpty($decision->metadata['fired_rules'], $label.' rule coverage');
            $this->assertSame(100.0, $decision->inputs['prerequisite_mastery']);
        }
    }

    public function test_fuzzy_weak_prerequisite_matches_independent_centroid_and_target(): void
    {
        [$student, $course] = $this->context();
        $prerequisite = $this->concept($course, 'C01', 1);
        $concept = $this->concept($course, 'C02', 2);
        $concept->prerequisites()->attach($prerequisite->id, ['weight' => 1.0, 'order' => 1]);

        $this->mastery($student, $concept, 90.0, 3);
        $this->mastery($student, $prerequisite, 30.0, 3);
        $decision = app(FuzzyAdaptiveEngine::class)->decide($student, $concept);

        $this->assertEqualsWithDelta(27.3333, (float) $decision->metadata['crisp_output'], 0.00015);
        $this->assertSame('prerequisite_remediation', $decision->metadata['selected_output']);
        $this->assertSame('prerequisite_remediation', $decision->action);
        $this->assertSame($prerequisite->id, $decision->targetConceptId);
        $this->assertSame('weakest_prerequisite', $decision->metadata['target_resolution']);
    }

    public function test_fuzzy_rule_coverage_remains_nonzero_for_boundary_state_grid(): void
    {
        [$student, $course] = $this->context();
        $concept = $this->concept($course, 'C01', 1);
        $engine = app(FuzzyAdaptiveEngine::class);

        foreach ([0.0, 20.0, 40.0, 60.0, 80.0, 100.0] as $score) {
            foreach ([0, 1, 2, 3, 4, 5, 10, 11] as $evidenceCount) {
                $this->mastery($student, $concept, $score, $evidenceCount);
                $decision = $engine->decide($student, $concept);
                $label = "mastery={$score}, evidence={$evidenceCount}";

                $this->assertNotEmpty($decision->metadata['fired_rules'], $label);
                $this->assertGreaterThanOrEqual(0.0, (float) $decision->metadata['crisp_output'], $label);
                $this->assertLessThanOrEqual(100.0, (float) $decision->metadata['crisp_output'], $label);
            }
        }
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

    private function mastery(User $student, LearningConcept $concept, float $score, int $evidenceCount): void
    {
        LearnerMastery::query()->updateOrCreate([
            'student_id' => $student->id,
            'concept_id' => $concept->id,
        ], [
            'mastery_score' => $score,
            'status' => $evidenceCount > 0 ? 'observed' : 'insufficient_evidence',
            'evidence_count' => $evidenceCount,
            'last_evidence_at' => now(),
        ]);
    }
}
