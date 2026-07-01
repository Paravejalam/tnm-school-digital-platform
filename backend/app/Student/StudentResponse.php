<?php

namespace App\Student;

class StudentResponse
{
    public static function fromStudent(Student $student): array
    {
        return [
            'id' => $student->id(),
            'admission_number' => $student->admissionNumber(),
            'first_name' => $student->firstName(),
            'last_name' => $student->lastName(),
            'email' => $student->email(),
            'phone' => $student->phone(),
            'class_name' => $student->className(),
            'section' => $student->section(),
            'status' => $student->status(),
        ];
    }

    public static function collection(array $students): array
    {
        return array_map(fn (Student $student): array => self::fromStudent($student), $students);
    }
}