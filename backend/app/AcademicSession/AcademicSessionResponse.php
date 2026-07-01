<?php

namespace App\AcademicSession;

class AcademicSessionResponse
{
    public static function fromEntity(AcademicSession $item): array
    {
        return [
            'id' => $item->id(),
            'session_name' => $item->sessionName(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (AcademicSession $item): array => self::fromEntity($item), $items);
    }
}
