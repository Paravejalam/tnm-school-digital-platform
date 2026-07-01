<?php

namespace App\Middleware;

class RequestHandler
{
    public static function handle(array $server): array
    {
        $method = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        return [
            'method' => $method,
            'path' => $path,
            'query' => $_GET,
            'body' => $_POST,
            'headers' => getallheaders(),
        ];
    }
}
