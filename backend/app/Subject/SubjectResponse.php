<?php

namespace App\Subject;

class SubjectResponse
{
    public static function fromEntity(Subject $item): array
    {
        return [
            'id' => $item->id(),
            'subject_name' => $item->subjectName(),
            'code' => $item->code(),
            'section_id' => $item->sectionId(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (Subject $item): array => self::fromEntity($item), $items);
    }
}
