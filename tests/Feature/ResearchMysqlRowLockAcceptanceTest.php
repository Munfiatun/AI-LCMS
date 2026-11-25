<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\ExperimentAssignment;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveDecision;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * MySQL-only acceptance checks on two distinct real database connections.
 *
 * This suite is intentionally excluded from SQLite verification. It uses a
 * disposable CI database, not student/research data. It verifies lock wait and
 * post-commit treatment enforcement, not a field-study or benchmark.
 */
class ResearchMysqlRowLockAcceptanceTest extends TestCase
{
    private string $previousDefaultConnection = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultConnection = (string) config('database.default');

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Two-connection row-lock acceptance is MySQL-only.');
        }

        config()->set('database.connections.r5_mysql_peer', config('database.connections.mysql'));
        DB::purge('r5_mysql_peer');
        DB::connection('r5_mysql_peer')->statement('SET SESSION innodb_lock_wait_timeout = 1');
    }

    protected function tearDown(): void
    {
        config()->set('database.default', $this->previousDefaultConnection);

        if (DB::getDefaultConnection() === 'mysql') {
            DB::disconnect('r5_mysql_peer');
        }

        parent::tearDown();
    }

    public function test_first_recommendation_write_waits_for_existing_student_lock(): void
    {
        [$student, $course, $concept] = $this->context();
        $connectionA = DB::connection('mysql');

        $connectionA->beginTransaction();

        try {
            $this->assertNotNull(
                $connectionA->table('users')->where('id', $student->id)->lockForUpdate()->first()
            );

            config()->set('database.default', 'r5_mysql_peer');

            try {
                app(AdaptiveRecommendationService::class)->persistDecision(
                    $student,
                    $course,
                    $concept,
                    $this->decision($concept),
                );
                $this->fail('Connection B must wait for the student lock held by A.');
            } catch (QueryException $exception) {
                $this->assertSame(1205, (int) ($exception->errorInfo[1] ?? 0));
            } finally {
                config()->set('database.default', 'mysql');
            }
        } finally {
            $connectionA->rollBack();
        }

        config()->set('database.default', 'r5_mysql_peer');

        try {
            $recommendation = app(AdaptiveRecommendationService::class)->persistDecision(
                $student,
                $course,
                $concept,
                $this->decision($concept),
            );
            $this->assertSame(AdaptiveRecommendation::STATUS_ACTIVE, $recommendation->status);
            $this->assertSame(1, AdaptiveRecommendation::query()
                ->where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->count());
        } finally {
            config()->set('database.default', 'mysql');
        }
    }

    public function test_control_assignment_committed_under_student_lock_blocks_stale_adaptive_write(): void
    {
        [$student, $course, $concept, $instructor] = $this->context();
        $connectionA = DB::connection('mysql');

        $connectionA->beginTransaction();

        try {
            $connectionA->table('users')->where('id', $student->id)->lockForUpdate()->first();

            ExperimentAssignment::create([
                'student_id' => $student->id,
                'course_id' => $course->id,
                'condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
                'assignment_version' => 'ci-staging-v1',
                'assigned_by' => $instructor->id,
                'assigned_at' => now(),
            ]);

            config()->set('database.default', 'r5_mysql_peer');

            try {
                app(AdaptiveRecommendationService::class)->persistDecision(
                    $student,
                    $course,
                    $concept,
                    $this->decision($concept),
                );
                $this->fail('Connection B must not skip the uncommitted student lock.');
            } catch (QueryException $exception) {
                $this->assertSame(1205, (int) ($exception->errorInfo[1] ?? 0));
            } finally {
                config()->set('database.default', 'mysql');
            }

            $connectionA->commit();
        } finally {
            if ($connectionA->transactionLevel() > 0) {
                $connectionA->rollBack();
            }
        }

        config()->set('database.default', 'r5_mysql_peer');

        try {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage('non-adaptive assignment');

            app(AdaptiveRecommendationService::class)->persistDecision(
                $student,
                $course,
                $concept,
                $this->decision($concept),
            );
        } finally {
            config()->set('database.default', 'mysql');
            $this->assertSame(0, AdaptiveRecommendation::query()
                ->where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->where('status', AdaptiveRecommendation::STATUS_ACTIVE)
                ->count());
        }
    }

    /** @return array{User, Course, LearningConcept, User} */
    private function context(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();

        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'R5-MYSQL-'.uniqid(),
        ]);

        return [$student, $course, $concept, $instructor];
    }

    private function decision(LearningConcept $concept): AdaptiveDecision
    {
        return new AdaptiveDecision(
            action: 'remediation',
            targetConceptId: $concept->id,
            reasonCode: 'mastery_low',
            algorithmCode: 'rule-based-v1',
            algorithmVersion: '1.0.0',
            inputs: ['mastery_score' => 20.0, 'evidence_count' => 3],
        );
    }
}
