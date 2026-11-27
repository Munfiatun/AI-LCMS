<?php

declare(strict_types=1);

namespace Tests\Research\AIComputational\Support;

use App\Models\Course;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Builds the frozen Phase 1 graph contract. Never loads a real user or course.
 * The caller rolls back every case; both engines receive the same fixture.
 */
final class ExperimentFixture
{
    public static function assertIsolated(): void
    {
        if (! app()->environment('testing') || app()->configurationIsCached()
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || ! in_array(config('database.connections.sqlite.url'), [null, ''], true)) {
            throw new RuntimeException('Research fixture requires uncached testing + SQLite :memory: + empty DB URL.');
        }

        $db = DB::connection();
        if ($db->getDriverName() !== 'sqlite' || $db->getDatabaseName() !== ':memory:') {
            throw new RuntimeException('Unexpected resolved DB connection.');
        }
        $attached = $db->select('PRAGMA database_list');
        foreach ($attached as $database) {
            if ((string) $database->file !== '' || ! in_array($database->name, ['main', 'temp'], true)) {
                throw new RuntimeException('File-backed or attached DB is forbidden.');
            }
        }
    }

    /** @return array{student:User, course:Course, concept:LearningConcept, targets:array<int,string>} */
    public static function build(array $row, User $instructor, User $student): array
    {
        self::assertIsolated();
        if (DB::transactionLevel() < 1) {
            throw new RuntimeException('Fixture requires an outer case transaction.');
        }
        $course = Course::query()->create([
            'instructor_id' => $instructor->id,
            'title' => 'Synthetic computational experiment',
            'slug' => 'research-computational-case',
            'description' => 'Synthetic fixture; not a real course.',
            'status' => Course::STATUS_PUBLISHED,
        ]);
        $targets = [];
        $create = static function (string $code, int $order) use ($course, &$targets): LearningConcept {
            $concept = LearningConcept::query()->create([
                'course_id' => $course->id, 'learning_objective_id' => null,
                'code' => $code, 'name' => $code, 'order' => $order, 'is_active' => true,
            ]);
            $targets[$concept->id] = $code;
            return $concept;
        };
        $mastery = static function (LearningConcept $concept, string|float $score, string|int $evidence) use ($student): void {
            LearnerMastery::query()->create([
                'student_id' => $student->id, 'concept_id' => $concept->id,
                'mastery_score' => (float) $score, 'evidence_count' => (int) $evidence,
                'status' => (int) $evidence > 0 ? 'observed' : 'insufficient_evidence',
                'last_evidence_at' => '2026-01-01 00:00:00',
            ]);
        };

        // Explicit insertion order P1, P2, C, Q1, Q2, N also fixes ID tie-breaking.
        $prerequisites = [];
        foreach ([1, 2] as $i) {
            $state = $row["prereq{$i}_state"];
            if ($state === 'absent') {
                continue;
            }
            $p = $create('P'.$i, 10 * $i);
            $prerequisites[$i] = $p;
            if ($state === 'observed') {
                $mastery($p, $row["prereq{$i}_mastery"], $row["prereq{$i}_evidence"]);
            }
            // 'missing' has an edge and no mastery row.
        }

        $current = $create('C', 30);
        $mastery($current, $row['mastery_score'], $row['evidence_count']);
        foreach ($prerequisites as $i => $p) {
            $current->prerequisites()->attach($p->id, ['weight' => 1.0, 'order' => $i]);
        }

        $next = $row['next_context'];
        if ($next !== 'none') {
            $q1 = $create('Q1', 1);
            $mastery($q1, $next === 'blocked' ? 60.0 : 90.0, 3);
            $q2 = null;
            if (in_array($next, ['dual_ready', 'dual_blocked'], true)) {
                $q2 = $create('Q2', 2);
                $mastery($q2, $next === 'dual_blocked' ? 60.0 : 90.0, 3);
            }
            $n = $create('N', 40);
            $n->prerequisites()->attach($q1->id, ['weight' => 1.0, 'order' => 1]);
            if ($q2 !== null) {
                $n->prerequisites()->attach($q2->id, ['weight' => 1.0, 'order' => 2]);
            }
        }

        return ['student' => $student, 'course' => $course, 'concept' => $current, 'targets' => $targets];
    }
}
