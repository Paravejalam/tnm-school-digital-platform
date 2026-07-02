<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Application Service Container
 *
 * Lightweight dependency injection container.
 * Stores resolved instances and factory callables.
 *
 * Authority: .github/AGENT.md
 */
class AppContainer
{
    /** @var array<string, mixed> Resolved service instances */
    private array $services = [];

    /** @var array<string, callable> Lazy factory callables */
    private array $factories = [];

    /**
     * Register a resolved instance.
     */
    public function set(string $name, mixed $value): void
    {
        $this->services[$name] = $value;
    }

    /**
     * Register a lazy factory callable.
     * The factory is invoked once; the result is cached as a singleton.
     */
    public function bind(string $name, callable $factory): void
    {
        $this->factories[$name] = $factory;
    }

    /**
     * Resolve a service by name.
     * Lazy factories are invoked on first access and cached.
     */
    public function get(string $name): mixed
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        if (isset($this->factories[$name])) {
            $resolved = ($this->factories[$name])($this);
            $this->services[$name] = $resolved;
            unset($this->factories[$name]);
            return $resolved;
        }

        return null;
    }

    /**
     * Check if a service is registered (instance or factory).
     */
    public function has(string $name): bool
    {
        return isset($this->services[$name]) || isset($this->factories[$name]);
    }

    /**
     * Alias for get() — supports make() convention.
     */
    public function make(string $name): mixed
    {
        return $this->get($name);
    }
}