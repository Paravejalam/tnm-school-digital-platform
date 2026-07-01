<?php

namespace App\Teacher;

use App\Auth\ValidationException;

class TeacherValidator
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

        if (array_key_exists('email', $payload) && $payload['email'] !== null && $payload['email'] !== '') {
            if (filter_var((string) $payload['email'], FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'][] = 'Email must be valid.';
            }
        }

        if (array_key_exists('employee_id', $payload) && trim((string) $payload['employee_id']) === '') {
            $errors['employee_id'][] = 'Employee id cannot be empty.';
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

        foreach (['employee_id', 'first_name', 'last_name', 'department'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                $errors[$field][] = str_replace('_', ' ', ucfirst($field)) . ' is required.';
            }
        }

        if (($payload['email'] ?? '') !== '' && filter_var((string) $payload['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'Email must be valid.';
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