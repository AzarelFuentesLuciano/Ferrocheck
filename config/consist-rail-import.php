<?php

declare(strict_types=1);

$vinAliases = ['vin', 'vehicle identification number', 'vehicle id number'];

return [
    'max_file_size' => 10 * 1024 * 1024,
    'max_batch_size' => 30 * 1024 * 1024,
    'expires_seconds' => 30 * 60,
    'header_scan_rows' => 50,
    'chunk_rows' => 500,
    'sample_limit' => 20,
    'analysis_sample_limit' => 50,
    'error_limit' => 25,
    'allowed_extensions' => ['xlsx', 'xls', 'csv'],
    'files' => [
        'vehicle_load_report' => [
            'label' => 'Vehicle Load Report',
            'required_headers' => ['vin' => $vinAliases],
        ],
        'shippers' => [
            'label' => 'Shippers',
            'required_headers' => ['vin' => $vinAliases],
        ],
        'cnacs' => [
            'label' => 'CNACS',
            'required_headers' => ['vin' => $vinAliases],
        ],
    ],
];
