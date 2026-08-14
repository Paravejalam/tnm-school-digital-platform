<?php

namespace App\Student;

use App\Auth\ValidationException;

class StudentValidator
{
    private const STATUSES = ['active', 'inactive', 'graduated', 'transferred', 'withdrawn'];

    public function validateCreate(array $payload): void
    {
        $errors = $this->validateRequired($payload);

        if (array_key_exists('status', $payload) && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be active, inactive, graduated, transferred or withdrawn.';
        }

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

        if (array_key_exists('admission_number', $payload) && trim((string) $payload['admission_number']) === '') {
            $errors['admission_number'][] = 'Admission number cannot be empty.';
        }

        if (isset($payload['status']) && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be active, inactive, graduated, transferred or withdrawn.';
        }

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    private function validateRequired(array $payload): array
    {
        $errors = [];

        foreach (['admission_number', 'first_name', 'last_name', 'class_name'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                $errors[$field][] = str_replace('_', ' ', ucfirst($field)) . ' is required.';
            }
        }

        if (($payload['email'] ?? '') !== '' && filter_var((string) $payload['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'Email must be valid.';
        }

        return $errors;
    }

    private function validStatus(mixed $status): bool
    {
        return in_array((string) $status, self::STATUSES, true);
    }
}
