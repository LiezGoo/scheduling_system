<?php

return [
    'contract_types' => [
        'permanent' => [
            'max_lecture_hours' => 18,
            'max_lab_hours' => 21,
        ],
        'contractual' => [
            'max_lecture_hours' => 21,
            'max_lab_hours' => 24,
        ],
    ],

    'status_thresholds' => [
        'near_limit_ratio' => 0.85,
    ],
];
