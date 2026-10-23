<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResearchEvent extends Model
{
    public const MATERIAL_OPENED = 'material_opened';

    public const MATERIAL_COMPLETED = 'material_completed';

    public const SLIDEBOOK_OPENED = 'slidebook_opened';

    public const ASSESSMENT_STARTED = 'assessment_started';

    public const ASSESSMENT_SUBMITTED = 'assessment_submitted';

    protected $fillable = [
        'event_id',
        'student_id',
        'course_id',
        'learning_objective_id',
        'experiment_assignment_id',
        'event_type',
        'source_type',
        'source_id',
        'treatment_condition',
        'assignment_version',
        'occurred_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class);
    }

    public function experimentAssignment(): BelongsTo
    {
        return $this->belongsTo(ExperimentAssignment::class);
    }
}
