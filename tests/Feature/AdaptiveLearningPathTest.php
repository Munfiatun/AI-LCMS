<?php

namespace Tests\Feature;

use App\Models\AdaptiveRecommendation;
use App\Models\Course;
use App\Models\LearningConcept;
use App\Models\User;
use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use App\Services\Learning\Adaptive\AdaptiveLearningPathService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AdaptiveLearningPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_collapses_duplicate_targets_and_orders_blocking_targets_deterministically(): void
    {
        [$student, $course] = $this->context();
        $c01 = $this->concept($course, 'C01', 1);
        $c02 = $this->concept($course, 'C02', 2);
        $c03 = $this->concept($course, 'C03', 3);
        $c04 = $this->concept($course, 'C04', 4);

        $rows = collect([
            $this->row($this->recommendation($student, $course, $c01, $c01, 'remediation', 'mastery_low')),
            $this->row($this->recommendation($student, $course, $c02, $c01, 'prerequisite_remediation', 'prerequisite_below_threshold')),
            $this->row($this->recommendation($student, $course, $c03, $c01, 'prerequisite_remediation', 'prerequisite_below_threshold')),
            $this->row($this->recommendation($student, $course, $c04, $c03, 'prerequisite_remediation', 'prerequisite_below_threshold')),
        ]);

        $path = app(AdaptiveLearningPathService::class)->build($rows)->first();

        $this->assertSame(4, $path['recommendation_count']);
        $this->assertSame(2, $path['unique_target_count']);
        $this->assertSame('C01', $path['current_step']['target_concept']->code);
        $this->assertSame(3, $path['current_step']['support_count']);
        $this->assertSame('prerequisite_remediation', $path['current_step']['representative_row']['recommendation']->action);
        $this->assertSame('current', $path['current_step']['status']);
        $this->assertSame('C03', $path['queued_steps']->first()['target_concept']->code);
        $this->assertSame('queued', $path['queued_steps']->first()['status']);
    }

    public function test_insufficient_evidence_is_prioritized_before_other_adaptive_actions(): void
    {
        [$student, $course] = $this->context();
        $c01 = $this->concept($course, 'C01', 1);
        $c03 = $this->concept($course, 'C03', 3);

        $rows = collect([
            $this->row($this->recommendation($student, $course, $c01, $c01, 'remediation', 'mastery_low')),
            $this->row($this->recommendation($student, $course, $c03, $c03, 'additional_diagnostic', 'current_evidence_insufficient')),
        ]);

        $path = app(AdaptiveLearningPathService::class)->build($rows)->first();

        $this->assertSame('C03', $path['current_step']['target_concept']->code);
        $this->assertSame('additional_diagnostic', $path['current_step']['representative_row']['recommendation']->action);
        $this->assertSame(10, $path['current_step']['priority']);
        $this->assertSame('C01', $path['queued_steps']->first()['target_concept']->code);
    }

    public function test_learning_path_is_reproducible_for_the_same_recommendation_state(): void
    {
        [$student, $course] = $this->context();
        $c01 = $this->concept($course, 'C01', 1);
        $c02 = $this->concept($course, 'C02', 2);
        $c03 = $this->concept($course, 'C03', 3);

        $rows = collect([
            $this->row($this->recommendation($student, $course, $c03, $c02, 'prerequisite_remediation', 'prerequisite_below_threshold')),
            $this->row($this->recommendation($student, $course, $c02, $c01, 'prerequisite_remediation', 'prerequisite_below_threshold')),
            $this->row($this->recommendation($student, $course, $c01, $c01, 'remediation', 'mastery_low')),
        ]);

        $service = app(AdaptiveLearningPathService::class);
        $first = $this->signature($service->build($rows));
        $second = $this->signature($service->build($rows->shuffle()));

        $this->assertSame($first, $second);
        $this->assertSame([
            ['position' => 1, 'status' => 'current', 'target' => 'C01', 'support' => 2],
            ['position' => 2, 'status' => 'queued', 'target' => 'C02', 'support' => 1],
        ], $first);
    }

    /** @return array{User, Course} */
    private function context(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => Course::STATUS_PUBLISHED,
        ]);

        return [$student, $course];
    }

    private function concept(Course $course, string $code, int $order): LearningConcept
    {
        return LearningConcept::factory()->create([
            'course_id' => $course->id,
            'code' => $code,
            'name' => $code,
            'order' => $order,
            'is_active' => true,
        ]);
    }

    private function recommendation(
        User $student,
        Course $course,
        LearningConcept $source,
        LearningConcept $target,
        string $action,
        string $reason,
    ): AdaptiveRecommendation {
        $baseline = app(AdaptiveAlgorithmRegistry::class)->baseline();

        $recommendation = AdaptiveRecommendation::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'source_concept_id' => $source->id,
            'target_concept_id' => $target->id,
            'algorithm_version_id' => $baseline->id,
            'action' => $action,
            'reason_code' => $reason,
            'decision_fingerprint' => hash('sha256', implode('|', [
                $student->id,
                $course->id,
                $source->id,
                $target->id,
                $action,
                microtime(true),
            ])),
            'status' => AdaptiveRecommendation::STATUS_ACTIVE,
            'inputs' => [],
            'metadata' => [],
            'generated_at' => now(),
        ]);

        return $recommendation->load(['course', 'sourceConcept', 'targetConcept']);
    }

    /** @return array{recommendation: AdaptiveRecommendation} */
    private function row(AdaptiveRecommendation $recommendation): array
    {
        return ['recommendation' => $recommendation];
    }

    /**
     * @param Collection<int, array<string, mixed>> $paths
     * @return list<array{position:int, status:string, target:string, support:int}>
     */
    private function signature(Collection $paths): array
    {
        $path = $paths->first();

        return $path['steps']->map(fn (array $step): array => [
            'position' => $step['position'],
            'status' => $step['status'],
            'target' => $step['target_concept']->code,
            'support' => $step['support_count'],
        ])->all();
    }
}
