<?php

namespace App\AttendanceRecord;

use App\Auth\ValidationException;

class AttendanceRecordValidator
{
    private const STATUSES = ['active', 'inactive'];

    public function validateCreate(array $payload): void
    {
        $errors = $this->validateRequired($payload);

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    public function validateUpdate(array $payload): void
    {
        $errors = [];

        if (array_key_exists('record_name', $payload) && trim((string) $payload['record_name']) === '') {
            $errors['record_name'][] = 'Attendance record name cannot be empty.';
        }

        if (array_key_exists('status', $payload) && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    private function validateRequired(array $payload): array
    {
        $errors = [];

        foreach (['record_name', 'attendance_id', 'student_id'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                $errors[$field][] = str_replace('_', ' ', ucfirst($field)) . ' is required.';
            }
        }

        if (isset($payload['status']) && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be active or inactive.';
        }

        return $errors;
    }

    private function validStatus(mixed $status): bool
    {
        return in_array((string) $status, self::STATUSES, true);
    }
}
