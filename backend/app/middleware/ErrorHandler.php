<?php

namespace App\Middleware;

use App\Helpers\JsonResponse;

class ErrorHandler
{
    public static function handle(Throwable $exception): array
    {
        http_response_code(500);
        return JsonResponse::error($exception->getMessage(), 500, [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
