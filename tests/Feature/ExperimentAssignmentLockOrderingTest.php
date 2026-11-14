<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\ExperimentAssignment;
use App\Models\LearnerMastery;
use App\Models\LearningConcept;
use App\Models\Role;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveDecision;
use App\Services\Learning\Adaptive\AdaptiveEngine;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

/**
 * Sequential and deliberately interleaved regression scenarios.
 *
 * SQLite feature tests check observable behavior, not row-lock semantics
 * under real concurrent PostgreSQL/MySQL connections. See the R5.1 protocol.
 */
class ExperimentAssignmentLockOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_control_assignment_supersedes_legacy_recommendation_and_prevents_new_generation(): void
    {
        [$instructor, $student, $course] = $this->context();
        $service = app(AdaptiveRecommendationService::class);
        $previous = $service->generateForCourse($student, $course)->sole();

        $this->assertDatabaseCount('experiment_assignments', 0);
        $this->assertSame(AdaptiveRecommendation::STATUS_ACTIVE, $previous->status);

        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'pilot-v1');

        $this->assertDatabaseCount('experiment_assignments', 1);
        $this->assertDatabaseHas('experiment_assignments', [
            'course_id' => $course->id,
            'student_id' => $student->id,
            'condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
            'assignment_version' => 'pilot-v1',
        ]);
        $this->assertSame(AdaptiveRecommendation::STATUS_SUPERSEDED, $previous->refresh()->status);
        $this->assertCount(0, $service->generateForCourse($student, $course));
        $this->assertDatabaseCount('adaptive_recommendations', 1);
    }

    public function test_switch_during_decision_computation_is_rechecked_before_persistence(): void
    {
        [$instructor, $student, $course] = $this->context();

        $engine = Mockery::mock(AdaptiveEngine::class);
        $engine->shouldReceive('decide')->once()->andReturnUsing(
            function (User $learner, LearningConcept $concept) use ($instructor, $course, $student): AdaptiveDecision {
                $this->assertSame($student->id, $learner->id);

                // Simulate a switch to control AFTER the generation entry guard
                // but BEFORE persistDecision checks the assignment.
                $this->assign(
                    $instructor,
                    $student,
                    $course,
                    ExperimentAssignment::CONDITION_NON_ADAPTIVE,
                    'pilot-v2',
                );

                return new AdaptiveDecision(
                    action: 'remediation',
                    targetConceptId: $concept->id,
                    reasonCode: 'mastery_low',
                    algorithmCode: 'rule-based-v1',
                    algorithmVersion: '1.0.0',
                    inputs: ['mastery_score' => 20.0, 'evidence_count' => 3],
                );
            }
        );
        $this->app->instance(AdaptiveEngine::class, $engine);

        try {
            app(AdaptiveRecommendationService::class)->generateForCourse($student, $course);
            $this->fail('A stale decision must not be persisted after switching to control.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('non-adaptive assignment', $exception->getMessage());
        }

        $this->assertDatabaseCount('adaptive_recommendations', 0);
        $this->assertDatabaseHas('experiment_assignments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
            'assignment_version' => 'pilot-v2',
        ]);
    }

    public function test_repeated_assignment_updates_keep_a_single_row_without_touching_other_courses(): void
    {
        [$instructor, $student, $course] = $this->context();
        $otherCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
        CourseEnrollment::create([
            'student_id' => $student->id,
            'course_id' => $otherCourse->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);

        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v1');
        $this->assign($instructor, $student, $otherCourse, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v1');
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'pilot-v2');

        $this->assertDatabaseCount('experiment_assignments', 2);
        $this->assertDatabaseHas('experiment_assignments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
            'assignment_version' => 'pilot-v2',
        ]);
        $this->assertDatabaseHas('experiment_assignments', [
            'student_id' => $student->id,
            'course_id' => $otherCourse->id,
            'condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
            'assignment_version' => 'pilot-v1',
        ]);
    }

    /** @return array{User, User, Course} */
    private function context(): array
    {
        $instructorRole = Role::firstOrCreate(['name' => Role::ROLE_INSTRUCTOR], ['label' => 'Instructor']);
        $studentRole = Role::firstOrCreate(['name' => Role::ROLE_STUDENT], ['label' => 'Student']);
        $instructor = User::factory()->create(['role_id' => $instructorRole->id, 'is_active' => true]);
        $student = User::factory()->create(['role_id' => $studentRole->id, 'is_active' => true]);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        CourseEnrollment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'R5-LOCK-01',
            'name' => 'Uji konsistensi assignment',
            'order' => 1,
            'is_active' => true,
        ]);
        LearnerMastery::create([
            'student_id' => $student->id,
            'concept_id' => $concept->id,
            'mastery_score' => 20.0,
            'status' => 'observed',
            'evidence_count' => 3,
            'last_evidence_at' => now(),
        ]);

        return [$instructor, $student, $course];
    }

    private function assign(User $instructor, User $student, Course $course, string $condition, string $version): void
    {
        $this->actingAs($instructor)
            ->put(route('instructor.courses.research.assignment', $course), [
                'student_id' => $student->id,
                'condition' => $condition,
                'assignment_version' => $version,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }
}
