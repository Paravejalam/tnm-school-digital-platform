<?php

namespace App\Audit;

use App\Auth\JwtHelper;
use App\Http\RequestHelper;
use PDO;
use ReflectionClass;
use Throwable;

class AuditLogger
{
    private ?RequestHelper $request;

    public function __construct(
        private ?PDO $database = null,
        private ?AuditLogRepositoryInterface $repository = null,
        private ?JwtHelper $jwtHelper = null,
        ?RequestHelper $request = null,
    ) {
        $this->request = $request;
    }

    public function log(string $action, string $entityType, ?int $entityId, ?array $oldValues = null, ?array $newValues = null): void
    {
        if (!$this->database instanceof PDO || !$this->repository instanceof AuditLogRepositoryInterface) {
            return;
        }

        $userId = $this->extractUserId();
        $ipAddress = $this->extractIpAddress();
        $userAgent = $this->extractUserAgent();

        $this->repository->create([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            'new_values' => $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    private function extractUserId(): ?int
    {
        if (!$this->jwtHelper instanceof JwtHelper) {
            return null;
        }

        $token = $this->extractBearerToken();

        if ($token === null) {
            return null;
        }

        try {
            $payload = $this->jwtHelper->decode($token);

            return isset($payload['id']) ? (int) $payload['id'] : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function extractBearerToken(): ?string
    {
        if ($this->request instanceof RequestHelper) {
            $requestData = $this->request->all();
            $headers = $requestData['headers'] ?? [];
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strtolower((string) $name) === 'authorization' && is_string($value)) {
                        return str_starts_with($value, 'Bearer ') ? substr($value, 7) : $value;
                    }
                }
            }

            $body = $requestData['body'] ?? [];
            if (is_array($body) && isset($body['token']) && is_string($body['token'])) {
                return $body['token'];
            }
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strtolower((string) $name) === 'authorization' && is_string($value)) {
                        return str_starts_with($value, 'Bearer ') ? substr($value, 7) : $value;
                    }
                }
            }
        }

        return null;
    }

    private function extractIpAddress(): ?string
    {
        if ($this->request instanceof RequestHelper) {
            $requestData = $this->request->all();
            $server = $requestData['server'] ?? [];
            if (is_array($server)) {
                return $server['REMOTE_ADDR'] ?? null;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    private function extractUserAgent(): ?string
    {
        if ($this->request instanceof RequestHelper) {
            $requestData = $this->request->all();
            $server = $requestData['server'] ?? [];
            if (is_array($server)) {
                return $server['HTTP_USER_AGENT'] ?? null;
            }
        }

        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    public function entityToArray(?object $entity): ?array
    {
        if ($entity === null) {
            return null;
        }

        $reflection = new ReflectionClass($entity);
        $properties = $reflection->getProperties();
        $data = [];

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $property->setAccessible(true);
            if ($property->isInitialized($entity)) {
                $data[$property->getName()] = $property->getValue($entity);
            }
        }

        return $data;
    }
}
