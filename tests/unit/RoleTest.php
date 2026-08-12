<?php

declare(strict_types=1);

/**
 * Unit tests for the Role module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/RoleTest.php
 *
 * Covers:
 * - RoleListRequest pagination and search
 * - RoleResponse casting and permission slugs
 * - RoleService list/find flow (via fake repository)
 * - RoleRepository paginate/findById/permission resolution (SQLite-backed)
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\Role\Role;
use App\Role\RoleListRequest;
use App\Role\RoleRepository;
use App\Role\RoleRepositoryInterface;
use App\Role\RoleResponse;
use App\Role\RoleService;

final class RoleTest
{
    private static int $assertions = 0;
    private static int $failures = 0;

    public static function run(): void
    {
        self::testListRequestParsesQuery();
        self::testResponseListsPermissions();
        self::testServiceListDelegatesToRepository();
        self::testServiceFindDelegatesToRepository();
        self::testServiceFindMissingReturnsNull();
        self::testRepositoryNoDatabaseDegradesGracefully();
        self::testRepositorySqlitePaginateAndFind();

        echo PHP_EOL;
        echo sprintf("Assertions: %d, Failures: %d%s", self::$assertions, self::$failures, PHP_EOL);
        exit(self::$failures === 0 ? 0 : 1);
    }

    private static function testListRequestParsesQuery(): void
    {
        $request = new RoleListRequest(['page' => 2, 'per_page' => 50, 'search' => 'admin']);

        self::assertSame(2, $request->page(), 'page');
        self::assertSame(50, $request->perPage(), 'per_page');
        self::assertSame('admin', $request->search(), 'search');

        $default = new RoleListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search');
    }

    private static function testResponseListsPermissions(): void
    {
        $role = new Role(1, 'Super Administrator', 'super-admin', 'Full access', true, '2026-01-01', '2026-01-02', ['users.view', 'students.view']);

        $data = RoleResponse::fromEntity($role);

        self::assertSame(1, $data['id'], 'id');
        self::assertSame('super-admin', $data['slug'], 'slug');
        self::assertSame(true, $data['is_system'], 'is_system');
        self::assertSame(['users.view', 'students.view'], $data['permissions'], 'permission slugs');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $service = new RoleService(new FakeRoleRepository());
        $result = $service->list(new RoleListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'total');
        self::assertSame(2, count($result['items']), 'item count');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = new RoleService(new FakeRoleRepository());
        $role = $service->find(1);

        self::assertTrue($role instanceof Role, 'returns entity');
        if ($role instanceof Role) {
            self::assertSame('super-admin', $role->slug(), 'slug');
            self::assertSame(['users.view'], $role->permissionSlugs(), 'permission slugs');
        }
    }

    private static function testServiceFindMissingReturnsNull(): void
    {
        $service = new RoleService(new FakeRoleRepository());

        self::assertSame(null, $service->find(999), 'missing role returns null');
    }

    private static function testRepositoryNoDatabaseDegradesGracefully(): void
    {
        $repository = new RoleRepository(null);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repository->paginate(1, 15), 'no db paginate');
        self::assertSame(null, $repository->findById(1), 'no db findById');
        self::assertSame([], $repository->findPermissionSlugs(1), 'no db permission slugs');
    }

    private static function testRepositorySqlitePaginateAndFind(): void
    {
        $pdo = self::sqlite();
        $repository = new RoleRepository($pdo);

        $all = $repository->paginate(1, 15, null);
        self::assertSame(3, $all['total'], 'all roles counted');
        self::assertSame(3, count($all['items']), 'all roles returned');

        $searched = $repository->paginate(1, 15, 'admin');
        self::assertSame(2, $searched['total'], 'search matches slug');

        $role = $repository->findById(1);
        self::assertTrue($role instanceof Role, 'finds role');
        if ($role instanceof Role) {
            self::assertSame('super-admin', $role->slug(), 'slug');
            self::assertSame(true, $role->isSystem(), 'is_system');
            self::assertSame(['audit-logs.view', 'users.view'], $role->permissionSlugs(), 'permission slugs sorted');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function sqlite(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                description TEXT NULL,
                is_system TINYINT(1) NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TEXT NULL,
                UNIQUE (slug)
            )'
        );
        $pdo->exec('CREATE TABLE permissions (id INTEGER PRIMARY KEY, name TEXT, slug TEXT, module TEXT)');
        $pdo->exec('CREATE TABLE role_permissions (role_id INTEGER, permission_id INTEGER, PRIMARY KEY (role_id, permission_id))');

        $pdo->exec("INSERT INTO roles (id, name, slug, description, is_system) VALUES (1, 'Super Administrator', 'super-admin', 'Full access', 1)");
        $pdo->exec("INSERT INTO roles (id, name, slug, description, is_system) VALUES (2, 'Administrator', 'admin', 'Ops', 1)");
        $pdo->exec("INSERT INTO roles (id, name, slug, description, is_system) VALUES (3, 'Teacher', 'teacher', 'Teaching staff', 1)");
        $pdo->exec("INSERT INTO permissions (id, name, slug, module) VALUES (1, 'View Users', 'users.view', 'Auth')");
        $pdo->exec("INSERT INTO permissions (id, name, slug, module) VALUES (2, 'View Audit Logs', 'audit-logs.view', 'System')");
        $pdo->exec('INSERT INTO role_permissions (role_id, permission_id) VALUES (1, 2)');
        $pdo->exec('INSERT INTO role_permissions (role_id, permission_id) VALUES (1, 1)');

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

final class FakeRoleRepository implements RoleRepositoryInterface
{
    private array $rows = [
        1 => ['id' => 1, 'name' => 'Super Administrator', 'slug' => 'super-admin', 'description' => 'Full access', 'is_system' => 1, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01', 'permissions' => ['users.view']],
        2 => ['id' => 2, 'name' => 'Administrator', 'slug' => 'admin', 'description' => 'Ops', 'is_system' => 1, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01', 'permissions' => []],
    ];

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        $items = array_map(fn (array $row): Role => $this->map($row), $this->rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Role
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findPermissionSlugs(int $roleId): array
    {
        return $this->rows[$roleId]['permissions'] ?? [];
    }

    private function map(array $row): Role
    {
        return new Role(
            (int) $row['id'],
            $row['name'],
            $row['slug'],
            $row['description'],
            (bool) $row['is_system'],
            $row['created_at'],
            $row['updated_at'],
            $row['permissions'] ?? []
        );
    }
}

RoleTest::run();
