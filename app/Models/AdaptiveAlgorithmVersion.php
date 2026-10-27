<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdaptiveAlgorithmVersion extends Model
{
    use HasFactory;

    public const TYPE_RULE_BASED = 'rule_based';

    public const TYPE_FUZZY_INFERENCE = 'fuzzy_inference';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_EXPERIMENTAL = 'experimental';

    public const STATUS_RETIRED = 'retired';

    protected $fillable = [
        'code',
        'name',
        'engine_type',
        'version',
        'status',
        'is_baseline',
        'description',
        'configuration',
        'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_baseline' => 'boolean',
            'configuration' => 'array',
            'activated_at' => 'datetime',
        ];
    }
}
