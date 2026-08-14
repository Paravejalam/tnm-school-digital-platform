<?php

declare(strict_types=1);

/**
 * Unit tests for the AuditLog read module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/AuditLogTest.php
 *
 * Covers:
 * - AuditLogListRequest pagination and filters
 * - AuditLogResponse JSON value decoding
 * - AuditLogService list/find flow (via fake repository)
 * - AuditLogRepository paginate/findById (SQLite-backed)
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\Audit\AuditLog;
use App\Audit\AuditLogger;
use App\Audit\AuditLoggerInterface;
use App\Audit\AuditLogListRequest;
use App\Audit\AuditLogRepository;
use App\Audit\AuditLogRepositoryInterface;
use App\Audit\AuditLogResponse;
use App\Audit\AuditLogService;

final class AuditLogTest
{
    private static int $assertions = 0;
    private static int $failures = 0;

    public static function run(): void
    {
        self::testListRequestParsesQuery();
        self::testListRequestIgnoresEmptyFilters();
        self::testResponseDecodesJsonValues();
        self::testResponseKeepsInvalidJsonAsString();
        self::testServiceListDelegatesToRepository();
        self::testServiceFindDelegatesToRepository();
        self::testServiceFindMissingReturnsNull();
        self::testRepositoryNoDatabaseDegradesGracefully();
        self::testRepositorySqliteCreateAndPaginate();
        self::testRepositorySqliteFindById();
        self::testLoggerImplementsInterface();
        self::testLoggerWritesThroughRepository();

        echo PHP_EOL;
        echo sprintf("Assertions: %d, Failures: %d%s", self::$assertions, self::$failures, PHP_EOL);
        exit(self::$failures === 0 ? 0 : 1);
    }

    private static function testListRequestParsesQuery(): void
    {
        $request = new AuditLogListRequest([
            'page' => 3,
            'per_page' => 50,
            'action' => 'UPDATE',
            'entity_type' => 'Student',
            'user_id' => '7',
            'entity_id' => '12',
        ]);

        self::assertSame(3, $request->page(), 'page');
        self::assertSame(50, $request->perPage(), 'per_page');
        self::assertSame(['action' => 'UPDATE', 'entity_type' => 'Student', 'user_id' => 7, 'entity_id' => 12], $request->filters(), 'filters');
    }

    private static function testListRequestIgnoresEmptyFilters(): void
    {
        $request = new AuditLogListRequest([
            'action' => '   ',
            'entity_type' => '',
            'user_id' => '0',
            'entity_id' => 'abc',
        ]);

        self::assertSame([], $request->filters(), 'empty filters dropped');

        $default = new AuditLogListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame([], $default->filters(), 'default filters');
    }

    private static function testResponseDecodesJsonValues(): void
    {
        $log = new AuditLog(
            1,
            5,
            'UPDATE',
            'Student',
            9,
            '{"name":"Old"}',
            '{"name":"New"}',
            '127.0.0.1',
            'test-agent',
            '2026-01-01 10:00:00'
        );

        $data = AuditLogResponse::fromEntity($log);

        self::assertSame(1, $data['id'], 'id');
        self::assertSame(5, $data['user_id'], 'user_id');
        self::assertSame(['name' => 'Old'], $data['old_values'], 'old_values decoded');
        self::assertSame(['name' => 'New'], $data['new_values'], 'new_values decoded');
        self::assertSame('127.0.0.1', $data['ip_address'], 'ip_address');
    }

    private static function testResponseKeepsInvalidJsonAsString(): void
    {
        $log = new AuditLog(2, null, 'LOGIN', 'User', null, '{bad json', null, null, null, null);
        $data = AuditLogResponse::fromEntity($log);

        self::assertSame('{bad json', $data['old_values'], 'invalid json kept as string');
        self::assertSame(null, $data['new_values'], 'null json kept as null');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $repository = new FakeAuditLogRepository();
        $service = new AuditLogService($repository);
        $result = $service->list(new AuditLogListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'total');
        self::assertSame(2, count($result['items']), 'item count');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = new AuditLogService(new FakeAuditLogRepository());
        $log = $service->find(1);

        self::assertTrue($log instanceof AuditLog, 'returns entity');
        if ($log instanceof AuditLog) {
            self::assertSame(1, $log->id(), 'id');
            self::assertSame('UPDATE', $log->action(), 'action');
        }
    }

    private static function testServiceFindMissingReturnsNull(): void
    {
        $service = new AuditLogService(new FakeAuditLogRepository());

        self::assertSame(null, $service->find(999), 'missing entry returns null');
    }

    private static function testRepositoryNoDatabaseDegradesGracefully(): void
    {
        $repository = new AuditLogRepository(null);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repository->paginate(1, 15, []), 'no db paginate');
        self::assertSame(null, $repository->findById(1), 'no db findById');
    }

    private static function testRepositorySqliteCreateAndPaginate(): void
    {
        $pdo = self::sqlite();
        $repository = new AuditLogRepository($pdo);

        $created = $repository->create([
            'user_id' => 3,
            'action' => 'LOGIN',
            'entity_type' => 'User',
            'entity_id' => 3,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'phpunit',
        ]);

        $repository->create([
            'user_id' => 4,
            'action' => 'UPDATE',
            'entity_type' => 'Student',
            'entity_id' => 7,
            'old_values' => '{"a":1}',
            'new_values' => '{"a":2}',
            'ip_address' => '10.0.0.2',
            'user_agent' => 'phpunit',
        ]);

        self::assertTrue($created instanceof AuditLog, 'create returns entity');
        self::assertTrue((int) $created->id() > 0, 'create assigns id');

        $all = $repository->paginate(1, 15, []);
        self::assertSame(2, $all['total'], 'all rows counted');
        self::assertSame(2, count($all['items']), 'all rows returned');

        $filtered = $repository->paginate(1, 15, ['action' => 'UPDATE']);
        self::assertSame(1, $filtered['total'], 'action filter count');
        self::assertSame('UPDATE', $filtered['items'][0]->action(), 'action filter match');

        $userFiltered = $repository->paginate(1, 15, ['user_id' => 3]);
        self::assertSame(1, $userFiltered['total'], 'user_id filter count');

        $entityFiltered = $repository->paginate(1, 15, ['entity_type' => 'Student', 'entity_id' => 7]);
        self::assertSame(1, $entityFiltered['total'], 'entity filter count');
    }

    private static function testRepositorySqliteFindById(): void
    {
        $pdo = self::sqlite();
        $repository = new AuditLogRepository($pdo);

        $created = $repository->create([
            'user_id' => 1,
            'action' => 'CREATE',
            'entity_type' => 'Teacher',
            'entity_id' => 2,
            'old_values' => null,
            'new_values' => '{"name":"T"}',
            'ip_address' => null,
            'user_agent' => null,
        ]);

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof AuditLog, 'finds created row');
        if ($found instanceof AuditLog) {
            self::assertSame('CREATE', $found->action(), 'action');
            self::assertSame(2, $found->entityId(), 'entity_id');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function testLoggerImplementsInterface(): void
    {
        $logger = new AuditLogger();

        self::assertTrue($logger instanceof AuditLoggerInterface, 'AuditLogger implements AuditLoggerInterface');
    }

    private static function testLoggerWritesThroughRepository(): void
    {
        $pdo = self::sqlite();
        $repository = new AuditLogRepository($pdo);
        $logger = new AuditLogger($pdo, $repository);

        $logger->log('PASSWORD_CHANGE', 'user', 7, null, ['email' => 'a@b.c']);

        $found = $repository->findById(1);
        self::assertTrue($found instanceof AuditLog, 'logger writes row via repository');
        if ($found instanceof AuditLog) {
            self::assertSame('PASSWORD_CHANGE', $found->action(), 'logged action');
            self::assertSame(7, $found->entityId(), 'logged entity_id');
        }
    }

    private static function sqlite(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                action VARCHAR(100) NOT NULL,
                entity_type VARCHAR(100) NOT NULL,
                entity_id INTEGER NULL,
                old_values TEXT NULL,
                new_values TEXT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(512) NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
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

final class FakeAuditLogRepository implements AuditLogRepositoryInterface
{
    private array $rows = [
        1 => ['id' => 1, 'user_id' => 5, 'action' => 'UPDATE', 'entity_type' => 'Student', 'entity_id' => 9, 'old_values' => '{"name":"Old"}', 'new_values' => '{"name":"New"}', 'ip_address' => '127.0.0.1', 'user_agent' => 'ua', 'created_at' => '2026-01-01 10:00:00'],
        2 => ['id' => 2, 'user_id' => 5, 'action' => 'LOGIN', 'entity_type' => 'User', 'entity_id' => 5, 'old_values' => null, 'new_values' => null, 'ip_address' => '127.0.0.1', 'user_agent' => 'ua', 'created_at' => '2026-01-01 11:00:00'],
    ];

    public function create(array $attributes): AuditLog
    {
        return $this->map($attributes);
    }

    public function paginate(int $page, int $perPage, array $filters = []): array
    {
        $items = array_map(fn (array $row): AuditLog => $this->map($row), $this->rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?AuditLog
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    private function map(array $row): AuditLog
    {
        return new AuditLog(
            isset($row['id']) ? (int) $row['id'] : null,
            isset($row['user_id']) ? (int) $row['user_id'] : null,
            $row['action'] ?? null,
            $row['entity_type'] ?? null,
            isset($row['entity_id']) ? (int) $row['entity_id'] : null,
            $row['old_values'] ?? null,
            $row['new_values'] ?? null,
            $row['ip_address'] ?? null,
            $row['user_agent'] ?? null,
            $row['created_at'] ?? null
        );
    }
}

AuditLogTest::run();
