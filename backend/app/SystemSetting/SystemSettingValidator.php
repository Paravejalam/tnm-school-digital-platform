<?php

namespace App\SystemSetting;

use App\Auth\ValidationException;

class SystemSettingValidator
{
    private const TYPES = ['string', 'integer', 'boolean', 'json'];

    public function validateUpdate(array $payload): void
    {
        $errors = [];

        if (array_key_exists('type', $payload) && !$this->validType($payload['type'])) {
            $errors['type'][] = 'Type must be string, integer, boolean or json.';
        }

        if (array_key_exists('is_public', $payload) && !$this->validBoolean($payload['is_public'])) {
            $errors['is_public'][] = 'is_public must be true or false.';
        }

        if (array_key_exists('module', $payload) && trim((string) $payload['module']) === '') {
            $errors['module'][] = 'Module cannot be empty.';
        }

        if ($errors !== []) {
            throw new ValidationException(errors: $errors);
        }
    }

    public function validateValueAgainstType(array $payload): void
    {
        if (!array_key_exists('value', $payload)) {
            return;
        }

        $type = isset($payload['type']) && $this->validType($payload['type'])
            ? (string) $payload['type']
            : 'string';
        $value = $payload['value'];

        $valid = match ($type) {
            'integer' => $this->validInteger($value),
            'boolean' => $this->validBoolean($value),
            'json' => $this->validJson($value),
            default => true,
        };

        if (!$valid) {
            throw new ValidationException(errors: ['value' => ['Value must be a valid ' . $type . '.']]);
        }
    }

    public function normalizeValue(array $payload): array
    {
        if (!array_key_exists('value', $payload)) {
            return $payload;
        }

        $type = isset($payload['type']) && $this->validType($payload['type'])
            ? (string) $payload['type']
            : 'string';

        $normalized = $payload;

        $normalized['value'] = match ($type) {
            'integer' => (string) (int) $payload['value'],
            'boolean' => $this->normalizeBoolean($payload['value']) ? '1' : '0',
            'json' => is_string($payload['value']) ? $payload['value'] : json_encode($payload['value'], JSON_UNESCAPED_UNICODE),
            default => (string) $payload['value'],
        };

        return $normalized;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower((string) $value);

        if (in_array($normalized, ['0', 'false'], true)) {
            return false;
        }

        return in_array($normalized, ['1', 'true'], true);
    }

    private function validType(mixed $type): bool
    {
        return in_array((string) $type, self::TYPES, true);
    }

    private function validInteger(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return true;
        }

        return in_array((string) $value, ['0', '1', 'true', 'false'], true);
    }

    private function validJson(mixed $value): bool
    {
        if (is_array($value) || is_object($value)) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
