<?php

namespace App\SystemSetting;

class SystemSettingResponse
{
    public static function fromEntity(SystemSetting $item): array
    {
        return [
            'id' => $item->id(),
            'key' => $item->key(),
            'value' => self::castValue($item->value(), $item->type()),
            'type' => $item->type(),
            'module' => $item->module(),
            'description' => $item->description(),
            'is_public' => (bool) $item->isPublic(),
            'created_at' => $item->createdAt(),
            'updated_at' => $item->updatedAt(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (SystemSetting $item): array => self::fromEntity($item), $items);
    }

    private static function castValue(mixed $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'json' => self::decodeJson($value),
            default => (string) $value,
        };
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
