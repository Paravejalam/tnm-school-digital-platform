<?php

namespace App\Audit;

class AuditLogResponse
{
    public static function fromEntity(AuditLog $item): array
    {
        return [
            'id' => $item->id(),
            'user_id' => $item->userId(),
            'action' => $item->action(),
            'entity_type' => $item->entityType(),
            'entity_id' => $item->entityId(),
            'old_values' => self::decodeJson($item->oldValues()),
            'new_values' => self::decodeJson($item->newValues()),
            'ip_address' => $item->ipAddress(),
            'user_agent' => $item->userAgent(),
            'created_at' => $item->createdAt(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (AuditLog $item): array => self::fromEntity($item), $items);
    }

    private static function decodeJson(mixed $value): mixed
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
