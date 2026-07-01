<?php

return [
    'app' => [
        'name' => env('APP_NAME', 'T.N. Memorial Public School Digital Platform'),
        'env' => env('APP_ENV', 'local'),
        'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
        'url' => env('APP_URL', 'http://localhost'),
    ],
    'mail' => [
        'host' => env('MAIL_HOST', 'smtp.localhost'),
        'port' => (int) env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'from' => env('MAIL_FROM', 'noreply@example.com'),
    ],
    'database' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => (int) env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'tnm_school_platform'),
        'user' => env('DB_USER', 'root'),
        'password' => env('DB_PASSWORD', ''),
    ],
];
