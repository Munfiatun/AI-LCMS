<?php

namespace App\Services\Learning\Adaptive;

use App\Models\AdaptiveAlgorithmVersion;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class AdaptiveAlgorithmRegistry
{
    /** @return Collection<int, AdaptiveAlgorithmVersion> */
    public function syncConfiguredVersions(): Collection
    {
        $algorithms = config('adaptive.algorithms', []);

        return collect($algorithms)->map(function (array $definition, string $code): AdaptiveAlgorithmVersion {
            $algorithm = AdaptiveAlgorithmVersion::query()->firstOrNew(['code' => $code]);
            $activatedAt = $algorithm->activated_at;

            if ($definition['status'] === AdaptiveAlgorithmVersion::STATUS_ACTIVE && $activatedAt === null) {
                $activatedAt = now();
            }

            if ($definition['status'] !== AdaptiveAlgorithmVersion::STATUS_ACTIVE) {
                $activatedAt = null;
            }

            $algorithm->fill([
                'name' => $definition['name'],
                'engine_type' => $definition['engine_type'],
                'version' => $definition['version'],
                'status' => $definition['status'],
                'is_baseline' => (bool) $definition['is_baseline'],
                'description' => $definition['description'] ?? null,
                'configuration' => $definition['configuration'] ?? [],
                'activated_at' => $activatedAt,
            ])->save();

            return $algorithm->refresh();
        })->values();
    }

    public function baseline(): AdaptiveAlgorithmVersion
    {
        $code = (string) config('adaptive.baseline_algorithm');

        return $this->findConfigured($code);
    }

    public function comparison(): AdaptiveAlgorithmVersion
    {
        $code = (string) config('adaptive.comparison_algorithm');

        return $this->findConfigured($code);
    }

    public function findConfigured(string $code): AdaptiveAlgorithmVersion
    {
        if (! array_key_exists($code, config('adaptive.algorithms', []))) {
            throw new InvalidArgumentException("Adaptive algorithm [{$code}] is not configured.");
        }

        $this->syncConfiguredVersions();

        return AdaptiveAlgorithmVersion::query()->where('code', $code)->firstOrFail();
    }
}
