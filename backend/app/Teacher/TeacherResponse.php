<?php

namespace App\Teacher;

class TeacherResponse
{
    public static function fromTeacher(Teacher $teacher): array
    {
        return [
            'id' => $teacher->id(),
            'employee_id' => $teacher->employeeId(),
            'first_name' => $teacher->firstName(),
            'last_name' => $teacher->lastName(),
            'email' => $teacher->email(),
            'phone' => $teacher->phone(),
            'department' => $teacher->department(),
            'designation' => $teacher->designation(),
            'status' => $teacher->status(),
        ];
    }

    public static function collection(array $teachers): array
    {
        return array_map(fn (Teacher $teacher): array => self::fromTeacher($teacher), $teachers);
    }
}