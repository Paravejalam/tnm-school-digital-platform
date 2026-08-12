<?php

namespace App\Role;

class RoleResponse
{
    public static function fromEntity(Role $item): array
    {
        return [
            'id' => $item->id(),
            'name' => $item->name(),
            'slug' => $item->slug(),
            'description' => $item->description(),
            'is_system' => (bool) $item->isSystem(),
            'permissions' => $item->permissionSlugs(),
            'created_at' => $item->createdAt(),
            'updated_at' => $item->updatedAt(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (Role $item): array => self::fromEntity($item), $items);
    }
}
