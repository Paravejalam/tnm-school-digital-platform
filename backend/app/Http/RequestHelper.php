<?php

namespace App\Http;

class RequestHelper
{
    private array $server;
    private array $query;
    private array $body;
    private array $headers;
    private mixed $jsonBody;

    public function __construct(array $server = [], array $query = [], array $body = [], array $headers = [], mixed $jsonBody = null)
    {
        $this->server = $server;
        $this->query = $query;
        $this->body = $body;
        $this->headers = $headers;
        $this->jsonBody = $jsonBody;
    }

    public static function fromGlobals(): self
    {
        $rawBody = file_get_contents('php://input');
        $jsonBody = json_decode($rawBody, true);

        if (!is_array($jsonBody)) {
            $jsonBody = null;
        }

        return new self(
            $_SERVER,
            $_GET,
            $_POST,
            getallheaders(),
            $jsonBody
        );
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        return parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function put(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function delete(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function json(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->jsonBody;
        }

        return $this->jsonBody[$key] ?? $default;
    }

    public function all(): array
    {
        return [
            'method' => $this->method(),
            'path' => $this->path(),
            'query' => $this->query,
            'body' => $this->body,
            'headers' => $this->headers,
            'json' => $this->jsonBody,
        ];
    }
}
