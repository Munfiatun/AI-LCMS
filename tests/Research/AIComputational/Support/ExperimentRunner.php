<?php

declare(strict_types=1);

namespace Tests\Research\AIComputational\Support;

use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\FuzzyAdaptiveEngine;
use App\Services\Learning\Adaptive\RuleBasedAdaptiveEngine;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

final class ExperimentRunner
{
    public const INPUT = [
        'case_id', 'dataset', 'seed', 'mastery_score', 'evidence_count',
        'prereq1_state', 'prereq1_mastery', 'prereq1_evidence',
        'prereq2_state', 'prereq2_mastery', 'prereq2_evidence', 'next_context', 'scenario',
    ];
    public const OUTPUT = [
        'case_id', 'dataset', 'seed', 'algorithm_code', 'algorithm_version',
        'config_hash', 'fixture_hash', 'variant_id', 'replicate', 'success',
        'error_class', 'error_message_sanitized', 'final_action', 'target_concept_code',
        'reason_code', 'selected_fuzzy_output', 'centroid', 'fired_rule_ids', 'execution_ns',
    ];
    public const FILES = [
        'dataset_grid.csv' => ['D1', 336], 'dataset_boundary.csv' => ['D2', 128],
        'dataset_scenario.csv' => ['D3', 16], 'dataset_random.csv' => ['D4', 1000],
    ];
    private const ALGORITHMS = [
        'rule-based-v1' => [RuleBasedAdaptiveEngine::class, '1.0.0'],
        'fuzzy-v2' => [FuzzyAdaptiveEngine::class, '2.0.0-experimental'],
    ];
    private const ACTIONS = [
        'additional_diagnostic', 'prerequisite_remediation', 'remediation',
        'reinforcement', 'next_concept', 'enrichment',
    ];

    public static function load(string $directory): array
    {
        $rows = [];
        $seen = [];
        foreach (self::FILES as $file => [$group, $expected]) {
            $handle = fopen($directory.'/'.$file, 'rb');
            if ($handle === false) {
                throw new RuntimeException('Cannot open input '.$file);
            }
            try {
                if (fgetcsv($handle, 0, ',', '"', '') !== self::INPUT) {
                    throw new RuntimeException('Unexpected CSV header '.$file);
                }
                $count = 0;
                while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                    if (count($values) !== count(self::INPUT)) {
                        throw new RuntimeException('Invalid row width '.$file);
                    }
                    $r = array_combine(self::INPUT, $values);
                    self::validate($r, $group);
                    if (isset($seen[$r['case_id']])) {
                        throw new RuntimeException('Duplicate case ID.');
                    }
                    $seen[$r['case_id']] = true;
                    $rows[] = $r;
                    $count++;
                }
                if ($count !== $expected) {
                    throw new RuntimeException('Unexpected row count '.$file);
                }
            } finally {
                fclose($handle);
            }
        }
        return $rows;
    }

    private static function validate(array $r, string $group): void
    {
        $score = static fn ($v) => is_string($v) && preg_match('/^\d{1,3}\.\d{2}$/D', $v) && (float) $v <= 100;
        $count = static fn ($v) => is_string($v) && preg_match('/^\d+$/D', $v) && strlen($v) <= 9;
        if ($r['dataset'] !== $group || ! preg_match('/^[A-Z0-9-]+$/D', $r['case_id'])
            || ! $score($r['mastery_score']) || ! $count($r['evidence_count'])
            || ! in_array($r['next_context'], ['none', 'ready', 'blocked', 'dual_ready', 'dual_blocked'], true)
            || ($group === 'D4' ? ! in_array($r['seed'], ['42','123','456','789','2026'], true) : $r['seed'] !== '')) {
            throw new RuntimeException('Invalid input case '.$r['case_id']);
        }
        foreach ([1, 2] as $i) {
            $state = $r["prereq{$i}_state"];
            $m = $r["prereq{$i}_mastery"];
            $e = $r["prereq{$i}_evidence"];
            if (! in_array($state, ['absent', 'missing', 'observed'], true)
                || ($state === 'observed' ? (! $score($m) || ! $count($e)) : ($m !== '' || $e !== ''))) {
                throw new RuntimeException('Invalid prerequisite '.$r['case_id']);
            }
        }
        if ($r['prereq1_state'] === 'absent' && $r['prereq2_state'] !== 'absent') {
            throw new RuntimeException('Invalid graph ordering '.$r['case_id']);
        }
    }

    /** Runs real Laravel engines. No recommendation persistence, no mock or reimplementation. */
    public static function run(array $profiles, string $out, string $commit): array
    {
        ExperimentFixture::assertIsolated();
        if (! preg_match('/^[a-f0-9]{40}$/D', $commit)) {
            throw new RuntimeException('AI_COMPUTATIONAL_COMMIT must identify the checked-out full SHA.');
        }
        if (! is_dir($out) || ! is_writable($out) || file_exists($out.'/comparative_results.csv')
            || file_exists($out.'/run_manifest.json')) {
            throw new RuntimeException('Supply a writable, fresh output directory.');
        }
        if (config('adaptive.baseline_algorithm') !== 'rule-based-v1'
            || config('adaptive.comparison_algorithm') !== 'fuzzy-v2') {
            throw new RuntimeException('Unexpected configured algorithms.');
        }
        $hashes = [];
        foreach (self::ALGORITHMS as $code => [, $version]) {
            $def = config('adaptive.algorithms.'.$code);
            if (($def['version'] ?? null) !== $version) {
                throw new RuntimeException('Unexpected version for '.$code);
            }
            $hashes[$code] = hash('sha256', json_encode($def['configuration'], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        }
        app(AdaptiveAlgorithmRegistry::class)->syncConfiguredVersions();
        $instructor = User::factory()->create(['name' => 'Synthetic instructor', 'email' => 'research-instructor@example.invalid']);
        $student = User::factory()->create(['name' => 'Synthetic learner', 'email' => 'research-learner@example.invalid']);

        $path = $out.'/comparative_results.csv';
        $handle = fopen($path, 'xb');
        if ($handle === false) {
            throw new RuntimeException('Cannot create results CSV.');
        }
        $counts = [];
        $attempted = $calls = $success = $failures = 0;
        $completePairs = $sameAction = $sameTarget = $sameBoth = 0;
        try {
            self::write($handle, self::OUTPUT);
            foreach ($profiles as $profile) {
                $group = $profile['dataset'];
                $counts[$group] ??= ['profiles' => 0, 'engine_calls' => 0, 'success' => 0, 'failures' => 0,
                    'complete_pairs' => 0, 'same_action' => 0, 'same_target' => 0, 'same_both' => 0];
                $counts[$group]['profiles']++;
                $level = DB::transactionLevel();
                DB::beginTransaction();
                $pair = [];
                try {
                    $fixture = null;
                    $fixtureError = null;
                    try {
                        $fixture = ExperimentFixture::build($profile, $instructor, $student);
                    } catch (Throwable $e) {
                        $fixtureError = $e;
                    }
                    foreach (self::ALGORITHMS as $code => [$class, $version]) {
                        $attempted++;
                        $record = array_fill_keys(self::OUTPUT, '');
                        $record = array_replace($record, [
                            'case_id' => $profile['case_id'], 'dataset' => $group, 'seed' => $profile['seed'],
                            'algorithm_code' => $code, 'algorithm_version' => $version,
                            'config_hash' => $hashes[$code],
                            'fixture_hash' => hash('sha256', json_encode($profile, JSON_THROW_ON_ERROR)),
                            'variant_id' => 'baseline', 'replicate' => 1, 'success' => 0,
                        ]);
                        if ($fixtureError !== null) {
                            $record['error_class'] = get_class($fixtureError);
                            $record['error_message_sanitized'] = 'fixture_setup_failed; engine_not_called';
                        } else {
                            $engineLevel = DB::transactionLevel();
                            DB::beginTransaction();
                            $start = null;
                            try {
                                $engine = app($class);
                                $start = hrtime(true);
                                $calls++;
                                $counts[$group]['engine_calls']++;
                                $decision = $engine->decide($fixture['student'], $fixture['concept']);
                                $record['execution_ns'] = hrtime(true) - $start;
                                if ($decision->algorithmCode !== $code || $decision->algorithmVersion !== $version) {
                                    throw new RuntimeException('Algorithm identity mismatch.');
                                }
                                // Keep actual outputs, even if a later specification check finds an anomaly.
                                $record['final_action'] = $decision->action;
                                $record['reason_code'] = $decision->reasonCode;
                                $record['target_concept_code'] = $fixture['targets'][$decision->targetConceptId] ?? '';
                                if ($code === 'fuzzy-v2') {
                                    $record['selected_fuzzy_output'] = $decision->metadata['selected_output'] ?? '';
                                    $record['centroid'] = isset($decision->metadata['crisp_output'])
                                        ? number_format((float) $decision->metadata['crisp_output'], 4, '.', '') : '';
                                    $record['fired_rule_ids'] = implode('|', array_column($decision->metadata['fired_rules'] ?? [], 'rule'));
                                }
                                if (! in_array($decision->action, self::ACTIONS, true) || $record['target_concept_code'] === '') {
                                    $record['error_class'] = 'SpecificationAnomaly';
                                    $record['error_message_sanitized'] = 'Unexpected action or unresolved target';
                                } else {
                                    $record['success'] = 1;
                                }
                            } catch (Throwable $e) {
                                if ($start !== null && $record['execution_ns'] === '') {
                                    $record['execution_ns'] = hrtime(true) - $start;
                                }
                                $record['error_class'] = get_class($e);
                                // Never export raw DB errors, credentials, or stack traces to public artifacts.
                                $record['error_message_sanitized'] = 'engine_or_output_processing_failed';
                            } finally {
                                DB::rollBack($engineLevel);
                            }
                        }
                        $pair[] = $record;
                        if ($record['success'] === 1) {
                            $success++;
                            $counts[$group]['success']++;
                        } else {
                            $failures++;
                            $counts[$group]['failures']++;
                        }
                        self::write($handle, array_values($record));
                    }
                    if ($pair[0]['success'] === 1 && $pair[1]['success'] === 1) {
                        $completePairs++;
                        $counts[$group]['complete_pairs']++;
                        $a = $pair[0]['final_action'] === $pair[1]['final_action'];
                        $b = $pair[0]['target_concept_code'] === $pair[1]['target_concept_code'];
                        $sameAction += (int) $a;
                        $sameTarget += (int) $b;
                        $sameBoth += (int) ($a && $b);
                        $counts[$group]['same_action'] += (int) $a;
                        $counts[$group]['same_target'] += (int) $b;
                        $counts[$group]['same_both'] += (int) ($a && $b);
                    }
                } finally {
                    DB::rollBack($level);
                }
            }
        } finally {
            fclose($handle);
        }
        $inputHashes = [];
        foreach (self::FILES as $file => $_) {
            $inputHashes[$file] = hash_file('sha256', base_path('research/ai_computational/datasets/'.$file));
        }
        $manifest = [
            'schema_version' => 1,
            'run_kind' => 'synthetic_comparative_engine_execution',
            'baseline_commit' => '8d99545be7039e97cc3778b356276efe4f5f1281',
            'experiment_commit' => $commit,
            'run_utc' => gmdate('c'),
            'php_version' => PHP_VERSION, 'laravel_version' => app()->version(),
            'os' => PHP_OS_FAMILY, 'database' => 'sqlite :memory:',
            'sqlite_version' => DB::selectOne('select sqlite_version() as version')->version,
            'input_sha256' => $inputHashes, 'algorithm_config_sha256' => $hashes,
            'adaptive_file_sha256' => hash_file('sha256', config_path('adaptive.php')),
            'engine_sha256' => [
                'rule-based-v1' => hash_file('sha256', app_path('Services/Learning/Adaptive/RuleBasedAdaptiveEngine.php')),
                'fuzzy-v2' => hash_file('sha256', app_path('Services/Learning/Adaptive/FuzzyAdaptiveEngine.php')),
            ],
            'results_sha256' => hash_file('sha256', $path),
            'profiles' => count($profiles), 'planned_rows' => count($profiles) * 2,
            'recorded_rows' => $attempted, 'actual_engine_calls' => $calls,
            'success' => $success, 'failures' => $failures, 'complete_pairs' => $completePairs,
            'same_action' => $sameAction, 'same_target' => $sameTarget, 'same_both' => $sameBoth,
            'by_dataset' => $counts, 'human_participants' => 0,
            'timing_note' => 'Single-call end-to-end diagnostic timing, not a controlled benchmark.',
            'claim_limit' => 'Agreement is not accuracy; no pedagogical or learning-effectiveness evidence.',
        ];
        if (file_put_contents($out.'/run_manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT).PHP_EOL) === false) {
            throw new RuntimeException('Manifest write failed.');
        }
        return $manifest;
    }

    private static function write($handle, array $row): void
    {
        if (fputcsv($handle, $row, ',', '"', '', "\n") === false) {
            throw new RuntimeException('Result CSV write failed.');
        }
    }
}
