<?php

namespace App\Auth;

use PDO;
use Throwable;

class TokenRepository implements TokenRepositoryInterface
{
    public function __construct(private ?PDO $database = null)
    {
    }

    public function storeToken(User $user, string $token): void
    {
        if (!$this->database instanceof PDO) {
            return;
        }

        try {
            $statement = $this->database->prepare(
                'INSERT INTO auth_tokens (user_id, token, revoked, created_at) VALUES (:user_id, :token, 0, NOW())'
            );
            $statement->execute([
                'user_id' => $user->id(),
                'token' => $token,
            ]);
        } catch (Throwable) {
        }
    }

    public function revokeToken(string $token): void
    {
        if (!$this->database instanceof PDO) {
            return;
        }

        try {
            $statement = $this->database->prepare('UPDATE auth_tokens SET revoked = 1 WHERE token = :token');
            $statement->execute(['token' => $token]);
        } catch (Throwable) {
        }
    }
}