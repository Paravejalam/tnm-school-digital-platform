<?php

namespace App\User;

class UserResponse
{
    public static function fromEntity(User $item): array
    {
        return [
            'id' => $item->id(),
            'name' => $item->name(),
            'email' => $item->email(),
            'is_active' => (bool) $item->isActive(),
            'last_login_at' => $item->lastLoginAt(),
            'created_at' => $item->createdAt(),
            'updated_at' => $item->updatedAt(),
            'roles' => $item->roleSlugs(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (User $item): array => self::fromEntity($item), $items);
    }
}
