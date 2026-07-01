<?php

namespace App\Attendance;

class AttendanceResponse
{
    public static function fromEntity(Attendance $item): array
    {
        return [
            'id' => $item->id(),
            'attendance_date' => $item->attendanceDate(),
            'academic_session_id' => $item->academicSessionId(),
            'class_id' => $item->classId(),
            'section_id' => $item->sectionId(),
            'student_id' => $item->studentId(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (Attendance $item): array => self::fromEntity($item), $items);
    }
}
