<?php

declare(strict_types=1);

namespace App\Support;

use App\Http\ResponseHelper;
use Throwable;

/**
 * Central Exception Handler
 *
 * In development mode: includes file, line, and abbreviated trace.
 * In production mode: returns only a safe generic message. NO stack trace is exposed.
 *
 * Authority: .github/AGENT.md Section 24.6 — Error responses must not expose internals.
 */
class ErrorHandler
{
    public function __construct(private readonly bool $isDevelopment = false)
    {
    }

    /**
     * Handle a throwable and return a structured JSON-compatible response array.
     * Also sets the HTTP status code on the response.
     */
    public function handle(Throwable $exception): array
    {
        $httpCode = $this->resolveHttpCode($exception);

        http_response_code($httpCode);

        if ($this->isDevelopment) {
            $details = [
                'exception' => get_class($exception),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'trace'     => array_slice($exception->getTrace(), 0, 5),
            ];

            return ResponseHelper::error($exception->getMessage(), $httpCode, $details);
        }

        // Production: safe message only — no internal path, no trace
        $safeMessage = $httpCode >= 500
            ? 'An internal server error occurred. Please contact support.'
            : $exception->getMessage();

        return ResponseHelper::error($safeMessage, $httpCode);
    }

    /**
     * Derive an appropriate HTTP status code from the exception.
     * Domain exceptions may carry a meaningful code; otherwise default to 500.
     */
    private function resolveHttpCode(Throwable $exception): int
    {
        $code = $exception->getCode();
        if (is_int($code) && $code >= 400 && $code <= 599) {
            return $code;
        }

        return 500;
    }
}