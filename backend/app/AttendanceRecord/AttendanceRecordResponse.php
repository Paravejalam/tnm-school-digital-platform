<?php

namespace App\AttendanceRecord;

class AttendanceRecordResponse
{
    public static function fromEntity(AttendanceRecord $item): array
    {
        return [
            'id' => $item->id(),
            'record_name' => $item->recordName(),
            'attendance_id' => $item->attendanceId(),
            'student_id' => $item->studentId(),
            'status' => $item->status(),
        ];
    }

    public static function collection(array $items): array
    {
        return array_map(fn (AttendanceRecord $item): array => self::fromEntity($item), $items);
    }
}
