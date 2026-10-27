<?php

return [
    'baseline_algorithm' => env('ADAPTIVE_BASELINE_ALGORITHM', 'rule-based-v1'),
    'comparison_algorithm' => env('ADAPTIVE_COMPARISON_ALGORITHM', 'fuzzy-v2'),

    'algorithms' => [
        'rule-based-v1' => [
            'name' => 'Rule-Based Adaptive Engine',
            'engine_type' => 'rule_based',
            'version' => '1.0.0',
            'status' => 'active',
            'is_baseline' => true,
            'description' => 'Deterministic and explainable baseline for adaptive learning decisions.',
            'configuration' => [
                'validation_status' => 'research_draft',
                'minimum_evidence_count' => 2,
                'mastery_thresholds' => [
                    'low_max' => 39.99,
                    'medium_max' => 79.99,
                    'prerequisite_minimum' => 70.00,
                ],
                'actions' => [
                    'additional_diagnostic',
                    'prerequisite_remediation',
                    'remediation',
                    'reinforcement',
                    'next_concept',
                    'enrichment',
                ],
            ],
        ],

        'fuzzy-v2' => [
            'name' => 'Fuzzy Inference Adaptive Engine',
            'engine_type' => 'fuzzy_inference',
            'version' => '2.0.0-experimental',
            'status' => 'experimental',
            'is_baseline' => false,
            'description' => 'Experimental Fuzzy Inference System for comparison with the deterministic baseline.',
            'configuration' => [
                'validation_status' => 'research_draft',
                'inference_method' => 'mamdani',
                'defuzzification' => 'centroid',
                'inputs' => [
                    'mastery_score' => ['low', 'medium', 'high'],
                    'evidence_count' => ['low', 'sufficient', 'high'],
                    'prerequisite_mastery' => ['not_mastered', 'partial', 'mastered'],
                ],
                'outputs' => [
                    'additional_diagnostic',
                    'remediation',
                    'reinforcement',
                    'next_concept',
                    'enrichment',
                ],
            ],
        ],
    ],
];
