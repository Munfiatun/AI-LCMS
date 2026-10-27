<?php

return [
    'model_version' => env('MASTERY_MODEL_VERSION', 'diagnostic-weighted-v1'),

    // R2 uses a transparent weighted-correctness model. Classification thresholds
    // are intentionally deferred until expert/pilot validation.
    'minimum_evidence_count' => (int) env('MASTERY_MIN_EVIDENCE_COUNT', 1),
];
