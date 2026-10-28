<?php

return [
    'model_version' => env('MASTERY_MODEL_VERSION', 'diagnostic-formative-weighted-v2'),

    // R4.4 keeps the learner model transparent: diagnostic establishes the
    // baseline, while formative assessment contributes additional evidence.
    // The weights are research-draft parameters and can be validated later
    // without changing the evidence/audit structure.
    'model_status' => 'research_draft',
    'minimum_evidence_count' => (int) env('MASTERY_MIN_EVIDENCE_COUNT', 1),
    'formative_requires_diagnostic_baseline' => true,
    'assessment_weights' => [
        'diagnostic' => 1.0,
        'formative' => 1.0,
    ],
];
