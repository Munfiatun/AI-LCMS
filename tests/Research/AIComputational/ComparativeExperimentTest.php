<?php

declare(strict_types=1);

namespace Tests\Research\AIComputational;

use Tests\Research\AIComputational\Support\ExperimentFixture;
use Tests\Research\AIComputational\Support\ExperimentRunner;
use Tests\TestCase;

final class ComparativeExperimentTest extends TestCase
{
    public function test_run_frozen_synthetic_comparison(): void
    {
        // No RefreshDatabase trait: validate actual connection BEFORE any migration.
        ExperimentFixture::assertIsolated();
        $out = getenv('AI_COMPUTATIONAL_OUTPUT');
        $commit = getenv('AI_COMPUTATIONAL_COMMIT');
        $this->assertIsString($out, 'Provide a fresh output directory.');
        $this->assertIsString($commit, 'Provide the checked-out full commit SHA.');
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $profiles = ExperimentRunner::load(base_path('research/ai_computational/datasets'));
        $this->assertCount(1480, $profiles);
        $manifest = ExperimentRunner::run($profiles, $out, $commit);

        // Print only aggregate evidence (no personal data), then fail CI if any row failed.
        fwrite(STDOUT, PHP_EOL.'COMPARATIVE_MANIFEST '.json_encode($manifest, JSON_THROW_ON_ERROR).PHP_EOL);
        $this->assertSame(2960, $manifest['recorded_rows']);
        $this->assertSame(2960, $manifest['actual_engine_calls'], 'One actual engine call per planned row.');
        $this->assertSame(0, $manifest['failures'], 'Inspect exported failure rows; do not remove them.');
        $this->assertSame(1480, $manifest['complete_pairs']);
    }
}
