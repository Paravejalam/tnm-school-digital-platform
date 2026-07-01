<?php

namespace App\Auth;

use PDO;
use Throwable;

class UserRepository implements UserRepositoryInterface
{
    public function __construct(private ?PDO $database = null)
    {
    }

    public function findByEmail(string $email): ?User
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1');
            $statement->execute(['email' => $email]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapUser($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findById(int $id): ?User
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT id, name, email, password_hash FROM users WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapUser($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): User
    {
        if (!$this->database instanceof PDO) {
            return $this->mapUser($attributes);
        }

        try {
            $statement = $this->database->prepare(
                'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
            );
            $statement->execute([
                'name' => $attributes['name'] ?? null,
                'email' => $attributes['email'] ?? null,
                'password_hash' => $attributes['password_hash'] ?? null,
            ]);

            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapUser($attributes);
    }

    private function mapUser(array $row): User
    {
        return new User(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['email'] ?? null,
            $row['password_hash'] ?? null
        );
    }
}