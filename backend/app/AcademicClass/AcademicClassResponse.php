<?php

namespace App\AcademicClass;

class AcademicClassResponse
{
    public static function fromEntity(AcademicClass $item): array
    {
        return [
            'id' => $item->id(),
            'class_name' => $item->className(),
            'code' => $item->code(),
            'academic_session_id' => $item->academicSessionId(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (AcademicClass $item): array => self::fromEntity($item), $items);
    }
}
