<?php

namespace App\Auth;

class JwtHelper
{
    public function issue(array $claims): string
    {
        $header = $this->base64UrlEncode([
            'alg' => 'none',
            'typ' => 'JWT',
        ]);

        $payload = $this->base64UrlEncode(array_merge($claims, [
            'iat' => time(),
        ]));

        return $header . '.' . $payload . '.';
    }

    public function decode(string $token): array
    {
        $segments = explode('.', $token);
        if (count($segments) < 2) {
            throw new AuthException('Invalid token structure.');
        }

        $payload = $this->base64UrlDecode($segments[1]);
        if (!is_array($payload)) {
            throw new AuthException('Invalid token payload.');
        }

        return $payload;
    }

    private function base64UrlEncode(array $payload): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($payload)), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $payload): mixed
    {
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($decoded === false) {
            return null;
        }

        return json_decode($decoded, true);
    }
}
