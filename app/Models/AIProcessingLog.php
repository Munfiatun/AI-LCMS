<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AIProcessingLog extends Model
{
    use HasFactory;

    protected $table = 'ai_processing_logs';

    public const PROCESS_MATERIAL_ANALYSIS = 'material_analysis';

    public const PROCESS_SLIDEBOOK_GENERATION = 'slidebook_generation';

    public const PROCESS_QUESTION_EXTRACTION = 'question_extraction';

    public const PROCESS_QUESTION_AUTHORING = 'question_authoring';

    public const PROCESS_MATERIAL_AUTHORING = 'material_authoring';

    public const PROCESS_QUESTION_REVISION = 'question_revision';

    public const PROCESS_MATERIAL_REVISION = 'material_revision';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'process_type',
        'source_type',
        'source_id',
        'provider',
        'model',
        'prompt_version',
        'input_hash',
        'status',
        'error_code',
        'error_message',
        'attempt_count',
        'processing_started_at',
        'processing_completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'processing_started_at' => 'datetime',
            'processing_completed_at' => 'datetime',
        ];
    }

    /**
     * @return HasOne<AIProcessingResult, $this>
     */
    public function result(): HasOne
    {
        return $this->hasOne(AIProcessingResult::class, 'log_id');
    }
}
