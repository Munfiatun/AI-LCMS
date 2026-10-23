<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperimentAssignment extends Model
{
    public const CONDITION_ADAPTIVE = 'adaptive';

    public const CONDITION_NON_ADAPTIVE = 'non_adaptive';

    protected $fillable = [
        'course_id',
        'student_id',
        'condition',
        'class_cluster',
        'assignment_version',
        'assigned_by',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function researchEvents(): HasMany
    {
        return $this->hasMany(ResearchEvent::class);
    }
}
