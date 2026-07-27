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
        private ?PDO $database = null
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

        return new AuthenticatedUser($user, $token);
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

            $this->database->commit();

            return new AuthenticatedUser($user, $token);
        } catch (Throwable $e) {
            $this->database->rollBack();

            throw new AuthException('Registration failed.', 0, $e);
        }
    }

    public function logout(?string $token = null): void
    {
        if ($token !== null && $token !== '') {
            $this->authRepository->revokeToken($token);
        }
    }
}
