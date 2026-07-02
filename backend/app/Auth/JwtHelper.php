<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * JWT Helper — HMAC-SHA256 Signed Tokens
 *
 * Issues and decodes JWT tokens using HS256.
 * Tokens include iat and exp claims.
 * Tokens are validated for expiry on decode.
 *
 * Authority: .github/AGENT.md Section 24.5 — JWT Security Rules
 *
 * SECURITY:
 * - Algorithm: HS256 (HMAC-SHA256). alg:none is prohibited.
 * - Secret sourced from JWT_SECRET environment variable.
 * - Access token TTL: JWT_ACCESS_TOKEN_TTL (default 900s / 15 minutes).
 * - Tokens must NOT be logged (caller responsibility).
 */
class JwtHelper
{
    private string $secret;
    private int $accessTokenTtl;

    public function __construct()
    {
        $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
        if ($secret === false || $secret === '' || $secret === null) {
            $secret = 'tnm-school-platform-default-secret-change-in-production';
        }
        $this->secret = (string) $secret;

        $ttl = $_ENV['JWT_ACCESS_TOKEN_TTL'] ?? getenv('JWT_ACCESS_TOKEN_TTL');
        $this->accessTokenTtl = ($ttl !== false && $ttl !== null && $ttl !== '')
            ? (int) $ttl
            : 900;
    }

    /**
     * Issue a signed HS256 JWT with iat and exp claims.
     *
     * @param array<string, mixed> $claims
     */
    public function issue(array $claims): string
    {
        $now = time();

        $header = $this->base64UrlEncode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]);

        $payload = $this->base64UrlEncode(array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $this->accessTokenTtl,
        ]));

        $signature = $this->sign($header . '.' . $payload);

        return $header . '.' . $payload . '.' . $signature;
    }

    /**
     * Decode and validate a JWT token.
     * Throws AuthException if the token is malformed, unsigned, expired, or has an invalid signature.
     *
     * @return array<string, mixed>
     * @throws AuthException
     */
    public function decode(string $token): array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new AuthException('Invalid token structure.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;

        // Verify header
        $header = $this->base64UrlDecode($encodedHeader);
        if (!is_array($header) || ($header['alg'] ?? '') !== 'HS256') {
            throw new AuthException('Invalid or unsupported token algorithm.');
        }

        // Verify signature
        $expectedSignature = $this->sign($encodedHeader . '.' . $encodedPayload);
        if (!hash_equals($expectedSignature, $encodedSignature)) {
            throw new AuthException('Token signature verification failed.');
        }

        // Decode payload
        $payload = $this->base64UrlDecode($encodedPayload);
        if (!is_array($payload)) {
            throw new AuthException('Invalid token payload.');
        }

        // Verify expiry
        if (isset($payload['exp']) && time() > (int) $payload['exp']) {
            throw new AuthException('Token has expired.');
        }

        return $payload;
    }

    /**
     * Sign a message using HMAC-SHA256.
     */
    private function sign(string $message): string
    {
        return $this->base64UrlEncodeRaw(
            hash_hmac('sha256', $message, $this->secret, true)
        );
    }

    /**
     * Base64URL-encode an array as JSON.
     *
     * @param array<string, mixed> $payload
     */
    private function base64UrlEncode(array $payload): string
    {
        return $this->base64UrlEncodeRaw((string) json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Base64URL-encode a raw binary/string value.
     */
    private function base64UrlEncodeRaw(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64URL-decode and JSON-parse a segment.
     *
     * @return array<string, mixed>|null
     */
    private function base64UrlDecode(string $payload): mixed
    {
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($decoded === false) {
            return null;
        }

        return json_decode($decoded, true);
    }
}