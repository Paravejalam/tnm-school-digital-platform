<?php

declare(strict_types=1);

/**
 * Unit tests for the AttendanceRecord module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/AttendanceRecordModuleTest.php
 *
 * Covers:
 * - AttendanceRecord entity getters
 * - AttendanceRecordResponse shape (id, record_name, attendance_id,
 *   student_id, status)
 * - AttendanceRecordListRequest pagination and search parsing
 * - CreateAttendanceRecordRequest / UpdateAttendanceRecordRequest accessors
 * - AttendanceRecordValidator create/update validation
 *   (statuses aligned with migration ENUM: present/absent/late/excused/holiday)
 * - AttendanceRecordException type
 * - AttendanceRecordRepository no-DB behavior
 * - AttendanceRecordRepository SQLite-backed CRUD + search
 * - Partial update preserves note/recorded_by
 * - Default status is 'present' (matches migration default)
 * - AttendanceRecordService create/update/delete flows (via fake repository)
 * - Duplicate record_name checks
 * - Reference validation for attendance
 * - AttendanceRecordServiceProvider DI bindings and aliases
 * - AttendanceRecordController request/response handling
 * - Router dispatch regression for /attendance-records resource routes
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\Attendance\Attendance;
use App\Attendance\AttendanceRepositoryInterface;
use App\AttendanceRecord\AttendanceRecord;
use App\AttendanceRecord\AttendanceRecordController;
use App\AttendanceRecord\AttendanceRecordException;
use App\AttendanceRecord\AttendanceRecordListRequest;
use App\AttendanceRecord\AttendanceRecordRepository;
use App\AttendanceRecord\AttendanceRecordRepositoryInterface;
use App\AttendanceRecord\AttendanceRecordResponse;
use App\AttendanceRecord\AttendanceRecordService;
use App\AttendanceRecord\AttendanceRecordServiceInterface;
use App\AttendanceRecord\AttendanceRecordServiceProvider;
use App\AttendanceRecord\AttendanceRecordValidator;
use App\AttendanceRecord\CreateAttendanceRecordRequest;
use App\AttendanceRecord\UpdateAttendanceRecordRequest;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use App\Core\Router;
use App\Http\RequestHelper;
use App\Support\AppContainer;

final class AttendanceRecordModuleTest
{
    private static int $assertions = 0;
    private static int $failures = 0;

    public static function run(): void
    {
        self::testEntityGetters();
        self::testResponseExposesExpectedFields();
        self::testResponseCollection();
        self::testListRequestParsesQuery();
        self::testCreateRequestPayload();
        self::testUpdateRequestIdAndPayload();
        self::testValidatorAcceptsValidCreate();
        self::testValidatorAcceptsAllSchemaStatuses();
        self::testValidatorRejectsMissingFields();
        self::testValidatorRejectsInvalidStatus();
        self::testValidatorAcceptsValidUpdate();
        self::testValidatorRejectsEmptyRecordNameOnUpdate();
        self::testValidatorRejectsInvalidStatusOnUpdate();
        self::testExceptionExtendsRuntimeException();
        self::testRepositoryNoDatabaseBehavior();
        self::testRepositoryCreatePersistsAndFindById();
        self::testRepositoryCreateDefaultsStatusToPresent();
        self::testRepositoryCreateDoesNotPersistRecordName();
        self::testRepositoryFindByNameMatchesNoteColumn();
        self::testRepositoryPaginateWithSearch();
        self::testRepositoryUpdatePersistsStatus();
        self::testRepositoryUpdatePreservesFieldsOnPartialUpdate();
        self::testRepositoryUpdateMissingIdReturnsNull();
        self::testRepositoryDeleteNotSupportedOnSqlite();
        self::testServiceNoDatabaseCreateMapsEntity();
        self::testServiceCreatePersistsAndReturnsEntity();
        self::testServiceCreateRejectsDuplicateRecordName();
        self::testServiceCreateRejectsMissingAttendanceReference();
        self::testServiceCreateRejectsInvalidPayload();
        self::testServiceCreateRejectsInvalidStatus();
        self::testServiceUpdatePersistsAndReturnsEntity();
        self::testServiceUpdateNotFoundReturnsNull();
        self::testServiceUpdateRejectsDuplicateRecordName();
        self::testServiceUpdateRejectsInvalidPayload();
        self::testServiceDeleteReturnsTrueWhenDeleted();
        self::testServiceDeleteReturnsFalseWhenMissing();
        self::testServiceListDelegatesToRepository();
        self::testServiceFindDelegatesToRepository();
        self::testServiceProviderRegistersBindings();
        self::testControllerIndexSuccess();
        self::testControllerShowNotFound();
        self::testControllerStoreValidationError();
        self::testControllerStoreConflict();
        self::testControllerStoreSuccess();
        self::testControllerUpdateNotFound();
        self::testControllerDestroyNotFound();
        self::testRouterDispatchRoutes();

        echo PHP_EOL;
        echo sprintf("Assertions: %d, Failures: %d%s", self::$assertions, self::$failures, PHP_EOL);
        exit(self::$failures === 0 ? 0 : 1);
    }

    // -------------------------------------------------------------------------
    // Entity
    // -------------------------------------------------------------------------

    private static function testEntityGetters(): void
    {
        $item = new AttendanceRecord(1, 'Roll call A', 1, 1, 'present', 'On time', 5, '2026-01-01 08:00:00', '2026-01-01', '2026-01-02', null);

        self::assertSame(1, $item->id(), 'id');
        self::assertSame('Roll call A', $item->recordName(), 'record_name');
        self::assertSame(1, $item->attendanceId(), 'attendance_id');
        self::assertSame(1, $item->studentId(), 'student_id');
        self::assertSame('present', $item->status(), 'status');
        self::assertSame('On time', $item->note(), 'note');
        self::assertSame(5, $item->recordedBy(), 'recorded_by');
        self::assertSame('2026-01-01 08:00:00', $item->recordedAt(), 'recorded_at');
        self::assertSame('2026-01-01', $item->createdAt(), 'created_at');
        self::assertSame('2026-01-02', $item->updatedAt(), 'updated_at');
        self::assertSame(null, $item->deletedAt(), 'deleted_at default null');
    }

    // -------------------------------------------------------------------------
    // Response DTO
    // -------------------------------------------------------------------------

    private static function testResponseExposesExpectedFields(): void
    {
        $item = new AttendanceRecord(1, 'Roll call A', 1, 1, 'present', null, null, null, null, null, null);
        $data = AttendanceRecordResponse::fromEntity($item);

        self::assertSame(1, $data['id'], 'response id');
        self::assertSame('Roll call A', $data['record_name'], 'response record_name');
        self::assertSame(1, $data['attendance_id'], 'response attendance_id');
        self::assertSame(1, $data['student_id'], 'response student_id');
        self::assertSame('present', $data['status'], 'response status');
        self::assertSame(5, count($data), 'response has exactly 5 fields');
    }

    private static function testResponseCollection(): void
    {
        $items = [
            new AttendanceRecord(1, 'Roll call A', 1, 1, 'present', null, null, null, null, null, null),
            new AttendanceRecord(2, 'Roll call B', 1, 2, 'absent', null, null, null, null, null, null),
        ];

        $collection = AttendanceRecordResponse::collection($items);

        self::assertSame(2, count($collection), 'collection length');
        self::assertSame('Roll call A', $collection[0]['record_name'], 'first record_name');
        self::assertSame('absent', $collection[1]['status'], 'second status');
    }

    // -------------------------------------------------------------------------
    // ListRequest
    // -------------------------------------------------------------------------

    private static function testListRequestParsesQuery(): void
    {
        $request = new AttendanceRecordListRequest([
            'page' => 2,
            'per_page' => 50,
            'search' => 'Roll call',
        ]);

        self::assertSame(2, $request->page(), 'page parsed');
        self::assertSame(50, $request->perPage(), 'per_page parsed');
        self::assertSame('Roll call', $request->search(), 'search parsed');

        $default = new AttendanceRecordListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search null');

        $nameFallback = new AttendanceRecordListRequest(['name' => 'Roll call']);
        self::assertSame('Roll call', $nameFallback->search(), 'name fallback to search');

        $bounded = new AttendanceRecordListRequest(['page' => 0, 'per_page' => 500]);
        self::assertSame(1, $bounded->page(), 'page clamped to 1');
        self::assertSame(100, $bounded->perPage(), 'per_page capped at 100');
    }

    // -------------------------------------------------------------------------
    // Create/Update requests
    // -------------------------------------------------------------------------

    private static function testCreateRequestPayload(): void
    {
        $payload = ['record_name' => 'Roll call A', 'attendance_id' => 1];
        $request = new CreateAttendanceRecordRequest($payload);

        self::assertSame($payload, $request->payload(), 'create payload passthrough');
    }

    private static function testUpdateRequestIdAndPayload(): void
    {
        $payload = ['status' => 'late'];
        $request = new UpdateAttendanceRecordRequest(7, $payload);

        self::assertSame(7, $request->id(), 'update id');
        self::assertSame($payload, $request->payload(), 'update payload passthrough');
    }

    // -------------------------------------------------------------------------
    // Validator
    // -------------------------------------------------------------------------

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new AttendanceRecordValidator();

        try {
            $validator->validateCreate([
                'record_name' => 'Roll call A',
                'attendance_id' => 1,
                'student_id' => 1,
                'status' => 'present',
            ]);
            $validator->validateCreate([
                'record_name' => 'Roll call B',
                'attendance_id' => 1,
                'student_id' => 2,
            ]);
            self::pass('valid create payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid create payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorAcceptsAllSchemaStatuses(): void
    {
        $validator = new AttendanceRecordValidator();

        try {
            foreach (['present', 'absent', 'late', 'excused', 'holiday'] as $status) {
                $validator->validateCreate([
                    'record_name' => 'Roll call',
                    'attendance_id' => 1,
                    'student_id' => 1,
                    'status' => $status,
                ]);
            }
            self::pass('all schema statuses accepted');
        } catch (ValidationException $exception) {
            self::fail('schema status rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsMissingFields(): void
    {
        $validator = new AttendanceRecordValidator();

        try {
            $validator->validateCreate(['status' => 'present']);
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['record_name']), 'record_name error present');
            self::assertTrue(isset($errors['attendance_id']), 'attendance_id error present');
            self::assertTrue(isset($errors['student_id']), 'student_id error present');
        }
    }

    private static function testValidatorRejectsInvalidStatus(): void
    {
        $validator = new AttendanceRecordValidator();

        try {
            $validator->validateCreate([
                'record_name' => 'Roll call A',
                'attendance_id' => 1,
                'student_id' => 1,
                'status' => 'active',
            ]);
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['status']), 'status error present');
            self::assertSame('Status must be present, absent, late, excused or holiday.', $errors['status'][0], 'status error message');
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new AttendanceRecordValidator();

        try {
            $validator->validateUpdate(['status' => 'late']);
            $validator->validateUpdate(['record_name' => 'Roll call B']);
            $validator->validateUpdate(['status' => 'excused', 'record_name' => 'Roll call C']);
            self::pass('valid update payloads accepted');
        } catch (ValidationException $exception) {
            self::fail('valid update payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsEmptyRecordNameOnUpdate(): void
    {
        $validator = new AttendanceRecordValidator();

        try {
            $validator->validateUpdate(['record_name' => '   ']);
            self::fail('empty record_name should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['record_name']), 'record_name error present');
            self::assertSame('Attendance record name cannot be empty.', $errors['record_name'][0], 'record_name error message');
        }
    }

    private static function testValidatorRejectsInvalidStatusOnUpdate(): void
    {
        $validator = new AttendanceRecordValidator();

        try {
            $validator->validateUpdate(['status' => 'inactive']);
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    // -------------------------------------------------------------------------
    // Exception
    // -------------------------------------------------------------------------

    private static function testExceptionExtendsRuntimeException(): void
    {
        self::assertTrue(new AttendanceRecordException('boom') instanceof \RuntimeException, 'AttendanceRecordException extends RuntimeException');
        self::assertSame('boom', (new AttendanceRecordException('boom'))->getMessage(), 'exception message');
    }

    // -------------------------------------------------------------------------
    // Repository — no DB
    // -------------------------------------------------------------------------

    private static function testRepositoryNoDatabaseBehavior(): void
    {
        $repository = new AttendanceRecordRepository(null);

        self::assertSame(0, $repository->paginate(1, 15)['total'], 'paginate no-db empty');
        self::assertSame([], $repository->paginate(1, 15)['items'], 'paginate no-db items');
        self::assertSame(null, $repository->findById(1), 'findById no-db null');
        self::assertSame(null, $repository->findByName('Roll call A'), 'findByName no-db null');
        self::assertSame(false, $repository->delete(1), 'delete no-db false');

        $created = $repository->create([
            'record_name' => 'Roll call A',
            'attendance_id' => 1,
            'student_id' => 1,
        ]);
        self::assertTrue($created instanceof AttendanceRecord, 'create no-db returns entity');
        if ($created instanceof AttendanceRecord) {
            self::assertSame(null, $created->id(), 'no-db created has no id');
            self::assertSame(1, $created->attendanceId(), 'no-db created attendance_id');
            self::assertSame('present', $created->status(), 'no-db created default status');
            self::assertSame(null, $created->recordName(), 'no-db created record_name null (no column in schema)');
            self::assertSame(null, $created->studentId(), 'no-db created student_id null (no column in schema)');
        }

        self::assertSame(null, $repository->update(1, ['status' => 'late']), 'update no-db null');
    }

    // -------------------------------------------------------------------------
    // Repository — SQLite CRUD
    // -------------------------------------------------------------------------

    private static function testRepositoryCreatePersistsAndFindById(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());
        $created = $repository->create([
            'attendance_id' => 1,
            'status' => 'late',
            'note' => 'Arrived late',
            'recorded_by' => 5,
        ]);

        self::assertTrue($created instanceof AttendanceRecord, 'create returns entity');
        if ($created instanceof AttendanceRecord) {
            self::assertTrue($created->id() > 0, 'create assigns id');
        }

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof AttendanceRecord, 'findById returns entity');
        if ($found instanceof AttendanceRecord) {
            self::assertSame(1, $found->attendanceId(), 'findById attendance_id');
            self::assertSame('late', $found->status(), 'findById status');
            self::assertSame('Arrived late', $found->note(), 'findById note');
            self::assertSame(5, $found->recordedBy(), 'findById recorded_by');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function testRepositoryCreateDefaultsStatusToPresent(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());
        $created = $repository->create([
            'attendance_id' => 1,
            'note' => 'Roll call A',
        ]);

        self::assertTrue($created instanceof AttendanceRecord, 'create returns entity');
        if ($created instanceof AttendanceRecord) {
            self::assertSame('present', $created->status(), 'default status is present (matches schema)');
        }
    }

    private static function testRepositoryCreateDoesNotPersistRecordName(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());
        $created = $repository->create([
            'record_name' => 'Roll call A',
            'attendance_id' => 1,
            'student_id' => 1,
        ]);

        self::assertTrue($created instanceof AttendanceRecord, 'create returns entity');
        if ($created instanceof AttendanceRecord) {
            self::assertSame(null, $created->recordName(), 'record_name not persisted (no column in schema)');
            self::assertSame(null, $created->studentId(), 'student_id not persisted (no column in schema)');
        }
    }

    private static function testRepositoryFindByNameMatchesNoteColumn(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());
        $repository->create([
            'attendance_id' => 1,
            'status' => 'present',
            'note' => 'Roll call A',
        ]);
        $repository->create([
            'attendance_id' => 1,
            'status' => 'absent',
            'note' => 'Roll call B',
        ]);

        $found = $repository->findByName('Roll call A');
        self::assertTrue($found instanceof AttendanceRecord, 'findByName finds existing');
        if ($found instanceof AttendanceRecord) {
            self::assertSame(1, $found->id(), 'findByName id');
        }

        self::assertSame(null, $repository->findByName('Roll call Z'), 'findByName missing null');
    }

    private static function testRepositoryPaginateWithSearch(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());
        $repository->create(['attendance_id' => 1, 'status' => 'present', 'note' => 'Roll call A']);
        $repository->create(['attendance_id' => 1, 'status' => 'present', 'note' => 'Roll call B']);
        $repository->create(['attendance_id' => 1, 'status' => 'present', 'note' => 'Roll call C']);

        $all = $repository->paginate(1, 15);
        self::assertSame(3, $all['total'], 'paginate total');
        self::assertSame(3, count($all['items']), 'paginate item count');

        $filtered = $repository->paginate(1, 15, 'Roll call B');
        self::assertSame(1, $filtered['total'], 'search total');
        self::assertSame(1, count($filtered['items']), 'search item count');

        $paged = $repository->paginate(2, 2);
        self::assertSame(3, $paged['total'], 'page 2 total retains full count');
        self::assertSame(1, count($paged['items']), 'page 2 has 1 item');
    }

    private static function testRepositoryUpdatePersistsStatus(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());
        $created = $repository->create([
            'attendance_id' => 1,
            'status' => 'present',
            'note' => 'Roll call A',
        ]);

        $updated = $repository->update((int) $created->id(), ['status' => 'late']);
        self::assertTrue($updated instanceof AttendanceRecord, 'update returns entity');
        if ($updated instanceof AttendanceRecord) {
            self::assertSame('late', $updated->status(), 'updated status persisted');
            self::assertSame(1, $updated->attendanceId(), 'attendance_id retained on update');
        }
    }

    private static function testRepositoryUpdatePreservesFieldsOnPartialUpdate(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());
        $created = $repository->create([
            'attendance_id' => 1,
            'status' => 'present',
            'note' => 'Roll call A',
            'recorded_by' => 5,
        ]);

        $updated = $repository->update((int) $created->id(), ['status' => 'late']);
        self::assertTrue($updated instanceof AttendanceRecord, 'partial update returns entity');
        if ($updated instanceof AttendanceRecord) {
            self::assertSame('late', $updated->status(), 'status updated');
            self::assertSame('Roll call A', $updated->note(), 'note preserved on status update');
            self::assertSame(5, $updated->recordedBy(), 'recorded_by preserved on status update');
            self::assertSame(1, $updated->attendanceId(), 'attendance_id preserved');
        }
    }

    private static function testRepositoryUpdateMissingIdReturnsNull(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());

        self::assertSame(null, $repository->update(999, ['status' => 'late']), 'update missing id returns null');
    }

    private static function testRepositoryDeleteNotSupportedOnSqlite(): void
    {
        $repository = new AttendanceRecordRepository(self::sqlite());
        $created = $repository->create([
            'attendance_id' => 1,
            'status' => 'present',
            'note' => 'Roll call A',
        ]);

        $result = $repository->delete((int) $created->id());
        self::assertSame(false, $result, 'delete uses NOW() (MySQL-only) so false on SQLite');

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof AttendanceRecord, 'row remains when delete unsupported on SQLite');
    }

    // -------------------------------------------------------------------------
    // Service
    // -------------------------------------------------------------------------

    private static function testServiceNoDatabaseCreateMapsEntity(): void
    {
        $service = new AttendanceRecordService(new AttendanceRecordRepository(null), new AttendanceRecordValidator());

        $result = $service->create(new CreateAttendanceRecordRequest([
            'record_name' => 'Roll call A',
            'attendance_id' => 1,
            'student_id' => 1,
        ]));

        self::assertTrue($result instanceof AttendanceRecord, 'no-db create returns entity');
        if ($result instanceof AttendanceRecord) {
            self::assertSame(1, $result->attendanceId(), 'no-db service attendance_id');
            self::assertSame('present', $result->status(), 'no-db service default status');
        }
    }

    private static function testServiceCreatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true);

        $result = $service->create(new CreateAttendanceRecordRequest([
            'record_name' => 'Roll call C',
            'attendance_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]));

        self::assertTrue($result instanceof AttendanceRecord, 'create returns entity');
        if ($result instanceof AttendanceRecord) {
            self::assertSame(1, $result->attendanceId(), 'created attendance_id');
            self::assertSame('present', $result->status(), 'created status');
        }
        self::assertSame(1, $repository->rows[3]['attendance_id'], 'create stored in repository');
        self::assertSame('CREATE', $logger->lastAction, 'create audit logged');
    }

    private static function testServiceCreateRejectsDuplicateRecordName(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $service = self::serviceWithReferences($repository, null, true);

        try {
            $service->create(new CreateAttendanceRecordRequest([
                'record_name' => 'Roll call A',
                'attendance_id' => 1,
                'student_id' => 1,
            ]));
            self::fail('duplicate record_name should be rejected');
        } catch (AttendanceRecordException $exception) {
            self::assertSame('Attendance record already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceCreateRejectsMissingAttendanceReference(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $service = self::serviceWithReferences($repository, null, false);

        try {
            $service->create(new CreateAttendanceRecordRequest([
                'record_name' => 'Roll call X',
                'attendance_id' => 99,
                'student_id' => 1,
                'status' => 'present',
            ]));
            self::fail('missing attendance reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['attendance_id']), 'attendance_id error present');
            self::assertSame('Attendance not found.', $errors['attendance_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsInvalidPayload(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $service = self::serviceWithReferences($repository, null, true);

        try {
            $service->create(new CreateAttendanceRecordRequest(['status' => 'present']));
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['record_name']), 'record_name error present');
            self::assertTrue(isset($errors['attendance_id']), 'attendance_id error present');
            self::assertTrue(isset($errors['student_id']), 'student_id error present');
        }
    }

    private static function testServiceCreateRejectsInvalidStatus(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $service = self::serviceWithReferences($repository, null, true);

        try {
            $service->create(new CreateAttendanceRecordRequest([
                'record_name' => 'Roll call X',
                'attendance_id' => 1,
                'student_id' => 1,
                'status' => 'active',
            ]));
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceUpdatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true);

        $result = $service->update(new UpdateAttendanceRecordRequest(1, ['status' => 'late']));

        self::assertTrue($result instanceof AttendanceRecord, 'update returns entity');
        if ($result instanceof AttendanceRecord) {
            self::assertSame('late', $result->status(), 'updated status');
            self::assertSame(1, $result->attendanceId(), 'attendance_id retained');
            self::assertSame('Roll call A', $result->note(), 'note retained');
        }
        self::assertSame('late', $repository->rows[1]['status'], 'update stored in repository');
        self::assertSame('UPDATE', $logger->lastAction, 'update audit logged');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);

        $result = $service->update(new UpdateAttendanceRecordRequest(999, ['status' => 'late']));

        self::assertSame(null, $result, 'missing attendance record returns null');
    }

    private static function testServiceUpdateRejectsDuplicateRecordName(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $service = self::serviceWithReferences($repository, null, true);

        try {
            $service->update(new UpdateAttendanceRecordRequest(1, ['record_name' => 'Roll call B']));
            self::fail('renaming a record to an existing name should be rejected');
        } catch (AttendanceRecordException $exception) {
            self::assertSame('Attendance record already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceUpdateRejectsInvalidPayload(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);

        try {
            $service->update(new UpdateAttendanceRecordRequest(1, ['status' => 'active']));
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceDeleteReturnsTrueWhenDeleted(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true);

        $result = $service->delete(1);

        self::assertSame(true, $result, 'delete returns true');
        self::assertSame('DELETE', $logger->lastAction, 'delete audit logged');
    }

    private static function testServiceDeleteReturnsFalseWhenMissing(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);

        $result = $service->delete(999);

        self::assertSame(false, $result, 'missing attendance record delete returns false');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $repository = new FakeAttendanceRecordRepository();
        $service = self::serviceWithReferences($repository, null, true);

        $result = $service->list(new AttendanceRecordListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'list total from repository');
        self::assertSame(1, $result['page'], 'list page');
        self::assertSame(10, $result['per_page'], 'list per_page');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);

        $found = $service->find(1);
        self::assertTrue($found instanceof AttendanceRecord, 'find returns entity');
        if ($found instanceof AttendanceRecord) {
            self::assertSame(1, $found->attendanceId(), 'found attendance_id');
        }

        self::assertSame(null, $service->find(999), 'find missing returns null');
    }

    // -------------------------------------------------------------------------
    // ServiceProvider / DI
    // -------------------------------------------------------------------------

    private static function testServiceProviderRegistersBindings(): void
    {
        $container = new AppContainer();
        $container->set('database', null);
        $container->set(AuditLoggerInterface::class, new FakeAuditLogger());

        (new AttendanceRecordServiceProvider())->register($container);

        $service = $container->get(AttendanceRecordService::class);
        self::assertTrue($service instanceof AttendanceRecordServiceInterface, 'AttendanceRecordService bound to interface');

        $repository = $container->get(AttendanceRecordRepository::class);
        self::assertTrue($repository instanceof AttendanceRecordRepositoryInterface, 'AttendanceRecordRepository bound to interface');

        $controller = $container->get(AttendanceRecordController::class);
        self::assertTrue($controller instanceof AttendanceRecordController, 'AttendanceRecordController bound');

        self::assertTrue($container->get('attendancerecord.validator') instanceof AttendanceRecordValidator, 'attendancerecord.validator alias');
        self::assertTrue($container->get('attendancerecord.repository') instanceof AttendanceRecordRepositoryInterface, 'attendancerecord.repository alias');
        self::assertTrue($container->get('attendancerecord.service') instanceof AttendanceRecordServiceInterface, 'attendancerecord.service alias');
        self::assertTrue($container->get('attendancerecord.controller') instanceof AttendanceRecordController, 'attendancerecord.controller alias');
    }

    // -------------------------------------------------------------------------
    // Controller
    // -------------------------------------------------------------------------

    private static function testControllerIndexSuccess(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);
        $controller = new AttendanceRecordController($service);

        $request = new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null);
        $response = $controller->index($request);

        self::assertSame('success', $response['status'], 'index success status');
        self::assertSame(2, count($response['data']['items']), 'index items count');
        self::assertSame(2, $response['data']['pagination']['total'], 'index total');
    }

    private static function testControllerShowNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);
        $controller = new AttendanceRecordController($service);

        $response = $controller->show(999);

        self::assertSame('error', $response['status'], 'show missing error status');
        self::assertSame('Attendance record not found.', $response['message'], 'show 404 message');
    }

    private static function testControllerStoreValidationError(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);
        $controller = new AttendanceRecordController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'present']);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store validation error status');
        self::assertTrue(isset($response['details']['validation']), 'store validation details present');
    }

    private static function testControllerStoreConflict(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);
        $controller = new AttendanceRecordController($service);

        $request = new RequestHelper([], [], [], [], [
            'record_name' => 'Roll call A',
            'attendance_id' => 1,
            'student_id' => 1,
        ]);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store conflict error status');
        self::assertSame('Attendance record already exists.', $response['message'], 'store conflict message');
    }

    private static function testControllerStoreSuccess(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);
        $controller = new AttendanceRecordController($service);

        $request = new RequestHelper([], [], [], [], [
            'record_name' => 'Roll call C',
            'attendance_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]);
        $response = $controller->store($request);

        self::assertSame('success', $response['status'], 'store success status');
        self::assertSame(1, $response['data']['attendance_id'], 'store success attendance_id');
        self::assertSame('present', $response['data']['status'], 'store success status');
    }

    private static function testControllerUpdateNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);
        $controller = new AttendanceRecordController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'late']);
        $response = $controller->update(999, $request);

        self::assertSame('error', $response['status'], 'update missing error status');
        self::assertSame('Attendance record not found.', $response['message'], 'update missing message');
    }

    private static function testControllerDestroyNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true);
        $controller = new AttendanceRecordController($service);

        $response = $controller->destroy(999);

        self::assertSame('error', $response['status'], 'destroy missing error status');
        self::assertSame('Attendance record not found.', $response['message'], 'destroy missing message');
    }

    // -------------------------------------------------------------------------
    // Router dispatch regression
    // -------------------------------------------------------------------------

    private static function testRouterDispatchRoutes(): void
    {
        $container = new AppContainer();
        $controller = new AttendanceRecordController(self::serviceWithReferences(new FakeAttendanceRecordRepository(), null, true));
        $container->set(AttendanceRecordController::class, $controller);
        $router = new Router($container);

        $index = $router->dispatch('GET', '/attendance-records', new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null));
        self::assertSame('success', $index['status'], 'GET /attendance-records index status');
        self::assertSame(2, $index['data']['pagination']['total'], 'GET /attendance-records index total');

        $show = $router->dispatch('GET', '/attendance-records/1');
        self::assertSame('success', $show['status'], 'GET /attendance-records/1 show status');
        self::assertSame(1, $show['data']['attendance_id'], 'GET /attendance-records/1 attendance_id');

        $store = $router->dispatch('POST', '/attendance-records', new RequestHelper([], [], [], [], [
            'record_name' => 'Roll call C',
            'attendance_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]));
        self::assertSame('success', $store['status'], 'POST /attendance-records store status');
        self::assertSame(1, $store['data']['attendance_id'], 'POST /attendance-records store attendance_id');

        $update = $router->dispatch('PUT', '/attendance-records/1', new RequestHelper([], [], [], [], ['status' => 'late']));
        self::assertSame('success', $update['status'], 'PUT /attendance-records/1 update status');
        self::assertSame('late', $update['data']['status'], 'PUT /attendance-records/1 updated status');

        $destroy = $router->dispatch('DELETE', '/attendance-records/1');
        self::assertSame('success', $destroy['status'], 'DELETE /attendance-records/1 destroy status');

        $missing = $router->dispatch('GET', '/attendance-records/999');
        self::assertSame('error', $missing['status'], 'GET /attendance-records/999 not found status');
        self::assertSame('Attendance record not found.', $missing['message'], 'GET /attendance-records/999 not found message');

        $unknown = $router->dispatch('GET', '/does-not-exist');
        self::assertSame(false, $unknown['success'], 'unknown route error flag');
        self::assertSame(404, $unknown['status'], 'unknown route 404');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function serviceWithReferences(
        AttendanceRecordRepositoryInterface $repository,
        ?FakeAuditLogger $logger,
        bool $attendanceExists
    ): AttendanceRecordService {
        return new AttendanceRecordService(
            $repository,
            new AttendanceRecordValidator(),
            new FakeAttendanceRepository($attendanceExists),
            null,
            $logger
        );
    }

    private static function sqlite(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE attendance_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                attendance_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT "present",
                note TEXT NULL,
                recorded_by INTEGER NULL,
                recorded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TEXT NULL
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

final class FakeAttendanceRecordRepository implements AttendanceRecordRepositoryInterface
{
    public array $rows = [
        1 => ['id' => 1, 'record_name' => 'Roll call A', 'attendance_id' => 1, 'student_id' => 1, 'status' => 'present', 'note' => 'Roll call A', 'recorded_by' => 5, 'recorded_at' => '2026-01-01 08:00:00', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'record_name' => 'Roll call B', 'attendance_id' => 1, 'student_id' => 2, 'status' => 'absent', 'note' => 'Roll call B', 'recorded_by' => 5, 'recorded_at' => '2026-01-01 08:00:00', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        $rows = array_values($this->rows);
        if ($search !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => stripos((string) $row['note'], $search) !== false));
        }
        $items = array_map(fn (array $row): AttendanceRecord => $this->map($row), $rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?AttendanceRecord
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByName(string $name): ?AttendanceRecord
    {
        foreach ($this->rows as $row) {
            if ($row['note'] === $name) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): AttendanceRecord
    {
        $nextId = max(array_keys($this->rows) ?: [0]) + 1;
        $this->rows[$nextId] = [
            'id' => $nextId,
            'record_name' => $attributes['record_name'] ?? null,
            'attendance_id' => $attributes['attendance_id'] ?? null,
            'student_id' => $attributes['student_id'] ?? null,
            'status' => $attributes['status'] ?? 'present',
            'note' => $attributes['note'] ?? null,
            'recorded_by' => $attributes['recorded_by'] ?? null,
            'recorded_at' => '2026-01-01 08:00:00',
            'created_at' => '2026-01-01',
            'updated_at' => '2026-01-01',
        ];

        return $this->map($this->rows[$nextId]);
    }

    public function update(int $id, array $attributes): ?AttendanceRecord
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        foreach (['record_name', 'attendance_id', 'student_id', 'status', 'note', 'recorded_by'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $this->rows[$id][$field] = $attributes[$field];
            }
        }

        return $this->map($this->rows[$id]);
    }

    public function delete(int $id): bool
    {
        if (!isset($this->rows[$id])) {
            return false;
        }
        unset($this->rows[$id]);

        return true;
    }

    private function map(array $row): AttendanceRecord
    {
        return new AttendanceRecord(
            (int) $row['id'],
            $row['record_name'] ?? null,
            isset($row['attendance_id']) ? (int) $row['attendance_id'] : null,
            isset($row['student_id']) ? (int) $row['student_id'] : null,
            $row['status'] ?? 'present',
            $row['note'] ?? null,
            isset($row['recorded_by']) ? (int) $row['recorded_by'] : null,
            $row['recorded_at'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            null
        );
    }
}

final class FakeAttendanceRepository implements AttendanceRepositoryInterface
{
    public function __construct(private bool $exists)
    {
    }

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Attendance
    {
        return $this->exists ? new Attendance($id, '2026-01-01', 1, 1, 1, 1, 'present', null, null, null, null, null) : null;
    }

    public function findByName(string $name): ?Attendance
    {
        return $this->exists ? new Attendance(1, $name, 1, 1, 1, 1, 'present', null, null, null, null, null) : null;
    }

    public function create(array $attributes): Attendance
    {
        return new Attendance(1, $attributes['attendance_date'] ?? null, 1, 1, 1, 1, 'present', null, null, null, null, null);
    }

    public function update(int $id, array $attributes): ?Attendance
    {
        return null;
    }

    public function delete(int $id): bool
    {
        return false;
    }
}

final class FakeAuditLogger implements AuditLoggerInterface
{
    public ?string $lastAction = null;

    public function log(string $action, string $entityType, ?int $entityId, ?array $oldValues = null, ?array $newValues = null): void
    {
        $this->lastAction = $action;
    }

    public function entityToArray(?object $entity): ?array
    {
        return $entity instanceof AttendanceRecord ? ['id' => $entity->id()] : null;
    }
}

AttendanceRecordModuleTest::run();
