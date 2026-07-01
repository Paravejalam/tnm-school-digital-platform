<?php

namespace App\Auth;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(string $message = 'Validation failed', private array $errors = [])
    {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
