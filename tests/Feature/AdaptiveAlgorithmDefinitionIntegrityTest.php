<?php

namespace Tests\Feature;

use App\Models\AdaptiveAlgorithmVersion;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AdaptiveAlgorithmDefinitionIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_registered_definition_remains_idempotent(): void
    {
        $registry = app(AdaptiveAlgorithmRegistry::class);
        $original = $registry->baseline();

        $registry->syncConfiguredVersions();

        $this->assertDatabaseCount('adaptive_algorithm_versions', 2);
        $unchanged = AdaptiveAlgorithmVersion::query()->where('code', 'rule-based-v1')->sole();

        $this->assertSame($original->id, $unchanged->id);
        $this->assertSame($original->version, $unchanged->version);
        $this->assertEquals($original->configuration, $unchanged->configuration);
    }

    public function test_registered_configuration_cannot_be_silently_rewritten(): void
    {
        $registry = app(AdaptiveAlgorithmRegistry::class);
        $original = $registry->baseline();
        $originalConfig = $original->configuration;

        config()->set('adaptive.algorithms.rule-based-v1.configuration.mastery_thresholds.low_max', 45.0);

        try {
            $registry->baseline();
            $this->fail('Expected an exception for an altered research algorithm configuration.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('new algorithm code', $exception->getMessage());
        }

        $stored = AdaptiveAlgorithmVersion::query()->where('code', 'rule-based-v1')->sole();
        $this->assertSame($original->id, $stored->id);
        $this->assertEquals($originalConfig, $stored->configuration);
        $this->assertSame('1.0.0', $stored->version);
    }

    public function test_registered_algorithm_version_cannot_be_changed_using_the_same_code(): void
    {
        $registry = app(AdaptiveAlgorithmRegistry::class);
        $registry->baseline();

        config()->set('adaptive.algorithms.rule-based-v1.version', '1.1.0');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('new algorithm code');

        $registry->syncConfiguredVersions();
    }

    public function test_registered_engine_type_cannot_be_changed_using_the_same_code(): void
    {
        $registry = app(AdaptiveAlgorithmRegistry::class);
        $registry->baseline();

        config()->set('adaptive.algorithms.rule-based-v1.engine_type', 'fuzzy_inference');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('new algorithm code');

        $registry->syncConfiguredVersions();
    }

    public function test_new_code_can_register_a_new_algorithm_definition_without_mutating_existing_one(): void
    {
        $registry = app(AdaptiveAlgorithmRegistry::class);
        $baseline = $registry->baseline();

        $new = config('adaptive.algorithms.rule-based-v1');
        $new['version'] = '1.1.0';
        $new['name'] = 'Rule-Based Adaptive Engine v1.1';
        $new['status'] = AdaptiveAlgorithmVersion::STATUS_EXPERIMENTAL;
        $new['is_baseline'] = false;
        $new['configuration']['mastery_thresholds']['low_max'] = 45.0;
        config()->set('adaptive.algorithms.rule-based-v1-1', $new);

        $versions = $registry->syncConfiguredVersions();

        $this->assertCount(3, $versions);
        $this->assertDatabaseCount('adaptive_algorithm_versions', 3);
        $this->assertSame($baseline->id, $registry->baseline()->id);
        $this->assertSame(39.99, (float) $registry->baseline()->configuration['mastery_thresholds']['low_max']);
        $newStored = AdaptiveAlgorithmVersion::query()->where('code', 'rule-based-v1-1')->sole();
        $this->assertSame('1.1.0', $newStored->version);
        $this->assertSame(45.0, (float) $newStored->configuration['mastery_thresholds']['low_max']);
    }
}
