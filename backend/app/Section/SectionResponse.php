<?php

namespace App\Section;

class SectionResponse
{
    public static function fromEntity(Section $item): array
    {
        return [
            'id' => $item->id(),
            'section_name' => $item->sectionName(),
            'code' => $item->code(),
            'class_id' => $item->classId(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (Section $item): array => self::fromEntity($item), $items);
    }
}
