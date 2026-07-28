<?php

namespace App\Auth;

use PDO;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(private ?PDO $database = null)
    {
    }

    public function store(User $user, string $token, int $ttlSeconds): void
    {
        if (!$this->database instanceof PDO) {
            return;
        }

        $statement = $this->database->prepare(
            'INSERT INTO auth_tokens (user_id, token, revoked, expires_at, created_at)
             VALUES (:user_id, :token, 0, DATE_ADD(NOW(), INTERVAL :ttl SECOND), NOW())'
        );
        $statement->execute([
            'user_id' => $user->id(),
            'token'   => $token,
            'ttl'     => $ttlSeconds,
        ]);
    }

    public function find(string $token): ?array
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        $statement = $this->database->prepare(
            'SELECT id, user_id, token, revoked, expires_at
             FROM auth_tokens
             WHERE token = :token
             LIMIT 1'
        );
        $statement->execute(['token' => $token]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function revoke(string $token): void
    {
        if (!$this->database instanceof PDO) {
            return;
        }

        $statement = $this->database->prepare(
            'UPDATE auth_tokens SET revoked = 1 WHERE token = :token'
        );
        $statement->execute(['token' => $token]);
    }

    public function revokeAllForUser(int $userId): void
    {
        if (!$this->database instanceof PDO) {
            return;
        }

        $statement = $this->database->prepare(
            'UPDATE auth_tokens SET revoked = 1 WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
    }
}
