<?php

declare(strict_types=1);

namespace Tests\Research\AIComputational;

use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use App\Services\Learning\Adaptive\FuzzyAdaptiveEngine;
use Illuminate\Support\Facades\DB;
use Tests\Research\AIComputational\Support\ExperimentFixture;
use Tests\Research\AIComputational\Support\ExperimentRunner;
use Tests\TestCase;
use Throwable;

final class NumericalBoundaryExperimentTest extends TestCase
{
    public function test_additional_reference_and_isolated_boundary_cases(): void
    {
        ExperimentFixture::assertIsolated();
        $out = getenv('AI_PHASE3_OUTPUT');
        $this->assertIsString($out);
        $this->assertFileExists($out.'/additional_inputs.csv');
        $this->assertFileDoesNotExist($out.'/additional_actual.csv');
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        app(AdaptiveAlgorithmRegistry::class)->syncConfiguredVersions();
        $instructor = User::factory()->create(['name' => 'Synthetic instructor', 'email' => 'phase3-instructor@example.invalid']);
        $student = User::factory()->create(['name' => 'Synthetic learner', 'email' => 'phase3-student@example.invalid']);
        $input = fopen($out.'/additional_inputs.csv', 'rb');
        $output = fopen($out.'/additional_actual.csv', 'xb');
        $this->assertNotFalse($input);
        $this->assertNotFalse($output);
        $this->assertSame(ExperimentRunner::INPUT, fgetcsv($input, 0, ',', '"', ''));
        $header = ['case_id','dataset','algorithm_code','algorithm_version','success','error_class',
            'final_action','target_concept_code','reason_code','selected_fuzzy_output','centroid','fired_rule_ids'];
        fputcsv($output, $header, ',', '"', '', "\n");
        $profiles = $written = 0;
        try {
            while (($v = fgetcsv($input, 0, ',', '"', '')) !== false) {
                $r = array_combine(ExperimentRunner::INPUT, $v);
                $profiles++;
                $level = DB::transactionLevel();
                DB::beginTransaction();
                try {
                    $fixture = ExperimentFixture::build($r, $instructor, $student);
                    foreach (['rule-based-v1' => [RuleBasedAdaptiveEngine::class, '1.0.0'],
                        'fuzzy-v2' => [FuzzyAdaptiveEngine::class, '2.0.0-experimental']] as $code => [$class,$version]) {
                        $line = array_fill_keys($header, '');
                        $line = array_replace($line, ['case_id'=>$r['case_id'],'dataset'=>$r['dataset'],
                            'algorithm_code'=>$code,'algorithm_version'=>$version,'success'=>0]);
                        $nested = DB::transactionLevel();
                        DB::beginTransaction();
                        try {
                            $decision = app($class)->decide($student, $fixture['concept']);
                            $line['final_action'] = $decision->action;
                            $line['target_concept_code'] = $fixture['targets'][$decision->targetConceptId] ?? '';
                            $line['reason_code'] = $decision->reasonCode;
                            if ($code === 'fuzzy-v2') {
                                $line['selected_fuzzy_output'] = $decision->metadata['selected_output'] ?? '';
                                $line['centroid'] = isset($decision->metadata['crisp_output'])
                                    ? number_format((float) $decision->metadata['crisp_output'], 4, '.', '') : '';
                                $line['fired_rule_ids'] = implode('|', array_column($decision->metadata['fired_rules'] ?? [], 'rule'));
                            }
                            $line['success'] = (int) ($decision->algorithmCode === $code
                                && $decision->algorithmVersion === $version && $line['target_concept_code'] !== '');
                            if (! $line['success']) {
                                $line['error_class'] = 'SpecificationAnomaly';
                            }
                        } catch (Throwable $e) {
                            $line['error_class'] = get_class($e);
                        } finally {
                            DB::rollBack($nested);
                        }
                        if (fputcsv($output, array_values($line), ',', '"', '', "\n") === false) {
                            throw new \RuntimeException('Cannot write Phase 3 result.');
                        }
                        $written++;
                    }
                } finally {
                    DB::rollBack($level);
                }
            }
        } finally {
            fclose($input);
            fclose($output);
        }
        $this->assertSame(62, $profiles);
        $this->assertSame(124, $written);
        // Numerical and specification checks occur in the independent Python verifier.
        // Actual mismatches remain in the artifact, rather than modifying the production engines.
    }
}
