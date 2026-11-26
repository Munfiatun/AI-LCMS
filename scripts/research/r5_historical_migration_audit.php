<?php
/**
 * R5 synthetic historical-research migration acceptance harness.
 *
 * STRICTLY CI ONLY: never run against production/student data. Every command
 * checks its database name, GitHub Actions environment and driver *before*
 * accessing data. Snapshots contain only counts and SHA-256 digests.
 *
 * Modes: seed | snapshot <file> | verify <file> | postcheck
 */
declare(strict_types=1);

use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\ResearchEvent;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\ResearchEventRecorder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$mode = $argv[1] ?? '';
$file = $argv[2] ?? '';
$database = (string) DB::connection()->getDatabaseName();
$allowedDatabases = ['r5_history_source', 'r5_history_restore', 'r5_history_recovered'];

if (getenv('GITHUB_ACTIONS') !== 'true'
    || getenv('R5_HISTORY_CI_ACK') !== 'synthetic-only'
    || app()->environment() !== 'testing'
    || DB::getDriverName() !== 'mysql'
    || (string) config('database.connections.mysql.host') !== '127.0.0.1'
    || ! in_array($database, $allowedDatabases, true)
) {
    fwrite(STDERR, "REFUSED: R5 historical audit may only run on the allowlisted disposable MySQL GitHub Actions CI databases.\n");
    exit(10);
}

function requireCondition(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function canonicalHash(array $rows): string
{
    foreach ($rows as &$row) {
        ksort($row);
    }
    unset($row);

    return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

/**
 * Every original column is covered; the newly added key is excluded so the
 * original row digest must be *exactly* equal after the migration.
 * Only digests and counts, never raw student IDs/metadata, are persisted.
 */
function snapshot(): array
{
    $tracked = [
        'users', 'courses', 'learning_objectives', 'learning_concepts',
        'experiment_assignments', 'research_events', 'adaptive_algorithm_versions',
        'adaptive_recommendations',
    ];
    $tables = [];

    foreach ($tracked as $table) {
        requireCondition(Schema::hasTable($table), "Expected research table missing: {$table}");
        $rows = DB::table($table)->orderBy('id')->get()->map(function ($record) use ($table): array {
            $row = (array) $record;

            if ($table === 'research_events') {
                // Compare the *pre-existing* row in full, unaffected by schema additions.
                unset($row['deduplication_key']);
            }

            return $row;
        })->all();

        $tables[$table] = ['count' => count($rows), 'sha256' => canonicalHash($rows)];
    }

    return [
        'format' => 'r5-legacy-integrity-hashes-v1',
        'tables' => $tables,
        'overall_sha256' => canonicalHash([$tables]),
    ];
}

function seedSynthetic(): void
{
    requireCondition(! Schema::hasColumn('research_events', 'deduplication_key'), 'Fixture must be seeded BEFORE dedup migration.');
    foreach (['users', 'courses', 'research_events', 'experiment_assignments', 'adaptive_algorithm_versions'] as $table) {
        requireCondition(DB::table($table)->count() === 0, "Refusing to seed non-empty table: {$table}");
    }

    $now = '2026-11-01 08:00:00';
    $later = '2026-11-02 10:15:00';
    $stamp = ['created_at' => $now, 'updated_at' => $now];
    DB::transaction(function () use ($now, $later, $stamp): void {
        $roleInstructor = DB::table('roles')->insertGetId(['name' => 'instructor', 'label' => 'Synthetic Teacher', ...$stamp]);
        $roleStudent = DB::table('roles')->insertGetId(['name' => 'student', 'label' => 'Synthetic Student', ...$stamp]);

        $teacher = DB::table('users')->insertGetId([
            'role_id' => $roleInstructor, 'name' => 'Synthetic Instructor',
            'email' => 'historical-teacher@example.invalid', 'password' => 'DISABLED-CI-NO-LOGIN', ...$stamp,
        ]);
        $adaptive = DB::table('users')->insertGetId([
            'role_id' => $roleStudent, 'name' => 'Synthetic Adaptive',
            'email' => 'historical-adaptive@example.invalid', 'password' => 'DISABLED-CI-NO-LOGIN', ...$stamp,
        ]);
        $control = DB::table('users')->insertGetId([
            'role_id' => $roleStudent, 'name' => 'Synthetic Control',
            'email' => 'historical-control@example.invalid', 'password' => 'DISABLED-CI-NO-LOGIN', ...$stamp,
        ]);
        $course = DB::table('courses')->insertGetId([
            'instructor_id' => $teacher, 'title' => 'R5 Synthetic Historical Course',
            'slug' => 'r5-synthetic-historical', 'status' => 'published', ...$stamp,
        ]);
        $objective = DB::table('learning_objectives')->insertGetId([
            'course_id' => $course, 'code' => 'HIST-LO-1', 'title' => 'Historical Research Objective', ...$stamp,
        ]);
        $conceptA = DB::table('learning_concepts')->insertGetId([
            'course_id' => $course, 'learning_objective_id' => $objective,
            'code' => 'HIST-A', 'name' => 'Synthetic Concept A', ...$stamp,
        ]);
        $conceptB = DB::table('learning_concepts')->insertGetId([
            'course_id' => $course, 'learning_objective_id' => $objective,
            'code' => 'HIST-B', 'name' => 'Synthetic Concept B', ...$stamp,
        ]);

        $assignmentA = DB::table('experiment_assignments')->insertGetId([
            'course_id' => $course, 'student_id' => $adaptive,
            'condition' => 'adaptive', 'class_cluster' => 'SYNTH-A',
            'assignment_version' => 'research-v2', 'assigned_by' => $teacher,
            'assigned_at' => $later, ...$stamp,
        ]);
        $assignmentC = DB::table('experiment_assignments')->insertGetId([
            'course_id' => $course, 'student_id' => $control,
            'condition' => 'non_adaptive', 'class_cluster' => 'SYNTH-C',
            'assignment_version' => 'research-control-v2', 'assigned_by' => $teacher,
            'assigned_at' => $later, ...$stamp,
        ]);

        $algorithms = config('adaptive.algorithms', []);
        requireCondition(isset($algorithms['rule-based-v1'], $algorithms['fuzzy-v2']), 'Expected frozen Rule-Based and Fuzzy algorithm definitions.');

        $algorithmIds = [];

        foreach (['rule-based-v1', 'fuzzy-v2'] as $code) {
            $definition = $algorithms[$code];
            $algorithmIds[$code] = DB::table('adaptive_algorithm_versions')->insertGetId([
                'code' => $code, 'name' => $definition['name'],
                'engine_type' => $definition['engine_type'], 'version' => $definition['version'],
                'status' => $definition['status'], 'is_baseline' => $definition['is_baseline'] ? 1 : 0,
                'description' => $definition['description'] ?? null,
                'configuration' => json_encode($definition['configuration'], JSON_THROW_ON_ERROR),
                'activated_at' => $definition['status'] === 'active' ? $now : null,
                ...$stamp,
            ]);
        }

        foreach ([
            [$adaptive, $conceptA, 'rule-based-v1', 'active'],
            [$control, $conceptB, 'fuzzy-v2', 'superseded'],
        ] as [$student, $concept, $code, $status]) {
            DB::table('adaptive_recommendations')->insert([
                'student_id' => $student, 'course_id' => $course,
                'source_concept_id' => $concept, 'target_concept_id' => $concept,
                'algorithm_version_id' => $algorithmIds[$code],
                'action' => 'remediation', 'reason_code' => 'historical_fixture',
                'decision_fingerprint' => hash('sha256', "{$student}:{$course}:{$concept}:{$code}"),
                'status' => $status, 'inputs' => '{"mastery_score":25,"evidence_count":3}',
                'metadata' => '{"historical":true,"nested":{"value":"unchanged"}}',
                'generated_at' => $now, ...$stamp,
            ]);
        }

        $events = [
            // Two legacy milestones with the same semantic identity, retained as-is.
            [$adaptive, $assignmentA, 'material_completed', 'LearningConcept', $conceptA, 'adaptive', 'research-v1', '{"source":"first","notes":["alpha",2]}'],
            [$adaptive, $assignmentA, 'material_completed', 'LearningConcept', $conceptA, 'adaptive', 'research-v1', '{"source":"duplicate-legacy","note":"must-not-delete"}'],
            // Repeatable exposure with independent IDs and timestamps.
            [$adaptive, $assignmentA, 'material_opened', 'LearningConcept', $conceptA, 'adaptive', 'research-v1', '{"source":"view-1"}'],
            [$adaptive, $assignmentA, 'material_opened', 'LearningConcept', $conceptA, 'adaptive', 'research-v1', '{"source":"view-2"}'],
            // Control snapshot deliberately reflects an earlier assignment version.
            [$control, $assignmentC, 'assessment_submitted', 'LearningConcept', $conceptB, 'non_adaptive', 'research-control-v1', '{"score":45,"source":"legacy"}'],
            [$control, $assignmentC, 'material_opened', 'LearningConcept', $conceptB, 'non_adaptive', 'research-control-v1', '{"source":"control-visit"}'],
        ];

        foreach ($events as $index => [$student, $assignment, $type, $sourceType, $sourceId, $condition, $version, $metadata]) {
            DB::table('research_events')->insert([
                'event_id' => sprintf('00000000-0000-4000-8000-%012d', $index + 1),
                'student_id' => $student, 'course_id' => $course,
                'learning_objective_id' => $objective, 'experiment_assignment_id' => $assignment,
                'event_type' => $type, 'source_type' => $sourceType, 'source_id' => $sourceId,
                'treatment_condition' => $condition, 'assignment_version' => $version,
                'occurred_at' => $index % 2 ? $later : $now,
                'metadata' => $metadata, ...$stamp,
            ]);
        }
    });

    requireCondition(DB::table('research_events')->count() === 6, 'Synthetic legacy fixture insertion count mismatch.');
    echo "PASS synthetic historical fixture: 2 assignment, 6 events (including 2 old duplicate milestones), 2 algorithm versions, 2 recommendations.\n";
}

function checkAfterMigration(): void
{
    requireCondition(Schema::hasColumn('research_events', 'deduplication_key'), 'New deduplication column missing after migration.');
    requireCondition(DB::table('research_events')->whereNotNull('deduplication_key')->count() === 0, 'Legacy event key must remain null.');
    requireCondition(DB::table('experiment_assignments')->count() === 2, 'Assignment cardinality changed.');
    requireCondition(DB::table('adaptive_recommendations')->count() === 2, 'Historical recommendation cardinality changed.');
    requireCondition(DB::table('research_events')->count() === 6, 'Historical events were deleted or duplicated.');
    requireCondition(DB::table('research_events')->where('event_type', 'material_completed')->count() === 2, 'Legacy duplicate milestone improperly removed.');

    $course = Course::query()->where('slug', 'r5-synthetic-historical')->sole();
    $adaptive = User::query()->where('email', 'historical-adaptive@example.invalid')->sole();
    $control = User::query()->where('email', 'historical-control@example.invalid')->sole();
    $first = LearningConcept::query()->where('code', 'HIST-A')->sole();
    $second = LearningConcept::query()->where('code', 'HIST-B')->sole();

    $recorder = app(ResearchEventRecorder::class);
    $oldFirst = ResearchEvent::query()->where('event_type', 'material_completed')->orderBy('id')->firstOrFail();
    $reused = $recorder->recordOnce($adaptive, $course, 'material_completed', $first, ['should_not' => 'mutate']);

    requireCondition($oldFirst->id === $reused->id, 'recordOnce did not reuse the earliest historical milestone.');
    requireCondition($reused->metadata === $oldFirst->metadata, 'Historical metadata was overwritten.');
    requireCondition(DB::table('research_events')->count() === 6, 'Legacy recordOnce unexpectedly inserted a new row.');

    $new = $recorder->recordOnce($adaptive, $course, 'assessment_started', $second, ['scenario' => 'new']);
    $repeated = $recorder->recordOnce($adaptive, $course, 'assessment_started', $second, ['scenario' => 'repeated']);
    requireCondition($new->id === $repeated->id && strlen((string) $new->deduplication_key) === 64, 'New recordOnce is not idempotent.');

    // Attempt a distinct UUID using an existing logical uniqueness key.
    $raw = (array) DB::table('research_events')->where('id', $new->id)->first();
    unset($raw['id']);
    $raw['event_id'] = 'ffffffff-ffff-4fff-8fff-ffffffffffff';

    $uniqueRejected = false;

    try {
        DB::table('research_events')->insert($raw);
    } catch (QueryException) {
        $uniqueRejected = true;
    }

    requireCondition($uniqueRejected, 'Database did not enforce deduplication_key UNIQUE.');

    $one = $recorder->record($control, $course, 'material_opened', $second, ['repeatable' => true]);
    $two = $recorder->record($control, $course, 'material_opened', $second, ['repeatable' => true]);
    requireCondition($one->id !== $two->id && $one->deduplication_key === null && $two->deduplication_key === null, 'Repeatable events lost multiplicity.');

    // The stored algorithm definitions must be stable across registry access.
    $oldAlgorithms = DB::table('adaptive_algorithm_versions')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    app(AdaptiveAlgorithmRegistry::class)->syncConfiguredVersions();
    $newAlgorithms = DB::table('adaptive_algorithm_versions')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();

    // Registry may update lifecycle timestamps; computational identity must remain stable.
    foreach ($oldAlgorithms as $index => $original) {
        $current = $newAlgorithms[$index] ?? [];
        foreach (['id', 'code', 'version', 'engine_type', 'configuration'] as $field) {
            requireCondition(($original[$field] ?? null) === ($current[$field] ?? null), "Algorithm {$field} changed silently.");
        }
    }

    requireCondition(DB::table('adaptive_recommendations')->where('student_id', $control->id)->where('status', 'active')->count() === 0, 'Control learner exposed to active adaptive treatment.');
    requireCondition(DB::table('experiment_assignments')->where('student_id', $control->id)->value('condition') === 'non_adaptive', 'Control assignment changed.');
    requireCondition(DB::table('research_events')->count() === 9, 'Unexpected research event cardinality after idempotency and repeatability checks.');

    echo "PASS postmigration: legacy data preserved; semantic duplicate legacy retained; DB uniqueness enforced; repeatable events permitted; treatment and algorithm identity stable.\n";
}

try {
    switch ($mode) {
        case 'seed':
            seedSynthetic();
            break;
        case 'snapshot':
            requireCondition($file !== '', 'Expected output snapshot filename.');
            $data = snapshot();
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
            echo "SNAPSHOT {$database}: ".json_encode($data, JSON_THROW_ON_ERROR)."\n";
            break;
        case 'verify':
            requireCondition($file !== '' && is_readable($file), 'Expected readable baseline snapshot.');
            $baseline = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            $current = snapshot();
            requireCondition($baseline === $current, 'FAILED: historical row checksum or count differs from baseline.');
            requireCondition(! Schema::hasColumn('research_events', 'deduplication_key')
                || DB::table('research_events')->whereNotNull('deduplication_key')->count() === 0,
                'FAILED: legacy event deduplication keys changed.');
            echo "PASS historical row integrity {$database}: all table counts and SHA-256 digests match.\n";
            break;
        case 'postcheck':
            checkAfterMigration();
            break;
        default:
            throw new InvalidArgumentException('Usage: php scripts/research/r5_historical_migration_audit.php [seed|snapshot <file>|verify <file>|postcheck]');
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "FAILED synthetic research audit {$mode}: {$exception->getMessage()}\n");
    exit(1);
}
