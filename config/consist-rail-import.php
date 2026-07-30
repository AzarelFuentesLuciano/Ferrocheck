<?php

declare(strict_types=1);

$vinAliases = [
    'vin',
    'vehicle identification number',
    'vehicle id number',
    'wholevin',
    'whole vin',
    'fdwholevin',
    'fdwholevin1',
    'fd whole vin',
    'fd whole vin 1',
];

return [
    'max_file_size' => 10 * 1024 * 1024,
    'max_batch_size' => 30 * 1024 * 1024,
    'expires_seconds' => 30 * 60,
    'header_scan_rows' => 50,
    'header_scan_max_columns' => 100,
    'max_worksheet_rows' => 250000,
    'chunk_rows' => 500,
    'sample_limit' => 20,
    'analysis_sample_limit' => 50,
    'error_limit' => 25,
    'allowed_extensions' => ['xlsx', 'xls', 'csv'],
    'vin_aliases' => $vinAliases,
    'files' => [
        'vehicle_load_report' => [
            'label' => 'Vehicle Load Report',
            'required_headers' => [
                'vin' => [
                    'fdwholevin',
                    'wholevin',
                    'whole vin',
                    'vin',
                ],
            ],
            'identity_headers' => [
                'fdtransportationname1',
                'fdloadid',
                'fdtrack',
                'fddestinationlocation',
                'fdshippernumberprefix',
            ],
            'minimum_identity_matches' => 3,
        ],
        'shippers' => [
            'label' => 'Shippers',
            'required_headers' => [
                'vin' => [
                    'fdwholevin1',
                    'wholevin1',
                    'whole vin 1',
                    'vin',
                ],
            ],
            'identity_headers' => [
                'fdpedimentotype',
                'fdportcode',
                'fdbillnumber',
                'fdbilldate',
                'fdprintdate',
            ],
            'minimum_identity_matches' => 3,
        ],
        'cnacs' => [
            'label' => 'CNACS',
            'required_headers' => [
                'vin' => [
                    'vin',
                ],
            ],
            'identity_headers' => [
                'invoiceno',
                'numremesa',
                'brokerid',
                'h/scode',
                'bodymodel',
                'conveyance',
            ],
            'minimum_identity_matches' => 3,
        ],
    ],
];
