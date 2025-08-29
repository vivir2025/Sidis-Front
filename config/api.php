<?php

return [
    'base_url' => env('API_BASE_URL', 'http://sidis.nacerparavivir.org/api/v1'),
    'timeout' => env('API_TIMEOUT', 30),
    'retry_attempts' => env('API_RETRY_ATTEMPTS', 3),
    'retry_delay' => env('API_RETRY_DELAY', 1000),
    
    'endpoints' => [
        'login' => '/auth/login',
        'logout' => '/auth/logout',
        'me' => '/auth/me',
        'refresh' => '/auth/refresh',
        'sync' => '/sync',
        'health' => '/health',

      'pacientes' => [
   'index' => '/pacientes',
            'store' => '/pacientes',
            'show' => '/pacientes/{uuid}',
            'update' => '/pacientes/{uuid}',
            'destroy' => '/pacientes/{uuid}',
            'search' => '/pacientes/search',
            'search_by_document' => '/pacientes/search/document',
            'bulk_sync' => '/pacientes/sync',
],
    ],
    
    'offline' => [
        'enabled' => env('OFFLINE_LOGIN_ENABLED', true),
        'max_offline_days' => env('MAX_OFFLINE_DAYS', 7),
        'storage_path' => storage_path('app/offline'),
    ]
];
