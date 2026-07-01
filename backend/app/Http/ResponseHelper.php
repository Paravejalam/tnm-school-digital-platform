<?php

namespace App\Http;

class ResponseHelper
{
    public static function success(mixed $data = null, int $status = 200, array $meta = []): array
    {
        return [
            'success' => true,
            'status' => $status,
            'data' => $data,
            'meta' => array_merge(['timestamp' => gmdate('c')], $meta),
        ];
    }

    public static function error(string $message, int $status = 500, array $details = []): array
    {
        return [
            'success' => false,
            'status' => $status,
            'error' => [
                'message' => $message,
                'details' => $details,
                'timestamp' => gmdate('c'),
            ],
        ];
    }

    public static function validation(array $errors, string $message = 'Validation failed', int $status = 422): array
    {
        return self::error($message, $status, ['validation' => $errors]);
    }
}
