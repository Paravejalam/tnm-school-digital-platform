<?php

namespace App\Auth;

class AuthValidator
{
    private const MIN_PASSWORD_LENGTH = 8;

    public function validateLogin(array $payload): void
    {
        $errors = $this->validateEmailAndPassword($payload);

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    public function validateRegister(array $payload): void
    {
        $errors = $this->validateEmailAndPassword($payload);
        $password = (string) ($payload['password'] ?? '');
        $confirmPassword = (string) ($payload['confirm_password'] ?? '');

        if ($confirmPassword === '') {
            $errors['confirm_password'][] = 'Confirm password is required.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'][] = 'Confirm password must match password.';
        }

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    public function validate(array $payload): array
    {
        return $this->validateEmailAndPassword($payload);
    }

    private function validateEmailAndPassword(array $payload): array
    {
        $errors = [];
        $email = (string) ($payload['email'] ?? '');
        $password = (string) ($payload['password'] ?? '');

        if ($email === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'Email must be valid.';
        }

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        } elseif (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            $errors['password'][] = 'Password must be at least ' . self::MIN_PASSWORD_LENGTH . ' characters.';
        }

        return $errors;
    }
}
