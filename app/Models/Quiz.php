<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quiz extends Model
{
    use HasFactory, SoftDeletes;

    public const ASSESSMENT_DIAGNOSTIC = 'diagnostic';

    public const ASSESSMENT_FORMATIVE = 'formative';

    public const ASSESSMENT_SUMMATIVE = 'summative';

    protected $table = 'quizzes';

    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'description',
        'instructions',
        'assessment_type',
        'passing_score',
        'duration_minutes',
        'total_questions',
        'randomize_questions',
        'randomize_options',
        'max_attempts',
        'status',
        'published_at',
    ];

    protected $casts = [
        'randomize_questions' => 'boolean',
        'randomize_options' => 'boolean',
        'published_at' => 'datetime',
    ];

    /** @param Builder<$this> $query */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('quizzes.status', 'published')->where(function (Builder $query): void {
            $query->whereNull('section_id')->orWhereHas('section', fn (Builder $section) => $section
                ->where('status', 'active')->whereColumn('course_sections.course_id', 'quizzes.course_id'));
        });
    }

    public function isDiagnostic(): bool
    {
        return $this->assessment_type === self::ASSESSMENT_DIAGNOSTIC;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'quiz_questions')
            ->withPivot(['id', 'order', 'points'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
