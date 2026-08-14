<?php

declare(strict_types=1);

/**
 * Unit tests for the User management module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/UserTest.php
 *
 * Covers:
 * - UserValidator create/update/roles validation
 * - UserResponse casting and role slugs
 * - UserListRequest pagination, search and is_active filters
 * - CreateUserRequest/UpdateUserRequest role id parsing
 * - UserService create/update/delete flow (via fake repository)
 * - UserRepository paginate/find/soft-delete/role flows (SQLite-backed)
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\Auth\ValidationException;
use App\User\CreateUserRequest;
use App\User\UpdateUserRequest;
use App\User\User;
use App\User\UserListRequest;
use App\User\UserRepository;
use App\User\UserRepositoryInterface;
use App\User\UserResponse;
use App\User\UserService;
use App\User\UserValidator;

final class UserTest
{
    private static int $assertions = 0;
    private static int $failures = 0;

    public static function run(): void
    {
        self::testValidatorAcceptsValidCreate();
        self::testValidatorRejectsInvalidCreate();
        self::testValidatorAcceptsValidUpdate();
        self::testValidatorRejectsInvalidUpdate();
        self::testValidatorRejectsUnknownRoles();
        self::testListRequestParsesQuery();
        self::testResponseOmitsPasswordAndListsRoles();
        self::testCreateRequestParsesRoles();
        self::testServiceCreateHashesPasswordAndPersists();
        self::testServiceCreateRejectsDuplicateEmail();
        self::testServiceUpdateNotFoundReturnsNull();
        self::testServiceUpdatePersistsChanges();
        self::testServiceDeleteNotFoundReturnsFalse();
        self::testRepositoryNoDatabaseDegradesGracefully();
        self::testRepositorySqliteCrudAndRoles();

        echo PHP_EOL;
        echo sprintf("Assertions: %d, Failures: %d%s", self::$assertions, self::$failures, PHP_EOL);
        exit(self::$failures === 0 ? 0 : 1);
    }

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new UserValidator();

        $valid = [
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'secret123'],
            ['name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'secret123', 'is_active' => false],
        ];

        foreach ($valid as $payload) {
            try {
                $validator->validateCreate($payload);
                self::pass('accepts create ' . json_encode($payload));
            } catch (ValidationException $e) {
                self::fail('should accept create ' . json_encode($payload) . ' got ' . json_encode($e->errors()));
            }
        }
    }

    private static function testValidatorRejectsInvalidCreate(): void
    {
        $validator = new UserValidator();

        $invalid = [
            ['email' => 'john@example.com', 'password' => 'secret123'],
            ['name' => 'John', 'password' => 'secret123'],
            ['name' => 'John', 'email' => 'not-an-email', 'password' => 'secret123'],
            ['name' => 'John', 'email' => 'john@example.com'],
            ['name' => 'John', 'email' => 'john@example.com', 'password' => 'short'],
            ['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret123', 'is_active' => 'yes'],
        ];

        foreach ($invalid as $payload) {
            try {
                $validator->validateCreate($payload);
                self::fail('should reject create ' . json_encode($payload));
            } catch (ValidationException) {
                self::pass('rejects create ' . json_encode($payload));
            }
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new UserValidator();

        try {
            $validator->validateUpdate(['name' => 'New Name']);
            $validator->validateUpdate(['email' => 'new@example.com']);
            $validator->validateUpdate(['is_active' => false]);
            $validator->validateUpdate(['password' => 'newsecret1']);
            self::pass('accepts valid partial updates');
        } catch (ValidationException $e) {
            self::fail('should accept partial updates got ' . json_encode($e->errors()));
        }
    }

    private static function testValidatorRejectsInvalidUpdate(): void
    {
        $validator = new UserValidator();

        $invalid = [
            ['email' => 'bad'],
            ['password' => 'short'],
            ['is_active' => '1'],
        ];

        foreach ($invalid as $payload) {
            try {
                $validator->validateUpdate($payload);
                self::fail('should reject update ' . json_encode($payload));
            } catch (ValidationException) {
                self::pass('rejects update ' . json_encode($payload));
            }
        }
    }

    private static function testValidatorRejectsUnknownRoles(): void
    {
        $validator = new UserValidator();
        $repository = new FakeUserRepository();

        try {
            $validator->validateRoles($repository, [999]);
            self::fail('should reject unknown role');
        } catch (ValidationException) {
            self::pass('rejects unknown role');
        }
    }

    private static function testListRequestParsesQuery(): void
    {
        $request = new UserListRequest([
            'page' => 2,
            'per_page' => 50,
            'search' => 'john',
            'is_active' => 'true',
        ]);

        self::assertSame(2, $request->page(), 'page');
        self::assertSame(50, $request->perPage(), 'per_page');
        self::assertSame('john', $request->search(), 'search');
        self::assertSame(true, $request->isActive(), 'is_active');

        $default = new UserListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search');
        self::assertSame(null, $default->isActive(), 'default is_active');
    }

    private static function testResponseOmitsPasswordAndListsRoles(): void
    {
        $user = new User(1, 'John', 'john@example.com', 'hash', true, null, '2026-01-01', '2026-01-02', [2], ['admin']);

        $data = UserResponse::fromEntity($user);

        self::assertSame(1, $data['id'], 'id');
        self::assertSame('john@example.com', $data['email'], 'email');
        self::assertSame(true, $data['is_active'], 'is_active');
        self::assertSame(['admin'], $data['roles'], 'roles');
        self::assertSame(false, array_key_exists('password_hash', $data), 'password omitted');
        self::assertSame(false, array_key_exists('passwordHash', $data), 'password variant omitted');
    }

    private static function testCreateRequestParsesRoles(): void
    {
        $request = new CreateUserRequest(['name' => 'A', 'roles' => ['2', 3, '2', 'x']]);

        self::assertSame([2, 3, 0], $request->roleIds(), 'role ids parsed and int cast');
    }

    private static function testServiceCreateHashesPasswordAndPersists(): void
    {
        $repository = new FakeUserRepository();
        $service = new UserService($repository, new UserValidator());

        $user = $service->create(new CreateUserRequest([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ]));

        self::assertTrue($user instanceof User, 'returns entity');
        if ($user instanceof User) {
            self::assertSame('alice@example.com', $user->email(), 'email persisted');
            self::assertNotSame('secret123', $user->passwordHash(), 'password not stored in plain text');
            self::assertTrue(password_verify('secret123', $user->passwordHash() ?? ''), 'password hashed with bcrypt');
        }
    }

    private static function testServiceCreateRejectsDuplicateEmail(): void
    {
        $repository = new FakeUserRepository();
        $service = new UserService($repository, new UserValidator());

        try {
            $service->create(new CreateUserRequest([
                'name' => 'Duplicate',
                'email' => 'existing@example.com',
                'password' => 'secret123',
            ]));
            self::fail('should reject duplicate email');
        } catch (ValidationException) {
            self::pass('rejects duplicate email');
        }
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = new UserService(new FakeUserRepository(), new UserValidator());

        self::assertSame(null, $service->update(new UpdateUserRequest(999, ['name' => 'X'])), 'missing user returns null');
    }

    private static function testServiceUpdatePersistsChanges(): void
    {
        $repository = new FakeUserRepository();
        $service = new UserService($repository, new UserValidator());

        $user = $service->update(new UpdateUserRequest(1, ['name' => 'Renamed']));

        self::assertTrue($user instanceof User, 'returns entity');
        if ($user instanceof User) {
            self::assertSame('Renamed', $user->name(), 'name updated');
            self::assertSame('existing@example.com', $user->email(), 'email retained');
        }
    }

    private static function testServiceDeleteNotFoundReturnsFalse(): void
    {
        $service = new UserService(new FakeUserRepository(), new UserValidator());

        self::assertSame(false, $service->delete(999), 'missing user delete returns false');
    }

    private static function testRepositoryNoDatabaseDegradesGracefully(): void
    {
        $repository = new UserRepository(null);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repository->paginate(1, 15), 'no db paginate');
        self::assertSame(null, $repository->findById(1), 'no db findById');
        self::assertSame(false, $repository->softDelete(1), 'no db softDelete');
        self::assertSame([], $repository->findRoleIds(1), 'no db findRoleIds');
    }

    private static function testRepositorySqliteCrudAndRoles(): void
    {
        $pdo = self::sqlite();
        $repository = new UserRepository($pdo);

        $created = $repository->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password_hash' => 'hash',
            'is_active' => true,
        ]);

        self::assertTrue($created instanceof User, 'create returns entity');
        self::assertTrue((int) $created->id() > 0, 'create assigns id');

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof User, 'finds created user');
        if ($found instanceof User) {
            self::assertSame('test@example.com', $found->email(), 'email');
            self::assertSame(true, $found->isActive(), 'is_active');
        }

        $repository->assignRole((int) $created->id(), 1);
        $repository->assignRole((int) $created->id(), 2);
        self::assertSame([1, 2], $repository->findRoleIds((int) $created->id()), 'role ids after assignment');

        $repository->replaceRoles((int) $created->id(), [2]);
        self::assertSame([2], $repository->findRoleIds((int) $created->id()), 'roles replaced');

        $withRoles = $repository->findById((int) $created->id());
        self::assertSame(['admin'], $withRoles?->roleSlugs() ?? [], 'role slugs resolved');

        $updated = $repository->update((int) $created->id(), ['name' => 'Renamed']);
        self::assertSame('Renamed', $updated?->name(), 'name updated');

        self::assertSame(true, $repository->softDelete((int) $created->id()), 'soft delete succeeds');
        self::assertSame(null, $repository->findById((int) $created->id()), 'soft-deleted user not found');

        $search = $repository->paginate(1, 15, 'Renamed');
        self::assertSame(0, $search['total'], 'deleted user excluded from search');
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
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                last_login_at TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TEXT NULL,
                UNIQUE (email)
            )'
        );
        $pdo->exec('CREATE TABLE user_roles (user_id INTEGER, role_id INTEGER, PRIMARY KEY (user_id, role_id))');
        $pdo->exec('CREATE TABLE roles (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, description TEXT, is_system INTEGER DEFAULT 0, deleted_at TEXT NULL)');
        $pdo->exec("INSERT INTO roles (id, name, slug) VALUES (1, 'Super Administrator', 'super-admin')");
        $pdo->exec("INSERT INTO roles (id, name, slug) VALUES (2, 'Administrator', 'admin')");

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

    private static function assertNotSame(mixed $expected, mixed $actual, string $label): void
    {
        if ($expected !== $actual) {
            self::pass($label);
        } else {
            self::fail($label . ' — expected values to differ');
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

final class FakeUserRepository implements UserRepositoryInterface
{
    private array $rows = [
        1 => ['id' => 1, 'name' => 'Existing', 'email' => 'existing@example.com', 'password_hash' => 'hash', 'is_active' => 1, 'last_login_at' => null, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    private array $roles = [1, 2];

    public function paginate(int $page, int $perPage, ?string $search = null, ?bool $isActive = null): array
    {
        $items = array_map(fn (array $row): User => $this->map($row), $this->rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?User
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        return $this->withRoles($this->map($this->rows[$id]));
    }

    public function findByEmail(string $email): ?User
    {
        foreach ($this->rows as $row) {
            if ($row['email'] === $email) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): User
    {
        $id = count($this->rows) + 1;
        $this->rows[$id] = array_merge(['id' => $id, 'last_login_at' => null, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'], $attributes);

        return $this->map($this->rows[$id]);
    }

    public function update(int $id, array $attributes): ?User
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        foreach (['name', 'email', 'password_hash', 'is_active'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $this->rows[$id][$field] = $attributes[$field];
            }
        }

        return $this->map($this->rows[$id]);
    }

    public function softDelete(int $id): bool
    {
        if (!isset($this->rows[$id])) {
            return false;
        }

        unset($this->rows[$id]);

        return true;
    }

    public function assignRole(int $userId, int $roleId): void
    {
        if (!in_array($roleId, $this->roles, true)) {
            $this->roles[] = $roleId;
        }
    }

    public function replaceRoles(int $userId, array $roleIds): void
    {
        $this->roles = array_values(array_unique(array_filter(array_map('intval', $roleIds))));
    }

    public function roleExists(int $roleId): bool
    {
        return in_array($roleId, [1, 2], true);
    }

    public function findRoleIds(int $userId): array
    {
        return $this->roles;
    }

    private function withRoles(User $user): User
    {
        $slugByRole = [1 => 'super-admin', 2 => 'admin'];

        return new User(
            $user->id(),
            $user->name(),
            $user->email(),
            $user->passwordHash(),
            $user->isActive(),
            $user->lastLoginAt(),
            $user->createdAt(),
            $user->updatedAt(),
            $this->roles,
            array_values(array_filter(array_map(fn (int $id): ?string => $slugByRole[$id] ?? null, $this->roles)))
        );
    }

    private function map(array $row): User
    {
        return new User(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['email'] ?? null,
            $row['password_hash'] ?? null,
            isset($row['is_active']) ? (bool) $row['is_active'] : null,
            $row['last_login_at'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}

UserTest::run();
