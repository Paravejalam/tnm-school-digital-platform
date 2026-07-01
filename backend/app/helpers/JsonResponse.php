<?php

namespace App\Helpers;

class JsonResponse
{
    public static function success(mixed $data = null, int $status = 200): array
    {
        return [
            'status' => 'success',
            'data' => $data,
            'timestamp' => gmdate('c'),
        ];
    }

    public static function error(string $message, int $status = 500, array $details = []): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'details' => $details,
            'timestamp' => gmdate('c'),
        ];
    }
}
