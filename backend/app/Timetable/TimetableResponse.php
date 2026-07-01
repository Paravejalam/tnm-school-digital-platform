<?php

namespace App\Timetable;

class TimetableResponse
{
    public static function fromEntity(Timetable $item): array
    {
        return [
            'id' => $item->id(),
            'timetable_name' => $item->timetableName(),
            'academic_session_id' => $item->academicSessionId(),
            'class_id' => $item->classId(),
            'section_id' => $item->sectionId(),
            'subject_id' => $item->subjectId(),
            'teacher_id' => $item->teacherId(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (Timetable $item): array => self::fromEntity($item), $items);
    }
}
