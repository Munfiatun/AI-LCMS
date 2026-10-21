<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LearningObjective extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'course_id',
        'code',
        'title',
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

    /** @return BelongsToMany<LearningMaterial, $this> */
    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(
            LearningMaterial::class,
            'learning_material_learning_objective'
        )->withTimestamps();
    }

    /** @return BelongsToMany<Question, $this> */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            Question::class,
            'learning_objective_question'
        )->withTimestamps();
    }

    /** @param Builder<$this> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
