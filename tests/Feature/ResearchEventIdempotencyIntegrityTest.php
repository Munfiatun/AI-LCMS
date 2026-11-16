<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\ExperimentAssignment;
use App\Models\LearningMaterial;
use App\Models\ResearchEvent;
use App\Models\User;
use App\Services\ResearchEventRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResearchEventIdempotencyIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_once_only_milestone_is_atomic_and_preserves_original_assignment_snapshot(): void
    {
        [$student, $course, $material] = $this->context();
        $assignment = ExperimentAssignment::create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'condition' => ExperimentAssignment::CONDITION_ADAPTIVE,
            'assignment_version' => 'pilot-v1',
            'assigned_at' => now(),
        ]);
        $recorder = app(ResearchEventRecorder::class);

        $first = $recorder->recordOnce(
            $student, $course, ResearchEvent::MATERIAL_COMPLETED, $material, ['source' => 'first']
        );
        $originalEventId = $first->event_id;
        $originalOccurredAt = $first->occurred_at;
        $this->assertSame(64, strlen($first->deduplication_key));

        $assignment->update([
            'condition' => ExperimentAssignment::CONDITION_NON_ADAPTIVE,
            'assignment_version' => 'pilot-v2',
            'assigned_at' => now()->addDay(),
        ]);

        $second = $recorder->recordOnce(
            $student, $course, ResearchEvent::MATERIAL_COMPLETED, $material, ['source' => 'later']
        );

        $this->assertSame($first->id, $second->id);
        $this->assertSame($originalEventId, $second->event_id);
        $this->assertEquals($originalOccurredAt, $second->occurred_at);
        $this->assertSame(ExperimentAssignment::CONDITION_ADAPTIVE, $second->treatment_condition);
        $this->assertSame('pilot-v1', $second->assignment_version);
        $this->assertSame(['source' => 'first'], $second->metadata);
        $this->assertDatabaseCount('research_events', 1);
    }

    public function test_repeatable_events_keep_null_key_and_can_be_recorded_more_than_once(): void
    {
        [$student, $course, $material] = $this->context();
        $recorder = app(ResearchEventRecorder::class);

        $first = $recorder->record($student, $course, ResearchEvent::MATERIAL_OPENED, $material);
        $second = $recorder->record($student, $course, ResearchEvent::MATERIAL_OPENED, $material);

        $this->assertNotSame($first->id, $second->id);
        $this->assertNull($first->deduplication_key);
        $this->assertNull($second->deduplication_key);
        $this->assertDatabaseCount('research_events', 2);
    }

    public function test_existing_legacy_milestone_is_reused_without_rewriting_it(): void
    {
        [$student, $course, $material] = $this->context();
        $recorder = app(ResearchEventRecorder::class);

        // Simulate an event stored before the deduplication column existed.
        $original = $recorder->record(
            $student, $course, ResearchEvent::MATERIAL_COMPLETED, $material, ['legacy' => true]
        );
        $same = $recorder->recordOnce(
            $student, $course, ResearchEvent::MATERIAL_COMPLETED, $material, ['legacy' => false]
        );

        $this->assertSame($original->id, $same->id);
        $this->assertNull($same->deduplication_key);
        $this->assertSame(['legacy' => true], $same->metadata);
        $this->assertDatabaseCount('research_events', 1);
    }

    public function test_once_only_events_remain_distinct_between_students_and_sources(): void
    {
        [$student, $course, $material] = $this->context();
        $otherStudent = User::factory()->create();
        $otherMaterial = LearningMaterial::factory()->create(['section_id' => $material->section_id]);
        $recorder = app(ResearchEventRecorder::class);

        $one = $recorder->recordOnce($student, $course, ResearchEvent::MATERIAL_COMPLETED, $material);
        $two = $recorder->recordOnce($otherStudent, $course, ResearchEvent::MATERIAL_COMPLETED, $material);
        $three = $recorder->recordOnce($student, $course, ResearchEvent::MATERIAL_COMPLETED, $otherMaterial);

        $this->assertNotSame($one->deduplication_key, $two->deduplication_key);
        $this->assertNotSame($one->deduplication_key, $three->deduplication_key);
        $this->assertDatabaseCount('research_events', 3);
    }

    /** @return array{User, Course, LearningMaterial} */
    private function context(): array
    {
        $instructor = User::factory()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $section = CourseSection::factory()->create(['course_id' => $course->id]);
        $material = LearningMaterial::factory()->create(['section_id' => $section->id]);

        return [$student, $course, $material];
    }
}
