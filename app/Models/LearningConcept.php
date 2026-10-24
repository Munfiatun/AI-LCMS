<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningConcept extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'course_id',
        'learning_objective_id',
        'code',
        'name',
        'description',
        'order',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<LearningObjective, $this> */
    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class);
    }

    /** @return BelongsToMany<LearningMaterial, $this> */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(
            LearningMaterial::class,
            'material_concepts',
            'learning_concept_id',
            'learning_material_id'
        )->withPivot('relevance_weight')->withTimestamps();
    }

    /** @return BelongsToMany<Question, $this> */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            Question::class,
            'question_concepts',
            'learning_concept_id',
            'question_id'
        )->withPivot(['weight', 'difficulty_level'])->withTimestamps();
    }

    /** @return BelongsToMany<LearningConcept, $this> */
    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'concept_prerequisites',
            'concept_id',
            'prerequisite_concept_id'
        )->withPivot(['weight', 'order'])->withTimestamps()->orderByPivot('order');
    }

    /** @return BelongsToMany<LearningConcept, $this> */
    public function requiredByConcepts(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'concept_prerequisites',
            'prerequisite_concept_id',
            'concept_id'
        )->withPivot(['weight', 'order'])->withTimestamps()->orderByPivot('order');
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
