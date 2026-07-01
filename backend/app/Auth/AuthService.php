<?php

namespace App\Auth;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private PasswordHasher $passwordHasher,
        private JwtHelper $jwtHelper,
        private AuthValidator $validator
    ) {
    }

    public function login(LoginRequest $request): AuthenticatedUser
    {
        $payload = $request->payload();
        $this->validator->validateLogin($payload);

        $credentials = $request->credentials();
        $user = new User(email: $credentials->email());
        $token = $this->jwtHelper->issue([
            'email' => $credentials->email(),
            'type' => 'login',
        ]);

        return new AuthenticatedUser($user, $token);
    }

    public function register(RegisterRequest $request): AuthenticatedUser
    {
        $payload = $request->payload();
        $this->validator->validateRegister($payload);

        $credentials = $request->credentials();
        $user = new User(
            name: $payload['name'] ?? null,
            email: $credentials->email()
        );
        $token = $this->jwtHelper->issue([
            'email' => $credentials->email(),
            'type' => 'register',
        ]);

        return new AuthenticatedUser($user, $token);
    }

    public function logout(?string $token = null): void
    {
    }
}
