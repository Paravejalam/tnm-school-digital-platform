<?php

namespace App\Auth;

use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class AuthController extends BaseController
{
    public function __construct(private ?AuthServiceInterface $authService = null)
    {
        parent::__construct();
    }

    public function login(RequestHelper $request): array
    {
        if (!$this->authService instanceof AuthServiceInterface) {
            return $this->error('Authentication service unavailable.', 503);
        }

        try {
            $authenticatedUser = $this->authService->login(new LoginRequest($this->payload($request)));

            return $this->json($this->formatAuthenticatedUser($authenticatedUser), 200);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AuthException $exception) {
            return $this->error($exception->getMessage(), 401);
        } catch (Throwable) {
            return $this->error('Authentication request failed.', 500);
        }
    }

    public function register(RequestHelper $request): array
    {
        if (!$this->authService instanceof AuthServiceInterface) {
            return $this->error('Authentication service unavailable.', 503);
        }

        try {
            $authenticatedUser = $this->authService->register(new RegisterRequest($this->payload($request)));

            return $this->json($this->formatAuthenticatedUser($authenticatedUser), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AuthException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Registration request failed.', 500);
        }
    }

    public function logout(RequestHelper $request): array
    {
        if (!$this->authService instanceof AuthServiceInterface) {
            return $this->error('Authentication service unavailable.', 503);
        }

        try {
            $this->authService->logout($this->token($request));

            return $this->json(['message' => 'Logged out successfully.'], 200);
        } catch (AuthException $exception) {
            return $this->error($exception->getMessage(), 401);
        } catch (Throwable) {
            return $this->error('Logout request failed.', 500);
        }
    }

    private function payload(RequestHelper $request): array
    {
        $json = $request->json();
        if (is_array($json)) {
            return $json;
        }

        $requestData = $request->all();

        return is_array($requestData['body'] ?? null) ? $requestData['body'] : [];
    }

    private function token(RequestHelper $request): ?string
    {
        $payload = $this->payload($request);
        if (isset($payload['token']) && is_string($payload['token'])) {
            return $payload['token'];
        }

        $requestData = $request->all();
        $headers = $requestData['headers'] ?? [];
        if (!is_array($headers)) {
            return null;
        }

        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'authorization' && is_string($value)) {
                return str_starts_with($value, 'Bearer ') ? substr($value, 7) : $value;
            }
        }

        return null;
    }

    private function formatAuthenticatedUser(AuthenticatedUser $authenticatedUser): array
    {
        $user = $authenticatedUser->user();

        return [
            'user' => $user instanceof User ? [
                'id' => $user->id(),
                'name' => $user->name(),
                'email' => $user->email(),
            ] : null,
            'token' => $authenticatedUser->token(),
        ];
    }
}
