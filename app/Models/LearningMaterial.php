<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LearningMaterial extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_REVIEW = 'review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'section_id',
        'title',
        'slug',
        'description',
        'content',
        'duration_minutes',
        'order',
        'status',
        'published_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function progress()
    {
        return $this->hasMany(MaterialProgress::class);
    }

    protected static function booted(): void
    {
        static::creating(function (LearningMaterial $material): void {
            if (empty($material->slug)) {
                $baseSlug = Str::slug($material->title);
                $slug = $baseSlug;
                $counter = 1;
                while (static::where('section_id', $material->section_id)->where('slug', $slug)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $material->slug = $slug;
            }
        });
    }

    /**
     * @return BelongsTo<CourseSection, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'section_id');
    }

    /**
     * @return HasMany<MaterialDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(MaterialDocument::class, 'material_id');
    }

    /** @return BelongsToMany<LearningObjective, $this> */
    public function learningObjectives(): BelongsToMany
    {
        return $this->belongsToMany(
            LearningObjective::class,
            'learning_material_learning_objective'
        )->withTimestamps()->orderBy('learning_objectives.order')->orderBy('learning_objectives.code');
    }

    /**
     * @return HasMany<Slidebook, $this>
     */
    public function slidebooks(): HasMany
    {
        return $this->hasMany(Slidebook::class, 'material_id');
    }

    /**
     * @return HasOne<Slidebook, $this>
     */
    public function slidebook(): HasOne
    {
        return $this->hasOne(Slidebook::class, 'material_id')->latestOfMany();
    }

    /** @return HasOne<Slidebook, $this> */
    public function publishedSlidebook(): HasOne
    {
        return $this->hasOne(Slidebook::class, 'material_id')->ofMany(
            ['version' => 'max', 'id' => 'max'],
            fn (Builder $query) => $query->where('status', Slidebook::STATUS_PUBLISHED)
        );
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('learning_materials.status', self::STATUS_PUBLISHED)->whereHas('section', fn ($section) => $section->where('status', 'active'));
    }
}
