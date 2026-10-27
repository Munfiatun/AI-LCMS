<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmComparisonService;
use App\Services\Learning\Adaptive\AdaptiveDecision;
use App\Services\Learning\Adaptive\FuzzyAdaptiveEngine;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdaptiveAlgorithmComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_compares_both_engines_for_the_same_learner_state(): void
    {
        [$student, $course, $concept] = $this->context();
        $this->mastery($student, $concept, 20, 3);

        $result = app(AdaptiveAlgorithmComparisonService::class)->compareCourse($student, $course);
        $row = $result['rows']->first();

        $this->assertSame('rule-based-v1', $row['rule_decision']->algorithmCode);
        $this->assertSame('fuzzy-v2', $row['fuzzy_decision']->algorithmCode);
        $this->assertSame('remediation', $row['rule_action']);
        $this->assertSame('remediation', $row['fuzzy_action']);
        $this->assertTrue($row['action_agreement']);
        $this->assertTrue($row['target_agreement']);
        $this->assertSame(100.0, $result['summary']['full_agreement_rate']);
        $this->assertIsNumeric($row['fuzzy_crisp_output']);
    }

    public function test_it_reports_divergence_without_treating_it_as_an_error(): void
    {
        [$student, $course, $concept] = $this->context();

        $rule = Mockery::mock(RuleBasedAdaptiveEngine::class);
        $rule->shouldReceive('decide')
            ->once()
            ->andReturn(new AdaptiveDecision(
                action: 'reinforcement',
                targetConceptId: $concept->id,
                reasonCode: 'mastery_medium',
                algorithmCode: 'rule-based-v1',
                algorithmVersion: '1.0.0',
            ));

        $fuzzy = Mockery::mock(FuzzyAdaptiveEngine::class);
        $fuzzy->shouldReceive('decide')
            ->once()
            ->andReturn(new AdaptiveDecision(
                action: 'remediation',
                targetConceptId: $concept->id,
                reasonCode: 'fuzzy_mamdani_centroid',
                algorithmCode: 'fuzzy-v2',
                algorithmVersion: '2.0.0-experimental',
                metadata: ['crisp_output' => 47.5, 'fired_rules' => [['rule' => 6, 'strength' => 0.5]]],
            ));

        $result = (new AdaptiveAlgorithmComparisonService($rule, $fuzzy))->compareCourse($student, $course);
        $row = $result['rows']->first();

        $this->assertFalse($row['action_agreement']);
        $this->assertTrue($row['target_agreement']);
        $this->assertFalse($row['full_agreement']);
        $this->assertSame(1, $result['summary']['action_divergences']);
        $this->assertSame(1, $result['summary']['full_divergences']);
        $this->assertSame(0.0, $result['summary']['full_agreement_rate']);
    }

    public function test_comparison_is_deterministic_for_unchanged_state(): void
    {
        [$student, $course, $concept] = $this->context();
        $this->mastery($student, $concept, 66.67, 3);

        $service = app(AdaptiveAlgorithmComparisonService::class);
        $first = $service->compareCourse($student, $course);
        $second = $service->compareCourse($student, $course);

        $firstRows = $first['rows']->map(fn (array $row): array => [
            'rule' => $row['rule_decision']->toArray(),
            'fuzzy' => $row['fuzzy_decision']->toArray(),
            'full_agreement' => $row['full_agreement'],
        ])->all();

        $secondRows = $second['rows']->map(fn (array $row): array => [
            'rule' => $row['rule_decision']->toArray(),
            'fuzzy' => $row['fuzzy_decision']->toArray(),
            'full_agreement' => $row['full_agreement'],
        ])->all();

        $this->assertSame($firstRows, $secondRows);
        $this->assertSame($first['summary'], $second['summary']);
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
