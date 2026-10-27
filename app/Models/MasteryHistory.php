<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasteryHistory extends Model
{
    use HasFactory;

    protected $table = 'mastery_history';

    /** @var list<string> */
    protected $fillable = [
        'student_id',
        'concept_id',
        'before_score',
        'after_score',
        'evidence_count',
        'evidence_type',
        'evidence_id',
        'model_version',
        'evidence_payload',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'before_score' => 'float',
            'after_score' => 'float',
            'evidence_count' => 'integer',
            'evidence_id' => 'integer',
            'evidence_payload' => 'array',
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
