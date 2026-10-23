<?php

namespace App\Services;

use App\Models\Course;
use App\Models\ExperimentAssignment;
use App\Models\ResearchEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ResearchEventRecorder
{
    /**
     * Record a research event with the current experiment-assignment snapshot.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        User $student,
        Course $course,
        string $eventType,
        ?Model $source = null,
        array $metadata = [],
        ?int $learningObjectiveId = null
    ): ResearchEvent {
        $assignment = $this->assignmentFor($student, $course);

        return ResearchEvent::create($this->payload(
            $student,
            $course,
            $eventType,
            $source,
            $metadata,
            $learningObjectiveId,
            $assignment
        ));
    }

    /**
     * Record a milestone only once for the same student, course, event type and source.
     * Useful for semantic transitions such as assessment_started/completed.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordOnce(
        User $student,
        Course $course,
        string $eventType,
        Model $source,
        array $metadata = [],
        ?int $learningObjectiveId = null
    ): ResearchEvent {
        $sourceType = class_basename($source);

        $existing = ResearchEvent::query()
            ->where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->where('event_type', $eventType)
            ->where('source_type', $sourceType)
            ->where('source_id', $source->getKey())
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->record($student, $course, $eventType, $source, $metadata, $learningObjectiveId);
    }

    private function assignmentFor(User $student, Course $course): ?ExperimentAssignment
    {
        return ExperimentAssignment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function payload(
        User $student,
        Course $course,
        string $eventType,
        ?Model $source,
        array $metadata,
        ?int $learningObjectiveId,
        ?ExperimentAssignment $assignment
    ): array {
        return [
            'event_id' => (string) Str::uuid(),
            'student_id' => $student->id,
            'course_id' => $course->id,
            'learning_objective_id' => $learningObjectiveId,
            'experiment_assignment_id' => $assignment?->id,
            'event_type' => $eventType,
            'source_type' => $source ? class_basename($source) : null,
            'source_id' => $source?->getKey(),
            'treatment_condition' => $assignment?->condition,
            'assignment_version' => $assignment?->assignment_version,
            'occurred_at' => now(),
            'metadata' => $metadata === [] ? null : $metadata,
        ];
    }
}
