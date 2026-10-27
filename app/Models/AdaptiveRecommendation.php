<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdaptiveRecommendation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'student_id',
        'course_id',
        'source_concept_id',
        'target_concept_id',
        'algorithm_version_id',
        'action',
        'reason_code',
        'decision_fingerprint',
        'status',
        'inputs',
        'metadata',
        'generated_at',
        'superseded_at',
    ];

    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'metadata' => 'array',
            'generated_at' => 'datetime',
            'superseded_at' => 'datetime',
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

    public function sourceConcept(): BelongsTo
    {
        return $this->belongsTo(LearningConcept::class, 'source_concept_id');
    }

    public function targetConcept(): BelongsTo
    {
        return $this->belongsTo(LearningConcept::class, 'target_concept_id');
    }

    public function algorithmVersion(): BelongsTo
    {
        return $this->belongsTo(AdaptiveAlgorithmVersion::class, 'algorithm_version_id');
    }
}
