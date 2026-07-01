<?php

return [
    'driver' => env('CACHE_DRIVER', 'file'),
    'path' => env('CACHE_PATH', __DIR__ . '/../storage/cache'),
    'ttl' => (int) env('CACHE_TTL', 300),
];
