<?php

namespace App\Support;

use App\Http\ResponseHelper;
use Throwable;

class ErrorHandler
{
    private bool $isDevelopment;

    public function __construct(bool $isDevelopment = false)
    {
        $this->isDevelopment = $isDevelopment;
    }

    public function handle(Throwable $exception): array
    {
        $details = [];
        if ($this->isDevelopment) {
            $details = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }

        return ResponseHelper::error($exception->getMessage(), 500, $details);
    }
}
