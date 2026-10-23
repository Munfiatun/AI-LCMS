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
        $assignment = ExperimentAssignment::query()
            ->where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();

        return ResearchEvent::create([
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
        ]);
    }
}
