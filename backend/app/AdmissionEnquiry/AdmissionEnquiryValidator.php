<?php

namespace App\AdmissionEnquiry;

use App\Auth\ValidationException;

class AdmissionEnquiryValidator
{
    private const STATUSES = ['new', 'contacted', 'qualified', 'rejected', 'admitted', 'withdrawn'];

    public function validateCreate(array $payload): void
    {
        $errors = $this->validateRequired($payload);

        if (array_key_exists('status', $payload) && $payload['status'] !== null && trim((string) $payload['status']) !== '' && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be new, contacted, qualified, rejected, admitted or withdrawn.';
        }

        if (array_key_exists('gender', $payload) && $payload['gender'] !== null && trim((string) $payload['gender']) !== '' && !$this->validGender($payload['gender'])) {
            $errors['gender'][] = 'Gender must be male, female, other or prefer not to say.';
        }

        if (($payload['parent_email'] ?? '') !== '' && filter_var((string) $payload['parent_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['parent_email'][] = 'Parent email must be valid.';
        }

        if (isset($payload['date_of_birth']) && $payload['date_of_birth'] !== null && trim((string) $payload['date_of_birth']) !== '' && !$this->validDate($payload['date_of_birth'])) {
            $errors['date_of_birth'][] = 'Date of birth must be a valid YYYY-MM-DD date.';
        }

        $this->validateStringLengths($errors, $payload);

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    public function validateUpdate(array $payload): void
    {
        $errors = [];

        if (array_key_exists('student_name', $payload) && trim((string) ($payload['student_name'] ?? '')) === '') {
            $errors['student_name'][] = 'Student name cannot be empty.';
        }

        if (array_key_exists('parent_name', $payload) && trim((string) ($payload['parent_name'] ?? '')) === '') {
            $errors['parent_name'][] = 'Parent name cannot be empty.';
        }

        if (array_key_exists('parent_phone', $payload) && trim((string) ($payload['parent_phone'] ?? '')) === '') {
            $errors['parent_phone'][] = 'Parent phone cannot be empty.';
        }

        if (array_key_exists('applying_for_class', $payload) && trim((string) ($payload['applying_for_class'] ?? '')) === '') {
            $errors['applying_for_class'][] = 'Applying for class cannot be empty.';
        }

        if (array_key_exists('status', $payload) && $payload['status'] !== null && trim((string) $payload['status']) !== '' && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be new, contacted, qualified, rejected, admitted or withdrawn.';
        }

        if (array_key_exists('gender', $payload) && $payload['gender'] !== null && trim((string) $payload['gender']) !== '' && !$this->validGender($payload['gender'])) {
            $errors['gender'][] = 'Gender must be male, female, other or prefer not to say.';
        }

        if (array_key_exists('parent_email', $payload) && $payload['parent_email'] !== null && trim((string) $payload['parent_email']) !== '' && filter_var((string) $payload['parent_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['parent_email'][] = 'Parent email must be valid.';
        }

        if (array_key_exists('date_of_birth', $payload) && $payload['date_of_birth'] !== null && trim((string) $payload['date_of_birth']) !== '' && !$this->validDate($payload['date_of_birth'])) {
            $errors['date_of_birth'][] = 'Date of birth must be a valid YYYY-MM-DD date.';
        }

        $this->validateStringLengths($errors, $payload);

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    private function validateRequired(array $payload): array
    {
        $errors = [];

        foreach (['student_name', 'parent_name', 'parent_phone', 'applying_for_class'] as $field) {
            if (trim((string) ($payload[$field] ?? '')) === '') {
                $errors[$field][] = str_replace('_', ' ', ucfirst($field)) . ' is required.';
            }
        }

        if (($payload['parent_email'] ?? '') !== '' && filter_var((string) $payload['parent_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['parent_email'][] = 'Parent email must be valid.';
        }

        if (isset($payload['date_of_birth']) && $payload['date_of_birth'] !== null && trim((string) $payload['date_of_birth']) !== '' && !$this->validDate($payload['date_of_birth'])) {
            $errors['date_of_birth'][] = 'Date of birth must be a valid YYYY-MM-DD date.';
        }

        if (isset($payload['gender']) && $payload['gender'] !== null && trim((string) $payload['gender']) !== '' && !$this->validGender($payload['gender'])) {
            $errors['gender'][] = 'Gender must be male, female, other or prefer not to say.';
        }

        if (isset($payload['status']) && $payload['status'] !== null && trim((string) $payload['status']) !== '' && !$this->validStatus($payload['status'])) {
            $errors['status'][] = 'Status must be new, contacted, qualified, rejected, admitted or withdrawn.';
        }

        return $errors;
    }

    private function validateStringLengths(array &$errors, array $payload): void
    {
        $limits = [
            'student_name' => 150,
            'parent_name' => 150,
            'parent_phone' => 30,
            'applying_for_class' => 50,
            'previous_school' => 255,
            'city' => 100,
            'state' => 100,
            'pincode' => 20,
            'gender' => 20,
            'status' => 30,
            'source' => 50,
            'parent_email' => 255,
            'enquiry_number' => 50,
        ];

        foreach ($limits as $field => $limit) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null) {
                continue;
            }

            $value = trim((string) $payload[$field]);
            if ($value === '') {
                continue;
            }

            if (mb_strlen($value) > $limit) {
                $errors[$field][] = ucfirst(str_replace('_', ' ', $field)) . ' exceeds the maximum length of ' . $limit . ' characters.';
            }
        }
    }

    private function validStatus(mixed $status): bool
    {
        return in_array(strtolower((string) $status), self::STATUSES, true);
    }

    private function validGender(mixed $gender): bool
    {
        $valid = ['male', 'female', 'other', 'prefer not to say'];

        return in_array(strtolower((string) $gender), $valid, true);
    }

    private function validDate(mixed $value): bool
    {
        $date = trim((string) $value);
        if ($date === '') {
            return true;
        }

        $d = DateTime::createFromFormat('Y-m-d', $date);

        return $d instanceof DateTime && $d->format('Y-m-d') === $date;
    }
}
