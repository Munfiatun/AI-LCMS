<?php

namespace App\Console\Commands;

use App\Services\Learning\Adaptive\AdaptiveAlgorithmRegistry;
use Illuminate\Console\Command;

class RegisterAdaptiveAlgorithms extends Command
{
    protected $signature = 'research:r3-register-algorithms';

    protected $description = 'Register configured adaptive-learning algorithm versions for R3 research auditability.';

    public function handle(AdaptiveAlgorithmRegistry $registry): int
    {
        $versions = $registry->syncConfiguredVersions();

        $this->info('Adaptive algorithm versions synchronized.');
        $this->table(
            ['Code', 'Engine', 'Version', 'Status', 'Baseline'],
            $versions->map(fn ($algorithm): array => [
                $algorithm->code,
                $algorithm->engine_type,
                $algorithm->version,
                $algorithm->status,
                $algorithm->is_baseline ? 'yes' : 'no',
            ])->all()
        );

        return self::SUCCESS;
    }
}
