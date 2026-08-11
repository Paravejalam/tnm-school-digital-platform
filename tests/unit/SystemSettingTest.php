<?php

declare(strict_types=1);

/**
 * Unit tests for the SystemSetting module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/SystemSettingTest.php
 *
 * Covers:
 * - SystemSettingValidator type/value validation and normalization
 * - SystemSettingResponse type casting
 * - SystemSettingListRequest pagination and filters
 * - SystemSettingService update flow (via fake repository)
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\Auth\ValidationException;
use App\SystemSetting\SystemSetting;
use App\SystemSetting\SystemSettingListRequest;
use App\SystemSetting\SystemSettingRepositoryInterface;
use App\SystemSetting\SystemSettingResponse;
use App\SystemSetting\SystemSettingService;
use App\SystemSetting\SystemSettingValidator;
use App\SystemSetting\UpdateSystemSettingRequest;

final class SystemSettingTest
{
    private static int $assertions = 0;
    private static int $failures = 0;

    public static function run(): void
    {
        self::testValidatorAcceptsValidPayloads();
        self::testValidatorRejectsInvalidPayloads();
        self::testNormalizeValueCastsByType();
        self::testResponseCastsTypedValues();
        self::testListRequestParsesQuery();
        self::testServiceUpdateNotFoundReturnsNull();
        self::testServiceUpdatePersistsAndReturnsEntity();
        self::testServiceUpdateThrowsOnInvalidValue();

        echo PHP_EOL;
        echo sprintf("Assertions: %d, Failures: %d%s", self::$assertions, self::$failures, PHP_EOL);
        exit(self::$failures === 0 ? 0 : 1);
    }

    private static function testValidatorAcceptsValidPayloads(): void
    {
        $validator = new SystemSettingValidator();

        $valid = [
            ['value' => 'hello'],
            ['value' => '15', 'type' => 'integer'],
            ['value' => '1', 'type' => 'boolean'],
            ['value' => true, 'type' => 'boolean'],
            ['value' => ['a' => 1], 'type' => 'json'],
            ['value' => '{"x":2}', 'type' => 'json'],
            ['is_public' => true],
            ['is_public' => 'false'],
            ['module' => 'System'],
        ];

        foreach ($valid as $payload) {
            try {
                $validator->validateUpdate($payload);
                $validator->validateValueAgainstType($payload);
                self::pass('accepts ' . json_encode($payload));
            } catch (ValidationException $e) {
                self::fail('should accept ' . json_encode($payload) . ' but got ' . json_encode($e->errors()));
            }
        }
    }

    private static function testValidatorRejectsInvalidPayloads(): void
    {
        $validator = new SystemSettingValidator();

        $invalid = [
            ['value' => 'abc', 'type' => 'integer'],
            ['value' => 'yes', 'type' => 'boolean'],
            ['value' => '{bad json', 'type' => 'json'],
            ['type' => 'float'],
            ['is_public' => 'maybe'],
            ['module' => '   '],
        ];

        foreach ($invalid as $payload) {
            try {
                $validator->validateUpdate($payload);
                $validator->validateValueAgainstType($payload);
                self::fail('should reject ' . json_encode($payload));
            } catch (ValidationException) {
                self::pass('rejects ' . json_encode($payload));
            }
        }
    }

    private static function testNormalizeValueCastsByType(): void
    {
        $validator = new SystemSettingValidator();

        self::assertSame('15', $validator->normalizeValue(['value' => '15', 'type' => 'integer'])['value'], 'integer cast');
        self::assertSame('1', $validator->normalizeValue(['value' => true, 'type' => 'boolean'])['value'], 'boolean true cast');
        self::assertSame('0', $validator->normalizeValue(['value' => 'false', 'type' => 'boolean'])['value'], 'boolean false cast');
        self::assertSame('{"a":1}', $validator->normalizeValue(['value' => ['a' => 1], 'type' => 'json'])['value'], 'json array cast');
        self::assertSame('plain', $validator->normalizeValue(['value' => 'plain', 'type' => 'string'])['value'], 'string cast');
    }

    private static function testResponseCastsTypedValues(): void
    {
        $integer = new SystemSetting(1, 'app.max', '150', 'integer', 'System', null, 0, null, null);
        $boolean = new SystemSetting(2, 'app.flag', '1', 'boolean', 'System', null, 1, null, null);
        $json = new SystemSetting(3, 'app.list', '{"a":1}', 'json', 'System', null, 0, null, null);

        $integerData = SystemSettingResponse::fromEntity($integer);
        $booleanData = SystemSettingResponse::fromEntity($boolean);
        $jsonData = SystemSettingResponse::fromEntity($json);

        self::assertSame(150, $integerData['value'], 'integer value cast');
        self::assertSame(true, $booleanData['value'], 'boolean value cast');
        self::assertSame(['a' => 1], $jsonData['value'], 'json value cast');
        self::assertSame(true, $booleanData['is_public'], 'is_public cast');
    }

    private static function testListRequestParsesQuery(): void
    {
        $request = new SystemSettingListRequest([
            'page' => 2,
            'per_page' => 50,
            'module' => 'System',
            'is_public' => 'true',
        ]);

        self::assertSame(2, $request->page(), 'page');
        self::assertSame(50, $request->perPage(), 'per_page');
        self::assertSame('System', $request->module(), 'module');
        self::assertSame(true, $request->isPublic(), 'is_public');

        $default = new SystemSettingListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->module(), 'default module');
        self::assertSame(null, $default->isPublic(), 'default is_public');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = new SystemSettingService(new FakeSystemSettingRepository(), new SystemSettingValidator());
        $result = $service->update(new UpdateSystemSettingRequest(999, ['value' => 'x']));

        self::assertSame(null, $result, 'missing setting returns null');
    }

    private static function testServiceUpdatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeSystemSettingRepository();
        $service = new SystemSettingService($repository, new SystemSettingValidator());
        $result = $service->update(new UpdateSystemSettingRequest(1, ['value' => '50', 'type' => 'integer']));

        self::assertTrue($result instanceof SystemSetting, 'returns entity');
        if ($result instanceof SystemSetting) {
            self::assertSame('50', $result->value(), 'updated value persisted');
            self::assertSame('integer', $result->type(), 'type retained');
        }
    }

    private static function testServiceUpdateThrowsOnInvalidValue(): void
    {
        $service = new SystemSettingService(new FakeSystemSettingRepository(), new SystemSettingValidator());

        try {
            $service->update(new UpdateSystemSettingRequest(1, ['value' => 'abc', 'type' => 'integer']));
            self::fail('should reject invalid typed value');
        } catch (ValidationException) {
            self::pass('rejects invalid typed value');
        }
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

final class FakeSystemSettingRepository implements SystemSettingRepositoryInterface
{
    private array $rows = [
        1 => ['id' => 1, 'key' => 'app.name', 'value' => 'T.N.', 'type' => 'string', 'module' => 'System', 'description' => null, 'is_public' => 1, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $module = null, ?bool $isPublic = null): array
    {
        $items = array_map(fn (array $row): SystemSetting => $this->map($row), $this->rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?SystemSetting
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByKey(string $key): ?SystemSetting
    {
        foreach ($this->rows as $row) {
            if ($row['key'] === $key) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function update(int $id, array $attributes): ?SystemSetting
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        foreach (['value', 'type', 'module', 'description', 'is_public'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $this->rows[$id][$field] = $attributes[$field];
            }
        }

        return $this->map($this->rows[$id]);
    }

    private function map(array $row): SystemSetting
    {
        return new SystemSetting(
            (int) $row['id'],
            $row['key'],
            $row['value'],
            $row['type'],
            $row['module'],
            $row['description'],
            (int) $row['is_public'],
            $row['created_at'],
            $row['updated_at']
        );
    }
}

SystemSettingTest::run();
