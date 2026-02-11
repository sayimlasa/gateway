<?php

return [
    'campuses' => [
        1 => [
            'authorization' => env('NACTE_CAMPUS1_AUTH'),
            'api_code'      => env('NACTE_CAMPUS1_CODE'),
            'api_code_submit' => env('NACTE_CAMPUS1_CODE_SUBMIT'),
            'api_code_processed' => env('NACTE_CAMPUS1_CODE_PROCESSED'),
            'api_code_unprocessed' => env('NACTE_CAMPUS1_CODE_UNPROCESSED'),
            'api_code_structure' => env('NACTE_CAMPUS1_CODE_STRUCTURE')
            
        ],
        2 => [
            'authorization' => env('NACTE_CAMPUS2_AUTH'),
            'api_code'      => env('NACTE_CAMPUS2_CODE'),
            'api_code_submit' => env('NACTE_CAMPUS2_CODE_SUBMIT'),
            'api_code_processed' => env('NACTE_CAMPUS2_CODE_PROCESSED'),
            'api_code_unprocessed' => env('NACTE_CAMPUS2_CODE_UNPROCESSED'),
            'api_code_structure' => env('NACTE_CAMPUS2_CODE_STRUCTURE')

        ],
    ],
];
