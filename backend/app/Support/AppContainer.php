<?php

namespace App\Support;

class AppContainer
{
    private array $services = [];

    public function set(string $name, mixed $value): void
    {
        $this->services[$name] = $value;
    }

    public function get(string $name): mixed
    {
        return $this->services[$name] ?? null;
    }
}
