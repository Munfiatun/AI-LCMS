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
use App\Services\Learning\Adaptive\AdaptiveEngine;
use App\Services\Learning\Adaptive\AdaptiveRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Tests\TestCase;

class ExperimentAssignmentRecommendationIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_to_control_hides_previous_recommendations_from_direct_workspace_access(): void
    {
        [$instructor, $student, $course] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');
        $recommendation = app(AdaptiveRecommendationService::class)->generateForCourse($student, $course)->sole();
        $this->assertVisibleRecommendations($student, [$recommendation->id]);

        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v2');

        $this->actingAs($student)
            ->get(route('student.adaptive-recommendations.index', [
                'course_id' => $course->id,
                'student_id' => $student->id,
                'condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
                'recommendation_id' => $recommendation->id,
            ]))
            ->assertOk()
            ->assertViewHas('rows', fn (Collection $rows): bool => $rows->isEmpty())
            ->assertViewHas('courseGroups', fn (Collection $groups): bool => $groups->isEmpty())
            ->assertViewHas('learningPaths', fn (Collection $paths): bool => $paths->isEmpty())
            ->assertViewHas('summary', fn (array $summary): bool => $summary['total'] === 0)
            ->assertDontSee('Variabel R5 untuk Remediasi');

        $this->assertModelExists($recommendation);
        $this->assertDatabaseCount('research_events', 0);
    }

    public function test_control_generation_with_changed_mastery_preserves_history_without_new_decisions(): void
    {
        [$instructor, $student, $course, $concept] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');
        $service = app(AdaptiveRecommendationService::class);
        $service->generateForCourse($student, $course);
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v2');
        $before = AdaptiveRecommendation::query()->orderBy('id')->get()->toArray();
        $this->changeMastery($student, $concept);

        $result = $service->generateForCourse($student, $course, ['trigger' => 'formative_reassessment']);

        $this->assertCount(0, $result);
        $this->assertSame($before, AdaptiveRecommendation::query()->orderBy('id')->get()->toArray());
        $this->assertDatabaseCount('research_events', 0);
    }

    public function test_returning_to_adaptive_requires_regeneration_before_old_recommendations_are_visible(): void
    {
        [$instructor, $student, $course] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');
        $recommendation = app(AdaptiveRecommendationService::class)->generateForCourse($student, $course)->sole();
        $this->assertVisibleRecommendations($student, [$recommendation->id]);
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v2');
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v3');

        $this->assertVisibleRecommendations($student, []);
        $this->assertModelExists($recommendation);
    }

    public function test_same_version_round_trip_still_requires_regeneration(): void
    {
        $this->freezeTime();
        [$instructor, $student, $course] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v1');
        app(AdaptiveRecommendationService::class)->generateForCourse($student, $course);
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'pilot-v1');
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'pilot-v1');

        $this->assertVisibleRecommendations($student, []);
        $this->assertDatabaseCount('adaptive_recommendations', 1);
    }

    public function test_regeneration_after_return_uses_current_mastery_and_keeps_previous_history(): void
    {
        [$instructor, $student, $course, $concept] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');
        $service = app(AdaptiveRecommendationService::class);
        $previous = $service->generateForCourse($student, $course)->sole();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v2');
        $this->changeMastery($student, $concept);
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v3');

        $current = $service->generateForCourse($student, $course)->sole();

        $this->assertNotSame($previous->id, $current->id);
        $this->assertSame('enrichment', $current->action);
        $this->assertSame(90, $current->inputs['mastery_score']);
        $this->assertSame(AdaptiveRecommendation::STATUS_SUPERSEDED, $previous->refresh()->status);
        $this->assertVisibleRecommendations($student, [$current->id]);
        $this->assertSame($current->id, $service->generateForCourse($student, $course)->sole()->id);
        $this->assertDatabaseCount('adaptive_recommendations', 2);
    }

    public function test_regeneration_after_return_with_unchanged_mastery_restores_one_visible_decision(): void
    {
        [$instructor, $student, $course] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');
        $service = app(AdaptiveRecommendationService::class);
        $service->generateForCourse($student, $course);
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v2');
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v3');

        $current = $service->generateForCourse($student, $course)->sole();

        $this->assertSame('remediation', $current->action);
        $this->assertVisibleRecommendations($student, [$current->id]);
        $this->assertSame($current->id, $service->generateForCourse($student, $course)->sole()->id);
        $this->assertSame(1, AdaptiveRecommendation::query()->where('status', AdaptiveRecommendation::STATUS_ACTIVE)->count());
    }

    public function test_resaving_an_adaptive_assignment_does_not_invalidate_current_recommendations(): void
    {
        [$instructor, $student, $course] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');
        $recommendation = app(AdaptiveRecommendationService::class)->generateForCourse($student, $course)->sole();

        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');

        $this->assertVisibleRecommendations($student, [$recommendation->id]);
        $this->assertDatabaseCount('adaptive_recommendations', 1);
    }

    public function test_return_from_legacy_control_assignment_does_not_expose_leftover_active_recommendations(): void
    {
        [$instructor, $student, $course] = $this->context();
        $recommendation = app(AdaptiveRecommendationService::class)->generateForCourse($student, $course)->sole();
        ExperimentAssignment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
            'assignment_version' => 'legacy-v1',
            'assigned_by' => $instructor->id,
            'assigned_at' => now(),
        ]);

        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v2');

        $this->assertVisibleRecommendations($student, []);
        $this->assertModelExists($recommendation);
    }

    public function test_first_adaptive_assignment_preserves_the_documented_unassigned_workflow(): void
    {
        [$instructor, $student, $course] = $this->context();
        $recommendation = app(AdaptiveRecommendationService::class)->generateForCourse($student, $course)->sole();

        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');

        $this->assertVisibleRecommendations($student, [$recommendation->id]);
        $this->assertDatabaseCount('adaptive_recommendations', 1);
    }

    public function test_a_control_assignment_is_scoped_to_one_student_and_course(): void
    {
        [$instructor, $student, $controlCourse] = $this->context();
        [, , $legacyCourse] = $this->context($instructor, $student);
        $otherStudent = $this->user(Role::ROLE_STUDENT);
        $this->enroll($otherStudent, $controlCourse);
        $service = app(AdaptiveRecommendationService::class);

        $service->generateForCourse($student, $controlCourse);
        $legacy = $service->generateForCourse($student, $legacyCourse)->sole();
        $other = $service->generateForCourse($otherStudent, $controlCourse)->sole();
        $this->assign($instructor, $student, $controlCourse, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v1');

        $this->assertCount(0, $service->generateForCourse($student, $controlCourse));
        $this->assertVisibleRecommendations($student, [$legacy->id]);
        $this->assertVisibleRecommendations($otherStudent, [$other->id]);
    }

    public function test_direct_persistence_rejects_a_decision_computed_before_switching_to_control(): void
    {
        [$instructor, $student, $course, $concept] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');
        $decision = app(AdaptiveEngine::class)->decide($student, $concept);
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v2');

        $this->expectException(InvalidArgumentException::class);

        try {
            app(AdaptiveRecommendationService::class)->persistDecision($student, $course, $concept, $decision);
        } finally {
            $this->assertDatabaseCount('adaptive_recommendations', 0);
        }
    }

    public function test_direct_persistence_cannot_reactivate_superseded_history_for_control_student(): void
    {
        [$instructor, $student, $course, $concept] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_ADAPTIVE, 'v1');
        $decision = app(AdaptiveEngine::class)->decide($student, $concept);
        $service = app(AdaptiveRecommendationService::class);
        $service->persistDecision($student, $course, $concept, $decision);
        $this->changeMastery($student, $concept);
        $service->generateForCourse($student, $course);
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v2');
        $before = AdaptiveRecommendation::query()->orderBy('id')->get()->toArray();

        $this->expectException(InvalidArgumentException::class);

        try {
            $service->persistDecision($student, $course, $concept, $decision);
        } finally {
            $this->assertSame($before, AdaptiveRecommendation::query()->orderBy('id')->get()->toArray());
        }
    }

    public function test_control_generation_command_succeeds_without_persisting_recommendations(): void
    {
        [$instructor, $student, $course] = $this->context();
        $this->assign($instructor, $student, $course, ExperimentAssignment::CONDITION_NON_ADAPTIVE, 'v1');

        $this->artisan('research:r3-generate-recommendations', [
            'course' => $course->id,
            'student' => $student->id,
        ])->expectsOutput('0 active recommendations synchronized.')->assertSuccessful();

        $this->assertDatabaseCount('adaptive_recommendations', 0);
    }

    /** @return array{User, User, Course, LearningConcept} */
    private function context(?User $instructor = null, ?User $student = null): array
    {
        $instructor ??= $this->user(Role::ROLE_INSTRUCTOR);
        $student ??= $this->user(Role::ROLE_STUDENT);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
        $this->enroll($student, $course);
        $concept = LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => 'R5-C01',
            'name' => 'Variabel R5 untuk Remediasi',
            'is_active' => true,
        ]);
        LearnerMastery::create([
            'student_id' => $student->id,
            'concept_id' => $concept->id,
            'mastery_score' => 33.33,
            'status' => 'observed',
            'evidence_count' => 3,
            'last_evidence_at' => now(),
        ]);

        return [$instructor, $student, $course, $concept];
    }

    private function user(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)]);

        return User::factory()->create(['role_id' => $role->id, 'is_active' => true]);
    }

    private function enroll(User $student, Course $course): void
    {
        CourseEnrollment::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'status' => 'active',
            'progress_percentage' => 0,
        ]);
    }

    private function assign(User $instructor, User $student, Course $course, string $condition, string $version): void
    {
        $this->actingAs($instructor)->put(route('instructor.courses.research.assignment', $course), [
            'student_id' => $student->id,
            'condition' => $condition,
            'assignment_version' => $version,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('experiment_assignments', [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'condition' => $condition,
            'assignment_version' => $version,
        ]);
    }

    /** @param list<int> $ids */
    private function assertVisibleRecommendations(User $student, array $ids): void
    {
        $response = $this->actingAs($student)->get(route('student.adaptive-recommendations.index'))->assertOk();
        $actualIds = $response->viewData('rows')->pluck('recommendation.id')->all();
        sort($actualIds);
        sort($ids);

        $this->assertSame($ids, $actualIds);
    }

    private function changeMastery(User $student, LearningConcept $concept): void
    {
        LearnerMastery::query()->where('student_id', $student->id)->where('concept_id', $concept->id)->update([
            'mastery_score' => 90,
            'evidence_count' => 4,
            'last_evidence_at' => now()->addMinute(),
        ]);
    }
}
