<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    /** @var list<string> */
    protected $fillable = [
        'instructor_id',
        'category_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'status',
        'published_at',
        'enrollment_code',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Course $course): void {
            if (empty($course->slug)) {
                $baseSlug = Str::slug($course->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $course->slug = $slug;
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return HasMany<CourseSection, $this> */
    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class)->orderBy('order');
    }

    /** @return HasManyThrough<LearningMaterial, CourseSection, $this> */
    public function materials(): HasManyThrough
    {
        return $this->hasManyThrough(LearningMaterial::class, CourseSection::class, 'course_id', 'section_id');
    }

    /** @return HasMany<LearningObjective, $this> */
    public function learningObjectives(): HasMany
    {
        return $this->hasMany(LearningObjective::class)->orderBy('order')->orderBy('code');
    }

    /** @return HasMany<QuestionBank, $this> */
    public function questionBanks(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    /** @param Builder<$this> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    /** @return HasMany<Quiz, $this> */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /** @return HasManyThrough<QuizAttempt, Quiz, $this> */
    public function quizAttempts(): HasManyThrough
    {
        return $this->hasManyThrough(
            QuizAttempt::class,
            Quiz::class,
            'course_id',
            'quiz_id',
            'id',
            'id'
        );
    }

    /** @return HasMany<CourseEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function generateEnrollmentCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (static::where('enrollment_code', $code)->exists());

        $this->update(['enrollment_code' => $code]);

        return $code;
    }
}
