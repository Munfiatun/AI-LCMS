<?php

namespace Tests\Feature;

use App\Models\AdaptiveAlgorithmVersion;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\AdaptiveDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdaptiveAlgorithmFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_persists_rule_based_baseline_and_fuzzy_experiment(): void
    {
        $registry = app(AdaptiveAlgorithmRegistry::class);

        $versions = $registry->syncConfiguredVersions();

        $this->assertCount(2, $versions);
        $this->assertDatabaseHas('adaptive_algorithm_versions', [
            'code' => 'rule-based-v1',
            'engine_type' => AdaptiveAlgorithmVersion::TYPE_RULE_BASED,
            'status' => AdaptiveAlgorithmVersion::STATUS_ACTIVE,
            'is_baseline' => true,
        ]);
        $this->assertDatabaseHas('adaptive_algorithm_versions', [
            'code' => 'fuzzy-v2',
            'engine_type' => AdaptiveAlgorithmVersion::TYPE_FUZZY_INFERENCE,
            'status' => AdaptiveAlgorithmVersion::STATUS_EXPERIMENTAL,
            'is_baseline' => false,
        ]);

        $baseline = $registry->baseline();
        $comparison = $registry->comparison();

        $this->assertSame('rule-based-v1', $baseline->code);
        $this->assertSame('research_draft', $baseline->configuration['validation_status']);
        $this->assertSame('fuzzy-v2', $comparison->code);
        $this->assertSame('mamdani', $comparison->configuration['inference_method']);
        $this->assertSame('centroid', $comparison->configuration['defuzzification']);
    }

    public function test_registry_sync_is_idempotent_and_preserves_activation_timestamp(): void
    {
        $registry = app(AdaptiveAlgorithmRegistry::class);

        $registry->syncConfiguredVersions();
        $activatedAt = AdaptiveAlgorithmVersion::where('code', 'rule-based-v1')->firstOrFail()->activated_at;

        $registry->syncConfiguredVersions();

        $this->assertDatabaseCount('adaptive_algorithm_versions', 2);
        $this->assertEquals(
            $activatedAt,
            AdaptiveAlgorithmVersion::where('code', 'rule-based-v1')->firstOrFail()->activated_at
        );
    }

    public function test_adaptive_decision_payload_keeps_algorithm_identity_and_reason(): void
    {
        $decision = new AdaptiveDecision(
            action: 'reinforcement',
            targetConceptId: 7,
            reasonCode: 'mastery_medium_prerequisite_ready',
            algorithmCode: 'rule-based-v1',
            algorithmVersion: '1.0.0',
            inputs: ['mastery_score' => 55.0, 'evidence_count' => 3],
            metadata: ['validation_status' => 'research_draft'],
        );

        $this->assertSame([
            'action' => 'reinforcement',
            'target_concept_id' => 7,
            'reason_code' => 'mastery_medium_prerequisite_ready',
            'algorithm_code' => 'rule-based-v1',
            'algorithm_version' => '1.0.0',
            'inputs' => ['mastery_score' => 55.0, 'evidence_count' => 3],
            'metadata' => ['validation_status' => 'research_draft'],
        ], $decision->toArray());
    }
}
