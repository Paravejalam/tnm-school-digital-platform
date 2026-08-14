<?php

declare(strict_types=1);

/**
 * Unit tests for the Auth self-service endpoints.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/AuthTest.php
 *
 * Covers:
 * - ChangePasswordRequest current/new/confirm password parsing
 * - AuthValidator change-password validation
 * - AuthService profile flow (via fake repository + real JWT)
 * - AuthService change-password flow (current check, update, differ guard)
 * - AuthRepository updatePassword delegation (SQLite-backed)
 * - No-DB graceful degradation
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\Auth\AuthException;
use App\Auth\AuthRepository;
use App\Auth\AuthRepositoryInterface;
use App\Auth\AuthService;
use App\Auth\AuthValidator;
use App\Auth\ChangePasswordRequest;
use App\Auth\JwtHelper;
use App\Auth\PasswordHasher;
use App\Auth\TokenRepository;
use App\Auth\User;
use App\Auth\UserRepository;
use App\Auth\ValidationException;

final class AuthTest
{
    private static int $assertions = 0;
    private static int $failures = 0;

    public static function run(): void
    {
        self::withEnv('APP_ENV', 'local', function (): void {
            self::testChangePasswordRequestParsesPayload();
            self::testValidatorAcceptsValidChangePassword();
            self::testValidatorRejectsMissingCurrentPassword();
            self::testValidatorRejectsShortNewPassword();
            self::testValidatorRejectsMismatchedConfirm();
            self::testServiceProfileReturnsUser();
            self::testServiceProfileRejectsMissingToken();
            self::testServiceProfileRejectsInvalidToken();
            self::testServiceChangePasswordUpdatesHash();
            self::testServiceChangePasswordRejectsWrongCurrent();
            self::testServiceChangePasswordRejectsSamePassword();
            self::testServiceChangePasswordRequiresToken();
            self::testRepositoryUpdatePasswordDelegates();
            self::testRepositoryNoDatabaseDegradesGracefully();
            self::testJwtHelperAcceptsLocalDefaultSecret();
            self::testJwtHelperRejectsMalformedToken();
            self::testJwtHelperRejectsInvalidSignature();
            self::testJwtHelperRejectsExpiredToken();
        });

        self::testJwtHelperRejectsMissingSecretInProduction();

        echo PHP_EOL;
        echo sprintf("Assertions: %d, Failures: %d%s", self::$assertions, self::$failures, PHP_EOL);
        exit(self::$failures === 0 ? 0 : 1);
    }

    private static function testChangePasswordRequestParsesPayload(): void
    {
        $request = new ChangePasswordRequest([
            'current_password' => 'old-pass',
            'new_password' => 'new-pass-123',
            'confirm_password' => 'new-pass-123',
        ]);

        self::assertSame('old-pass', $request->currentPassword(), 'current password');
        self::assertSame('new-pass-123', $request->newPassword(), 'new password');
        self::assertSame('new-pass-123', $request->confirmPassword(), 'confirm password');

        $empty = new ChangePasswordRequest([]);
        self::assertSame(null, $empty->currentPassword(), 'missing current password');
        self::assertSame(null, $empty->newPassword(), 'missing new password');
    }

    private static function testValidatorAcceptsValidChangePassword(): void
    {
        $validator = new AuthValidator();
        $validator->validateChangePassword([
            'current_password' => 'old-password',
            'new_password' => 'new-password-1',
            'confirm_password' => 'new-password-1',
        ]);

        self::pass('valid change-password payload accepted');
    }

    private static function testValidatorRejectsMissingCurrentPassword(): void
    {
        $validator = new AuthValidator();

        try {
            $validator->validateChangePassword([
                'new_password' => 'new-password-1',
                'confirm_password' => 'new-password-1',
            ]);
            self::fail('expected ValidationException for missing current password');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['current_password']), 'current_password error present');
        }
    }

    private static function testValidatorRejectsShortNewPassword(): void
    {
        $validator = new AuthValidator();

        try {
            $validator->validateChangePassword([
                'current_password' => 'old-password',
                'new_password' => 'short',
                'confirm_password' => 'short',
            ]);
            self::fail('expected ValidationException for short new password');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['new_password']), 'new_password error present');
        }
    }

    private static function testValidatorRejectsMismatchedConfirm(): void
    {
        $validator = new AuthValidator();

        try {
            $validator->validateChangePassword([
                'current_password' => 'old-password',
                'new_password' => 'new-password-1',
                'confirm_password' => 'different-1',
            ]);
            self::fail('expected ValidationException for mismatched confirm password');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['confirm_password']), 'confirm_password error present');
        }
    }

    private static function testServiceProfileReturnsUser(): void
    {
        $jwt = new JwtHelper();
        $token = $jwt->issue(['id' => 1, 'email' => 'a@b.c']);
        $service = self::service(new FakeAuthRepository());

        $user = $service->profile($token);

        self::assertTrue($user instanceof User, 'returns user entity');
        if ($user instanceof User) {
            self::assertSame(1, $user->id(), 'user id');
            self::assertSame('a@b.c', $user->email(), 'user email');
        }
    }

    private static function testServiceProfileRejectsMissingToken(): void
    {
        $service = self::service(new FakeAuthRepository());

        try {
            $service->profile(null);
            self::fail('expected AuthException for missing token');
        } catch (AuthException $exception) {
            self::pass('missing token rejected');
        }
    }

    private static function testServiceProfileRejectsInvalidToken(): void
    {
        $service = self::service(new FakeAuthRepository());

        try {
            $service->profile('not-a-real-token');
            self::fail('expected AuthException for invalid token');
        } catch (AuthException $exception) {
            self::pass('invalid token rejected');
        }
    }

    private static function testServiceChangePasswordUpdatesHash(): void
    {
        $repository = new FakeAuthRepository();
        $service = self::service($repository);
        $jwt = new JwtHelper();
        $token = $jwt->issue(['id' => 1, 'email' => 'a@b.c']);

        $service->changePassword(new ChangePasswordRequest([
            'current_password' => 'current-password-1',
            'new_password' => 'new-password-1',
            'confirm_password' => 'new-password-1',
        ]), $token);

        self::assertTrue($repository->updatedPassword !== null, 'password hash set');
        self::assertTrue(password_verify('new-password-1', (string) $repository->updatedPassword), 'password hash matches new password');
    }

    private static function testServiceChangePasswordRejectsWrongCurrent(): void
    {
        $service = self::service(new FakeAuthRepository());
        $jwt = new JwtHelper();
        $token = $jwt->issue(['id' => 1, 'email' => 'a@b.c']);

        try {
            $service->changePassword(new ChangePasswordRequest([
                'current_password' => 'wrong-password',
                'new_password' => 'new-password-1',
                'confirm_password' => 'new-password-1',
            ]), $token);
            self::fail('expected AuthException for wrong current password');
        } catch (AuthException $exception) {
            self::pass('wrong current password rejected');
        }
    }

    private static function testServiceChangePasswordRejectsSamePassword(): void
    {
        $service = self::service(new FakeAuthRepository());
        $jwt = new JwtHelper();
        $token = $jwt->issue(['id' => 1, 'email' => 'a@b.c']);

        try {
            $service->changePassword(new ChangePasswordRequest([
                'current_password' => 'current-password-1',
                'new_password' => 'current-password-1',
                'confirm_password' => 'current-password-1',
            ]), $token);
            self::fail('expected AuthException for unchanged password');
        } catch (AuthException $exception) {
            self::pass('unchanged password rejected');
        }
    }

    private static function testServiceChangePasswordRequiresToken(): void
    {
        $service = self::service(new FakeAuthRepository());

        try {
            $service->changePassword(new ChangePasswordRequest([
                'current_password' => 'current-password-1',
                'new_password' => 'new-password-1',
                'confirm_password' => 'new-password-1',
            ]), null);
            self::fail('expected AuthException for missing token');
        } catch (AuthException $exception) {
            self::pass('change-password missing token rejected');
        }
    }

    private static function testRepositoryUpdatePasswordDelegates(): void
    {
        $pdo = self::sqlite();
        $users = new UserRepository($pdo);
        $tokens = new TokenRepository($pdo);
        $repository = new AuthRepository($users, $tokens);

        $users->create(['name' => 'A', 'email' => 'a@b.c', 'password_hash' => 'old-hash']);

        $repository->updatePassword(1, 'new-hash');

        $updated = $users->findById(1);
        self::assertTrue($updated instanceof User, 'user found after update');
        if ($updated instanceof User) {
            self::assertSame('new-hash', $updated->passwordHash(), 'password hash updated');
        }
    }

    private static function testRepositoryNoDatabaseDegradesGracefully(): void
    {
        $users = new UserRepository(null);
        $tokens = new TokenRepository(null);
        $repository = new AuthRepository($users, $tokens);

        $repository->updatePassword(1, 'new-hash');

        self::pass('updatePassword no-db no-op');
    }

    private static function testJwtHelperAcceptsLocalDefaultSecret(): void
    {
        self::withEnv('APP_ENV', 'local', function (): void {
            self::withEnv('JWT_SECRET', null, function (): void {
                $jwt = new JwtHelper();
                $token = $jwt->issue(['id' => 42, 'type' => 'access']);
                $payload = $jwt->decode($token);

                self::assertSame(42, (int) $payload['id'], 'valid access token accepted');
            });
        });
    }

    private static function testJwtHelperRejectsMissingSecretInProduction(): void
    {
        self::withEnv('APP_ENV', 'production', function (): void {
            self::withEnv('JWT_SECRET', null, function (): void {
                try {
                    new JwtHelper();
                    self::fail('expected RuntimeException for missing JWT secret in production');
                } catch (RuntimeException $exception) {
                    self::assertTrue(str_contains($exception->getMessage(), 'JWT_SECRET'), 'production requires configured JWT secret');
                }
            });
        });
    }

    private static function testJwtHelperRejectsMalformedToken(): void
    {
        $jwt = new JwtHelper();

        try {
            $jwt->decode('not-a-token');
            self::fail('expected AuthException for malformed token');
        } catch (AuthException $exception) {
            self::pass('malformed token rejected');
        }
    }

    private static function testJwtHelperRejectsInvalidSignature(): void
    {
        $jwt = new JwtHelper();
        $token = $jwt->issue(['id' => 1, 'type' => 'access']);
        $tampered = substr($token, 0, -1) . 'A';

        try {
            $jwt->decode($tampered);
            self::fail('expected AuthException for invalid signature');
        } catch (AuthException $exception) {
            self::pass('invalid signature rejected');
        }
    }

    private static function testJwtHelperRejectsExpiredToken(): void
    {
        $jwt = new JwtHelper();
        $expired = $jwt->issue(['id' => 1, 'type' => 'access'], -1);

        try {
            $jwt->decode($expired);
            self::fail('expected AuthException for expired token');
        } catch (AuthException $exception) {
            self::pass('expired token rejected');
        }
    }

    private static function withEnv(string $key, mixed $value, callable $callback): mixed
    {
        $previous = $_ENV[$key] ?? getenv($key);
        if ($value === null) {
            unset($_ENV[$key]);
            putenv($key);
        } else {
            $_ENV[$key] = (string) $value;
            putenv($key . '=' . (string) $value);
        }

        try {
            return $callback();
        } finally {
            if ($previous === false || $previous === null) {
                unset($_ENV[$key]);
                putenv($key);
            } else {
                $_ENV[$key] = (string) $previous;
                putenv($key . '=' . (string) $previous);
            }
        }
    }

    private static function service(FakeAuthRepository $repository): AuthService
    {
        return new AuthService(
            new PasswordHasher(),
            new JwtHelper(),
            new AuthValidator(),
            $repository
        );
    }

    private static function sqlite(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(200) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );
        $pdo->exec(
            'CREATE TABLE user_roles (
                user_id INTEGER NOT NULL,
                role_id INTEGER NOT NULL
            )'
        );

        return $pdo;
    }

    private static function assertSame(mixed $expected, mixed $actual, string $label): void
    {
        if ($expected === $actual) {
            self::pass($label);
        } else {
            self::fail($label . ' — expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
        }
    }

    private static function assertTrue(mixed $actual, string $label): void
    {
        if ($actual === true) {
            self::pass($label);
        } else {
            self::fail($label . ' — expected true got ' . var_export($actual, true));
        }
    }

    private static function pass(string $label): void
    {
        self::$assertions++;
        echo "PASS: {$label}" . PHP_EOL;
    }

    private static function fail(string $label): void
    {
        self::$assertions++;
        self::$failures++;
        echo "FAIL: {$label}" . PHP_EOL;
    }
}

final class FakeAuthRepository implements AuthRepositoryInterface
{
    public ?string $updatedPassword = null;

    private array $user = [
        'id' => 1,
        'name' => 'Test User',
        'email' => 'a@b.c',
        'password_hash' => null,
    ];

    public function __construct()
    {
        $this->user['password_hash'] = password_hash('current-password-1', PASSWORD_DEFAULT);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->map($this->user);
    }

    public function findById(int $id): ?User
    {
        return $this->map($this->user);
    }

    public function create(array $attributes): User
    {
        $this->user = array_merge($this->user, $attributes);

        return $this->map($this->user);
    }

    public function storeToken(User $user, string $token): void
    {
    }

    public function revokeToken(string $token): void
    {
    }

    public function assignRole(int $userId, int $roleId): void
    {
    }

    public function findUserRole(int $userId): ?string
    {
        return 'admin';
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->updatedPassword = $passwordHash;
    }

    private function map(array $row): User
    {
        return new User(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['email'] ?? null,
            $row['password_hash'] ?? null
        );
    }
}

AuthTest::run();
