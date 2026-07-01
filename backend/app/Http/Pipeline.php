<?php

declare(strict_types=1);

namespace App\Http;

class Pipeline
{
    /**
     * Executes the provided callback as the final step of the request lifecycle.
     */
    public function handle(callable $callback): mixed
    {
        return $callback();
    }
}
