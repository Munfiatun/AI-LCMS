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
            'description' => 'Experimental Mamdani Fuzzy Inference System for comparison with the deterministic baseline.',
            'configuration' => [
                'validation_status' => 'research_draft',
                'inference_method' => 'mamdani',
                'and_operator' => 'minimum',
                'aggregation_operator' => 'maximum',
                'defuzzification' => 'centroid',
                'targeting' => [
                    'minimum_evidence_count' => 2,
                    'prerequisite_ready_minimum' => 70.00,
                ],
                'output_universe' => ['min' => 0, 'max' => 100, 'step' => 1],
                'inputs' => [
                    'mastery_score' => [
                        'universe' => [0, 100],
                        'sets' => [
                            'low' => ['type' => 'trapezoid', 'points' => [0, 0, 30, 50]],
                            'medium' => ['type' => 'triangle', 'points' => [30, 60, 80]],
                            'high' => ['type' => 'trapezoid', 'points' => [60, 80, 100, 100]],
                        ],
                    ],
                    'evidence_count' => [
                        'universe' => [0, 10],
                        'sets' => [
                            'low' => ['type' => 'trapezoid', 'points' => [0, 0, 1, 2]],
                            'sufficient' => ['type' => 'triangle', 'points' => [1, 3, 5]],
                            'high' => ['type' => 'trapezoid', 'points' => [4, 5, 10, 10]],
                        ],
                    ],
                    'prerequisite_mastery' => [
                        'universe' => [0, 100],
                        'sets' => [
                            'not_mastered' => ['type' => 'trapezoid', 'points' => [0, 0, 40, 60]],
                            'partial' => ['type' => 'triangle', 'points' => [40, 65, 80]],
                            'mastered' => ['type' => 'trapezoid', 'points' => [70, 85, 100, 100]],
                        ],
                    ],
                ],
                'outputs' => [
                    'additional_diagnostic' => ['type' => 'trapezoid', 'points' => [0, 0, 10, 22]],
                    'prerequisite_remediation' => ['type' => 'triangle', 'points' => [12, 28, 42]],
                    'remediation' => ['type' => 'triangle', 'points' => [32, 45, 58]],
                    'reinforcement' => ['type' => 'triangle', 'points' => [48, 60, 72]],
                    'next_concept' => ['type' => 'triangle', 'points' => [64, 78, 90]],
                    'enrichment' => ['type' => 'trapezoid', 'points' => [82, 92, 100, 100]],
                ],
                'rules' => [
                    ['if' => ['evidence_count' => 'low'], 'then' => 'additional_diagnostic'],
                    ['if' => ['evidence_count' => 'sufficient', 'prerequisite_mastery' => 'not_mastered'], 'then' => 'prerequisite_remediation'],
                    ['if' => ['evidence_count' => 'high', 'prerequisite_mastery' => 'not_mastered'], 'then' => 'prerequisite_remediation'],
                    ['if' => ['mastery_score' => 'low', 'evidence_count' => 'sufficient', 'prerequisite_mastery' => 'partial'], 'then' => 'remediation'],
                    ['if' => ['mastery_score' => 'low', 'evidence_count' => 'high', 'prerequisite_mastery' => 'partial'], 'then' => 'remediation'],
                    ['if' => ['mastery_score' => 'low', 'evidence_count' => 'sufficient', 'prerequisite_mastery' => 'mastered'], 'then' => 'remediation'],
                    ['if' => ['mastery_score' => 'low', 'evidence_count' => 'high', 'prerequisite_mastery' => 'mastered'], 'then' => 'remediation'],
                    ['if' => ['mastery_score' => 'medium', 'evidence_count' => 'sufficient', 'prerequisite_mastery' => 'partial'], 'then' => 'reinforcement'],
                    ['if' => ['mastery_score' => 'medium', 'evidence_count' => 'high', 'prerequisite_mastery' => 'partial'], 'then' => 'reinforcement'],
                    ['if' => ['mastery_score' => 'medium', 'evidence_count' => 'sufficient', 'prerequisite_mastery' => 'mastered'], 'then' => 'reinforcement'],
                    ['if' => ['mastery_score' => 'medium', 'evidence_count' => 'high', 'prerequisite_mastery' => 'mastered'], 'then' => 'reinforcement'],
                    ['if' => ['mastery_score' => 'high', 'evidence_count' => 'sufficient', 'prerequisite_mastery' => 'partial'], 'then' => 'reinforcement'],
                    ['if' => ['mastery_score' => 'high', 'evidence_count' => 'high', 'prerequisite_mastery' => 'partial'], 'then' => 'reinforcement'],
                    ['if' => ['mastery_score' => 'high', 'evidence_count' => 'sufficient', 'prerequisite_mastery' => 'mastered'], 'then' => 'next_concept'],
                    ['if' => ['mastery_score' => 'high', 'evidence_count' => 'high', 'prerequisite_mastery' => 'mastered'], 'then' => 'enrichment'],
                ],
            ],
        ],
    ],
];
