<?php

namespace App\Auth;

class AuthMiddleware
{
    public function handle(callable $next): mixed
    {
        return $next();
    }
}