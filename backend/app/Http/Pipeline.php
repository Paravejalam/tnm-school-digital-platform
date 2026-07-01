<?php

declare(strict_types=1);

namespace App\Http;

class Pipeline
{
    public function handle(callable $callback, array $middleware = [], mixed $request = null): mixed
    {
        $next = $callback;

        foreach (array_reverse($middleware) as $handler) {
            $next = fn (): mixed => $handler->handle($request, $next);
        }

        return $next();
    }
}