<?php

namespace App\AcademicSession;

use App\Auth\ValidationException;

class AcademicSessionValidator
{
    private const STATUSES = ['active', 'inactive', 'archived'];

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

        if (array_key_exists('session_name', $payload) && trim((string) $payload['session_name']) === '') {
            $errors['session_name'][] = 'Academic session name cannot be empty.';
        }

        if (array_key_exists('start_date', $payload) && trim((string) $payload['start_date']) === '') {
            $errors['start_date'][] = 'Start date is required.';
        }

        if (array_key_exists('end_date', $payload) && trim((string) $payload['end_date']) === '') {
            $errors['end_date'][] = 'End date is required.';
        }

        if (array_key_exists('status', $payload) && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be active, inactive, or archived.';
        }

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    private function validateRequired(array $payload): array
    {
        $errors = [];

        foreach (['session_name', 'start_date', 'end_date'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                $errors[$field][] = str_replace('_', ' ', ucfirst($field)) . ' is required.';
            }
        }

        if (isset($payload['status']) && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be active, inactive, or archived.';
        }

        return $errors;
    }

    private function validStatus(mixed $status): bool
    {
        return in_array((string) $status, self::STATUSES, true);
    }
}
