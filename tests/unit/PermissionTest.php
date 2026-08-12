<?php

declare(strict_types=1);

/**
 * Unit tests for the Permission module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/PermissionTest.php
 *
 * Covers:
 * - PermissionListRequest pagination, module and search filters
 * - PermissionResponse casting
 * - PermissionService list/find flow (via fake repository)
 * - PermissionRepository paginate/findById/findBySlug (SQLite-backed)
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\Permission\Permission;
use App\Permission\PermissionListRequest;
use App\Permission\PermissionRepository;
use App\Permission\PermissionRepositoryInterface;
use App\Permission\PermissionResponse;
use App\Permission\PermissionService;

final class PermissionTest
{
    private static int $assertions = 0;
    private static int $failures = 0;

    public static function run(): void
    {
        self::testListRequestParsesQuery();
        self::testResponseCastsFields();
        self::testServiceListDelegatesToRepository();
        self::testServiceFindDelegatesToRepository();
        self::testServiceFindMissingReturnsNull();
        self::testRepositoryNoDatabaseDegradesGracefully();
        self::testRepositorySqlitePaginateFind();

        echo PHP_EOL;
        echo sprintf("Assertions: %d, Failures: %d%s", self::$assertions, self::$failures, PHP_EOL);
        exit(self::$failures === 0 ? 0 : 1);
    }

    private static function testListRequestParsesQuery(): void
    {
        $request = new PermissionListRequest(['page' => 2, 'per_page' => 50, 'module' => 'Auth', 'search' => 'view']);

        self::assertSame(2, $request->page(), 'page');
        self::assertSame(50, $request->perPage(), 'per_page');
        self::assertSame('Auth', $request->module(), 'module');
        self::assertSame('view', $request->search(), 'search');

        $default = new PermissionListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->module(), 'default module');
        self::assertSame(null, $default->search(), 'default search');
    }

    private static function testResponseCastsFields(): void
    {
        $permission = new Permission(1, 'View Users', 'users.view', 'Auth', 'desc', '2026-01-01', '2026-01-02');

        $data = PermissionResponse::fromEntity($permission);

        self::assertSame(1, $data['id'], 'id');
        self::assertSame('users.view', $data['slug'], 'slug');
        self::assertSame('Auth', $data['module'], 'module');
        self::assertSame('desc', $data['description'], 'description');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $service = new PermissionService(new FakePermissionRepository());
        $result = $service->list(new PermissionListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'total');
        self::assertSame(2, count($result['items']), 'item count');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = new PermissionService(new FakePermissionRepository());
        $permission = $service->find(1);

        self::assertTrue($permission instanceof Permission, 'returns entity');
        if ($permission instanceof Permission) {
            self::assertSame('users.view', $permission->slug(), 'slug');
            self::assertSame('Auth', $permission->module(), 'module');
        }
    }

    private static function testServiceFindMissingReturnsNull(): void
    {
        $service = new PermissionService(new FakePermissionRepository());

        self::assertSame(null, $service->find(999), 'missing permission returns null');
    }

    private static function testRepositoryNoDatabaseDegradesGracefully(): void
    {
        $repository = new PermissionRepository(null);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repository->paginate(1, 15), 'no db paginate');
        self::assertSame(null, $repository->findById(1), 'no db findById');
        self::assertSame(null, $repository->findBySlug('users.view'), 'no db findBySlug');
    }

    private static function testRepositorySqlitePaginateFind(): void
    {
        $pdo = self::sqlite();
        $repository = new PermissionRepository($pdo);

        $all = $repository->paginate(1, 15);
        self::assertSame(3, $all['total'], 'all permissions counted');
        self::assertSame(3, count($all['items']), 'all permissions returned');

        $filtered = $repository->paginate(1, 15, 'Auth');
        self::assertSame(1, $filtered['total'], 'module filter count');

        $searched = $repository->paginate(1, 15, null, 'users');
        self::assertSame(1, $searched['total'], 'search matches slug');

        $found = $repository->findById(2);
        self::assertTrue($found instanceof Permission, 'finds by id');
        if ($found instanceof Permission) {
            self::assertSame('students.view', $found->slug(), 'slug');
        }

        $bySlug = $repository->findBySlug('audit-logs.view');
        self::assertTrue($bySlug instanceof Permission, 'finds by slug');
        if ($bySlug instanceof Permission) {
            self::assertSame('System', $bySlug->module(), 'module');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
        self::assertSame(null, $repository->findBySlug('nope.view'), 'missing slug returns null');
    }

    private static function sqlite(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(150) NOT NULL,
                module VARCHAR(100) NOT NULL,
                description TEXT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (slug)
            )'
        );
        $pdo->exec("INSERT INTO permissions (id, name, slug, module) VALUES (1, 'View Users', 'users.view', 'Auth')");
        $pdo->exec("INSERT INTO permissions (id, name, slug, module) VALUES (2, 'View Students', 'students.view', 'Student')");
        $pdo->exec("INSERT INTO permissions (id, name, slug, module) VALUES (3, 'View Audit Logs', 'audit-logs.view', 'System')");

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

final class FakePermissionRepository implements PermissionRepositoryInterface
{
    private array $rows = [
        1 => ['id' => 1, 'name' => 'View Users', 'slug' => 'users.view', 'module' => 'Auth', 'description' => null, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'name' => 'View Students', 'slug' => 'students.view', 'module' => 'Student', 'description' => null, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $module = null, ?string $search = null): array
    {
        $items = array_map(fn (array $row): Permission => $this->map($row), $this->rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Permission
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findBySlug(string $slug): ?Permission
    {
        foreach ($this->rows as $row) {
            if ($row['slug'] === $slug) {
                return $this->map($row);
            }
        }

        return null;
    }

    private function map(array $row): Permission
    {
        return new Permission(
            (int) $row['id'],
            $row['name'],
            $row['slug'],
            $row['module'],
            $row['description'],
            $row['created_at'],
            $row['updated_at']
        );
    }
}

PermissionTest::run();
