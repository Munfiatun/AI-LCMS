<?php

namespace App\Services\Learning\Adaptive;

use App\Models\AdaptiveRecommendation;
use App\Models\LearningMaterial;

class AdaptiveLearningResourceResolver
{
    /**
     * Resolve the most relevant published learning material for a recommendation.
     * Diagnostic actions intentionally return null because they require an
     * assessment activity rather than a content material.
     */
    public function materialFor(AdaptiveRecommendation $recommendation): ?LearningMaterial
    {
        if ($recommendation->action === 'additional_diagnostic') {
            return null;
        }

        $targetConcept = $recommendation->targetConcept;

        if (! $targetConcept) {
            return null;
        }

        return $targetConcept->materials()
            ->published()
            ->whereHas('section', fn ($query) => $query->where('course_id', $recommendation->course_id))
            ->with('section:id,course_id,title,order')
            ->orderByDesc('material_concepts.relevance_weight')
            ->orderBy('learning_materials.order')
            ->orderBy('learning_materials.id')
            ->first();
    }
}
