<?php

namespace App\Auth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private PasswordHasher $passwordHasher,
        private JwtHelper $jwtHelper,
        private AuthValidator $validator,
        private AuthRepositoryInterface $authRepository
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

        $token = $this->jwtHelper->issue([
            'id' => $user->id(),
            'email' => $user->email(),
            'type' => 'login',
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

        $user = $this->authRepository->create([
            'name' => $payload['name'] ?? null,
            'email' => $credentials->email(),
            'password_hash' => $this->passwordHasher->hash((string) $credentials->password()),
        ]);
        $token = $this->jwtHelper->issue([
            'id' => $user->id(),
            'email' => $user->email(),
            'type' => 'register',
        ]);
        $this->authRepository->storeToken($user, $token);

        return new AuthenticatedUser($user, $token);
    }

    public function logout(?string $token = null): void
    {
        if ($token !== null && $token !== '') {
            $this->authRepository->revokeToken($token);
        }
    }
}