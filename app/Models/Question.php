<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    use HasFactory;

    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    public const TYPE_TRUE_FALSE = 'true_false';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEW = 'review';

    public const STATUS_APPROVED = 'approved';

    public const SOURCE_EXPLICIT = 'explicit';

    public const SOURCE_INFERRED = 'inferred';

    public const SOURCE_MANUAL = 'manual';

    public const DIFFICULTY_EASY = 'easy';

    public const DIFFICULTY_MEDIUM = 'medium';

    public const DIFFICULTY_HARD = 'hard';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_bank_id',
        'question_text',
        'type',
        'topic',
        'source_slide_number',
        'source_excerpt',
        'difficulty',
        'explanation',
        'points',
        'order',
        'needs_review',
        'answer_source',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_slide_number' => 'integer',
            'points' => 'integer',
            'order' => 'integer',
            'needs_review' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<QuestionBank, $this>
     */
    public function questionBank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    /** @return BelongsToMany<LearningObjective, $this> */
    public function learningObjectives(): BelongsToMany
    {
        return $this->belongsToMany(
            LearningObjective::class,
            'learning_objective_question'
        )->withTimestamps()->orderBy('learning_objectives.order')->orderBy('learning_objectives.code');
    }

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class, 'question_id')->orderBy('order');
    }

    /**
     * @return HasOne<QuestionOption, $this>
     */
    public function correctOption(): HasOne
    {
        return $this->hasOne(QuestionOption::class, 'question_id')->where('is_correct', true);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED)->where('needs_review', false);
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeNeedsReview(Builder $query): void
    {
        $query->where('needs_review', true);
    }
}
