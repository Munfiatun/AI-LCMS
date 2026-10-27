<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearnerMastery extends Model
{
    use HasFactory;

    protected $table = 'learner_mastery';

    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'concept_id',
        'mastery_score',
        'status',
        'evidence_count',
        'last_evidence_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'mastery_score' => 'float',
            'evidence_count' => 'integer',
            'last_evidence_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<LearningConcept, $this> */
    public function concept(): BelongsTo
    {
        return $this->belongsTo(LearningConcept::class, 'concept_id');
    }
}
