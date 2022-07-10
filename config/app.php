<?php

declare(strict_types=1);

return [
    'google_analytics'=> [
        'view_id'        => $_ENV['VIEW_ID'],
        'reporting_api'  =>  [
            'credentials' => __PROJECT_ROOT__ . '/' . $_ENV['CREDENTIAL_FILE_NAME'],
        ]
    ]
];
