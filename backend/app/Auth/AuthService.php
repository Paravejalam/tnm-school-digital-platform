<?php

namespace App\Auth;

use PDO;
use Throwable;

class AuthService implements AuthServiceInterface
{
    private const DEFAULT_ROLE_ID = 4;
    private const DEFAULT_ROLE_SLUG = 'student';

    public function __construct(
        private PasswordHasher $passwordHasher,
        private JwtHelper $jwtHelper,
        private AuthValidator $validator,
        private AuthRepositoryInterface $authRepository,
        private ?PDO $database = null,
        private ?RefreshTokenRepositoryInterface $refreshTokenRepository = null
    ) {
    }

    public function login(LoginRequest $request): AuthenticatedUser
    {
        $payload = $request->payload();
        $this->validator->validateLogin($payload);

        $credentials = $request->credentials();
        $user = $this->authRepository->findByEmail((string) $credentials->email());

        if (!$user instanceof User || !$this->passwordHasher->verify((string) $credentials->password(), (string) $user->passwordHash())) {
            throw new AuthException('Invalid credentials.');
        }

        $role = $this->authRepository->findUserRole((int) $user->id());

        $token = $this->jwtHelper->issue([
            'id'    => $user->id(),
            'email' => $user->email(),
            'type'  => 'login',
            'role'  => $role,
        ]);
        $this->authRepository->storeToken($user, $token);

        $refreshToken = $this->issueRefreshToken($user);

        return new AuthenticatedUser($user, $token, $refreshToken);
    }

    public function register(RegisterRequest $request): AuthenticatedUser
    {
        $payload = $request->payload();
        $this->validator->validateRegister($payload);

        $credentials = $request->credentials();
        $existingUser = $this->authRepository->findByEmail((string) $credentials->email());

        if ($existingUser instanceof User) {
            throw new AuthException('Email is already registered.');
        }

        if (!$this->database instanceof PDO) {
            throw new AuthException('Database connection unavailable.');
        }

        $this->database->beginTransaction();

        try {
            $user = $this->authRepository->create([
                'name'          => $payload['name'] ?? null,
                'email'         => $credentials->email(),
                'password_hash' => $this->passwordHasher->hash((string) $credentials->password()),
            ]);

            $this->authRepository->assignRole((int) $user->id(), self::DEFAULT_ROLE_ID);

            $token = $this->jwtHelper->issue([
                'id'    => $user->id(),
                'email' => $user->email(),
                'type'  => 'register',
                'role'  => self::DEFAULT_ROLE_SLUG,
            ]);

            $this->authRepository->storeToken($user, $token);

            $refreshToken = $this->issueRefreshToken($user);

            $this->database->commit();

            return new AuthenticatedUser($user, $token, $refreshToken);
        } catch (Throwable $e) {
            $this->database->rollBack();

            throw new AuthException('Registration failed.', 0, $e);
        }
    }

    public function refresh(string $refreshToken): AuthenticatedUser
    {
        if (!$this->refreshTokenRepository instanceof RefreshTokenRepositoryInterface) {
            throw new AuthException('Refresh token service unavailable.');
        }

        try {
            $payload = $this->jwtHelper->decode($refreshToken);
        } catch (Throwable) {
            throw new AuthException('Invalid refresh token.');
        }

        if (($payload['type'] ?? '') !== 'refresh') {
            throw new AuthException('Invalid refresh token.');
        }

        $stored = $this->refreshTokenRepository->find($refreshToken);

        if (!is_array($stored)) {
            throw new AuthException('Refresh token not found.');
        }

        if ((int) ($stored['revoked'] ?? 0) === 1) {
            $this->refreshTokenRepository->revokeAllForUser((int) ($stored['user_id'] ?? 0));

            throw new AuthException('Refresh token has been revoked.');
        }

        if (isset($stored['expires_at']) && $stored['expires_at'] !== null) {
            $expiresAt = strtotime((string) $stored['expires_at']);
            if ($expiresAt !== false && $expiresAt < time()) {
                $this->refreshTokenRepository->revoke($refreshToken);

                throw new AuthException('Refresh token has expired.');
            }
        }

        if (!$this->database instanceof PDO) {
            throw new AuthException('Database connection unavailable.');
        }

        $this->database->beginTransaction();

        try {
            $this->refreshTokenRepository->revoke($refreshToken);

            $userId = (int) $payload['id'];
            $user = $this->authRepository->findById($userId);
            if (!$user instanceof User) {
                throw new AuthException('User not found.');
            }

            $role = $this->authRepository->findUserRole($userId);

            $newAccessToken = $this->jwtHelper->issue([
                'id'    => $user->id(),
                'email' => $user->email(),
                'type'  => 'access',
                'role'  => $role,
            ]);
            $this->authRepository->storeToken($user, $newAccessToken);

            $newRefreshToken = $this->issueRefreshToken($user);

            $this->database->commit();

            return new AuthenticatedUser($user, $newAccessToken, $newRefreshToken);
        } catch (Throwable $e) {
            $this->database->rollBack();

            throw new AuthException('Token refresh failed.', 0, $e);
        }
    }

    public function logout(?string $token = null): void
    {
        if ($token !== null && $token !== '') {
            $this->authRepository->revokeToken($token);

            if ($this->refreshTokenRepository instanceof RefreshTokenRepositoryInterface) {
                try {
                    $payload = $this->jwtHelper->decode($token);
                    $userId = isset($payload['id']) ? (int) $payload['id'] : 0;
                    if ($userId > 0) {
                        $this->refreshTokenRepository->revokeAllForUser($userId);
                    }
                } catch (Throwable) {
                }
            }
        }
    }

    private function issueRefreshToken(User $user): string
    {
        $refreshToken = $this->jwtHelper->issue([
            'id'    => $user->id(),
            'email' => $user->email(),
            'type'  => 'refresh',
        ], $this->jwtHelper->refreshTokenTtl());

        if ($this->refreshTokenRepository instanceof RefreshTokenRepositoryInterface) {
            $this->refreshTokenRepository->store($user, $refreshToken, $this->jwtHelper->refreshTokenTtl());
        }

        return $refreshToken;
    }
}
