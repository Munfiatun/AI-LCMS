<?php

namespace App\Services\Learning\Adaptive;

use App\Models\AdaptiveRecommendation;
use App\Models\LearningMaterial;
use App\Models\Quiz;

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

    /**
     * Resolve a published formative quiz that provides concept-level evidence
     * for the recommendation target. Higher target-concept question coverage
     * wins; quiz id is the deterministic tie-breaker.
     */
    public function formativeQuizFor(AdaptiveRecommendation $recommendation): ?Quiz
    {
        if ($recommendation->action === 'additional_diagnostic' || ! $recommendation->target_concept_id) {
            return null;
        }

        $targetConceptId = (int) $recommendation->target_concept_id;

        return Quiz::query()
            ->available()
            ->where('course_id', $recommendation->course_id)
            ->where('assessment_type', Quiz::ASSESSMENT_FORMATIVE)
            ->whereHas('questions.learningConcepts', fn ($query) => $query->where('learning_concepts.id', $targetConceptId))
            ->withCount([
                'questions as target_concept_question_count' => fn ($query) => $query
                    ->whereHas('learningConcepts', fn ($conceptQuery) => $conceptQuery
                        ->where('learning_concepts.id', $targetConceptId)),
            ])
            ->orderByDesc('target_concept_question_count')
            ->orderBy('id')
            ->first();
    }
}
