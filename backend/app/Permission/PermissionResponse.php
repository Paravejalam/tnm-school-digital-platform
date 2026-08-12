<?php

namespace App\Permission;

class PermissionResponse
{
    public static function fromEntity(Permission $item): array
    {
        return [
            'id' => $item->id(),
            'name' => $item->name(),
            'slug' => $item->slug(),
            'module' => $item->module(),
            'description' => $item->description(),
            'created_at' => $item->createdAt(),
            'updated_at' => $item->updatedAt(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (Permission $item): array => self::fromEntity($item), $items);
    }
}
