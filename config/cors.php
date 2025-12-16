<?php

return [


    'paths' => ['api/*', 'sanctum/csrf-cookie'],



    // Prefer patterns for local dev so ports can vary
    'allowed_origins' => [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
    ],
    'allowed_origins_patterns' => [
        '/^http:\/\/localhost(?::\d+)?$/',
        '/^http:\/\/127\.0\.0\.1(?::\d+)?$/',
    ],


    'allowed_methods' => ['*'],


    'allowed_headers' => ['*'],


    'exposed_headers' => [],


    'max_age' => 0,



    'supports_credentials' => true,

];
