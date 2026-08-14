<?php

namespace App\User;

use App\Auth\ValidationException;

class UserValidator
{
    public function validateCreate(array $payload): void
    {
        $errors = $this->validateNameEmail($payload);
        $password = (string) ($payload['password'] ?? '');

        if ($password === '') {
            $errors['password'][] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'][] = 'Password must be at least 8 characters.';
        }

        if (array_key_exists('is_active', $payload) && !is_bool($payload['is_active'])) {
            $errors['is_active'][] = 'is_active must be a boolean.';
        }

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    public function validateUpdate(array $payload): void
    {
        $errors = [];

        if (array_key_exists('name', $payload)) {
            $name = (string) $payload['name'];
            if ($name === '') {
                $errors['name'][] = 'Name is required.';
            } elseif (strlen($name) > 200) {
                $errors['name'][] = 'Name must not exceed 200 characters.';
            }
        }

        if (array_key_exists('email', $payload)) {
            $email = (string) $payload['email'];
            if ($email === '') {
                $errors['email'][] = 'Email is required.';
            } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                $errors['email'][] = 'Email must be valid.';
            }
        }

        if (array_key_exists('password', $payload) && $payload['password'] !== '') {
            $password = (string) $payload['password'];
            if (strlen($password) < 8) {
                $errors['password'][] = 'Password must be at least 8 characters.';
            }
        }

        if (array_key_exists('is_active', $payload) && !is_bool($payload['is_active'])) {
            $errors['is_active'][] = 'is_active must be a boolean.';
        }

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    public function validateRoles(UserRepositoryInterface $repository, array $roleIds): void
    {
        foreach ($roleIds as $roleId) {
            if (!$repository->roleExists((int) $roleId)) {
                throw new ValidationException('Invalid role.', ['roles' => ['One or more roles do not exist.']]);
            }
        }
    }

    private function validateNameEmail(array $payload): array
    {
        $errors = [];
        $name = (string) ($payload['name'] ?? '');
        $email = (string) ($payload['email'] ?? '');

        if ($name === '') {
            $errors['name'][] = 'Name is required.';
        } elseif (strlen($name) > 200) {
            $errors['name'][] = 'Name must not exceed 200 characters.';
        }

        if ($email === '') {
            $errors['email'][] = 'Email is required.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'][] = 'Email must be valid.';
        }

        return $errors;
    }
}