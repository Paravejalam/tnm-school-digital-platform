<?php

declare(strict_types=1);

/**
 * Unit tests for the Attendance module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/AttendanceModuleTest.php
 *
 * Covers:
 * - Attendance entity getters
 * - AttendanceResponse shape (id, attendance_date, academic_session_id,
 *   class_id, section_id, student_id, status)
 * - AttendanceListRequest pagination and search parsing
 * - CreateAttendanceRequest / UpdateAttendanceRequest accessors
 * - AttendanceValidator create/update validation
 *   (statuses aligned with migration ENUM: present/absent/late/excused/holiday)
 * - AttendanceException type
 * - AttendanceRepository no-DB behavior
 * - AttendanceRepository SQLite-backed CRUD + search
 * - Partial update preserves remarks/marked_by
 * - Default status is 'present' (matches migration default)
 * - AttendanceService create/update/delete flows (via fake repository)
 * - Duplicate attendance-date checks
 * - Reference validation for session/class/section/student
 * - AttendanceServiceProvider DI bindings and aliases
 * - AttendanceController request/response handling
 * - Router dispatch regression for /attendance resource routes
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\AcademicClass\AcademicClass;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\AcademicSession\AcademicSession;
use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\Attendance\Attendance;
use App\Attendance\AttendanceController;
use App\Attendance\AttendanceException;
use App\Attendance\AttendanceListRequest;
use App\Attendance\AttendanceRepository;
use App\Attendance\AttendanceRepositoryInterface;
use App\Attendance\AttendanceResponse;
use App\Attendance\AttendanceService;
use App\Attendance\AttendanceServiceInterface;
use App\Attendance\AttendanceServiceProvider;
use App\Attendance\AttendanceValidator;
use App\Attendance\CreateAttendanceRequest;
use App\Attendance\UpdateAttendanceRequest;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use App\Core\Router;
use App\Http\RequestHelper;
use App\Section\Section;
use App\Section\SectionRepositoryInterface;
use App\Student\Student;
use App\Student\StudentRepositoryInterface;
use App\Support\AppContainer;

final class AttendanceModuleTest
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
        self::testValidatorRejectsEmptyAttendanceDateOnUpdate();
        self::testValidatorRejectsInvalidStatusOnUpdate();
        self::testExceptionExtendsRuntimeException();
        self::testRepositoryNoDatabaseBehavior();
        self::testRepositoryCreatePersistsAndFindById();
        self::testRepositoryCreateDefaultsStatusToPresent();
        self::testRepositoryFindByNameAndDuplicates();
        self::testRepositoryPaginateWithSearch();
        self::testRepositoryUpdatePersistsStatus();
        self::testRepositoryUpdatePreservesFieldsOnPartialUpdate();
        self::testRepositoryUpdateMissingIdReturnsNull();
        self::testRepositoryDeleteNotSupportedOnSqlite();
        self::testServiceNoDatabaseCreateMapsEntity();
        self::testServiceCreatePersistsAndReturnsEntity();
        self::testServiceCreateRejectsDuplicateDate();
        self::testServiceCreateRejectsMissingSessionReference();
        self::testServiceCreateRejectsMissingClassReference();
        self::testServiceCreateRejectsMissingSectionReference();
        self::testServiceCreateRejectsMissingStudentReference();
        self::testServiceCreateRejectsInvalidPayload();
        self::testServiceCreateRejectsInvalidStatus();
        self::testServiceUpdatePersistsAndReturnsEntity();
        self::testServiceUpdateNotFoundReturnsNull();
        self::testServiceUpdateRejectsDuplicateDate();
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
        $item = new Attendance(1, '2026-01-01', 1, 1, 1, 1, 'present', 'On time', 5, '2026-01-01', '2026-01-02', null);

        self::assertSame(1, $item->id(), 'id');
        self::assertSame('2026-01-01', $item->attendanceDate(), 'attendance_date');
        self::assertSame(1, $item->academicSessionId(), 'academic_session_id');
        self::assertSame(1, $item->classId(), 'class_id');
        self::assertSame(1, $item->sectionId(), 'section_id');
        self::assertSame(1, $item->studentId(), 'student_id');
        self::assertSame('present', $item->status(), 'status');
        self::assertSame('On time', $item->remarks(), 'remarks');
        self::assertSame(5, $item->markedBy(), 'marked_by');
        self::assertSame('2026-01-01', $item->createdAt(), 'created_at');
        self::assertSame('2026-01-02', $item->updatedAt(), 'updated_at');
        self::assertSame(null, $item->deletedAt(), 'deleted_at default null');
    }

    // -------------------------------------------------------------------------
    // Response DTO
    // -------------------------------------------------------------------------

    private static function testResponseExposesExpectedFields(): void
    {
        $item = new Attendance(1, '2026-01-01', 1, 1, 1, 1, 'present', null, null, null, null, null);
        $data = AttendanceResponse::fromEntity($item);

        self::assertSame(1, $data['id'], 'response id');
        self::assertSame('2026-01-01', $data['attendance_date'], 'response attendance_date');
        self::assertSame(1, $data['academic_session_id'], 'response academic_session_id');
        self::assertSame(1, $data['class_id'], 'response class_id');
        self::assertSame(1, $data['section_id'], 'response section_id');
        self::assertSame(1, $data['student_id'], 'response student_id');
        self::assertSame('present', $data['status'], 'response status');
        self::assertSame(7, count($data), 'response has exactly 7 fields');
    }

    private static function testResponseCollection(): void
    {
        $items = [
            new Attendance(1, '2026-01-01', 1, 1, 1, 1, 'present', null, null, null, null, null),
            new Attendance(2, '2026-01-02', 1, 1, 1, 2, 'absent', null, null, null, null, null),
        ];

        $collection = AttendanceResponse::collection($items);

        self::assertSame(2, count($collection), 'collection length');
        self::assertSame('2026-01-01', $collection[0]['attendance_date'], 'first attendance_date');
        self::assertSame('absent', $collection[1]['status'], 'second status');
    }

    // -------------------------------------------------------------------------
    // ListRequest
    // -------------------------------------------------------------------------

    private static function testListRequestParsesQuery(): void
    {
        $request = new AttendanceListRequest([
            'page' => 2,
            'per_page' => 50,
            'search' => '2026-01',
        ]);

        self::assertSame(2, $request->page(), 'page parsed');
        self::assertSame(50, $request->perPage(), 'per_page parsed');
        self::assertSame('2026-01', $request->search(), 'search parsed');

        $default = new AttendanceListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search null');

        $nameFallback = new AttendanceListRequest(['name' => '2026-01']);
        self::assertSame('2026-01', $nameFallback->search(), 'name fallback to search');

        $bounded = new AttendanceListRequest(['page' => 0, 'per_page' => 500]);
        self::assertSame(1, $bounded->page(), 'page clamped to 1');
        self::assertSame(100, $bounded->perPage(), 'per_page capped at 100');
    }

    // -------------------------------------------------------------------------
    // Create/Update requests
    // -------------------------------------------------------------------------

    private static function testCreateRequestPayload(): void
    {
        $payload = ['attendance_date' => '2026-01-01', 'student_id' => 1];
        $request = new CreateAttendanceRequest($payload);

        self::assertSame($payload, $request->payload(), 'create payload passthrough');
    }

    private static function testUpdateRequestIdAndPayload(): void
    {
        $payload = ['status' => 'late'];
        $request = new UpdateAttendanceRequest(7, $payload);

        self::assertSame(7, $request->id(), 'update id');
        self::assertSame($payload, $request->payload(), 'update payload passthrough');
    }

    // -------------------------------------------------------------------------
    // Validator
    // -------------------------------------------------------------------------

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new AttendanceValidator();

        try {
            $validator->validateCreate([
                'attendance_date' => '2026-01-01',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'student_id' => 1,
                'status' => 'present',
            ]);
            $validator->validateCreate([
                'attendance_date' => '2026-01-01',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'student_id' => 1,
            ]);
            self::pass('valid create payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid create payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorAcceptsAllSchemaStatuses(): void
    {
        $validator = new AttendanceValidator();

        try {
            foreach (['present', 'absent', 'late', 'excused', 'holiday'] as $status) {
                $validator->validateCreate([
                    'attendance_date' => '2026-01-01',
                    'academic_session_id' => 1,
                    'class_id' => 1,
                    'section_id' => 1,
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
        $validator = new AttendanceValidator();

        try {
            $validator->validateCreate([]);
            self::fail('empty payload should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['attendance_date']), 'attendance_date error present');
            self::assertTrue(isset($exception->errors()['academic_session_id']), 'academic_session_id error present');
            self::assertTrue(isset($exception->errors()['class_id']), 'class_id error present');
            self::assertTrue(isset($exception->errors()['section_id']), 'section_id error present');
            self::assertTrue(isset($exception->errors()['student_id']), 'student_id error present');
        }

        try {
            $validator->validateCreate(['attendance_date' => '2026-01-01', 'student_id' => 1]);
            self::fail('missing references should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['academic_session_id']), 'academic_session_id error on partial payload');
            self::assertTrue(isset($exception->errors()['class_id']), 'class_id error on partial payload');
        }
    }

    private static function testValidatorRejectsInvalidStatus(): void
    {
        $validator = new AttendanceValidator();

        try {
            $validator->validateCreate([
                'attendance_date' => '2026-01-01',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'student_id' => 1,
                'status' => 'active',
            ]);
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new AttendanceValidator();

        try {
            $validator->validateUpdate(['status' => 'late']);
            $validator->validateUpdate(['remarks' => 'Arrived late']);
            $validator->validateUpdate(['attendance_date' => '2026-01-02']);
            $validator->validateUpdate([]);
            self::pass('valid update payloads accepted');
        } catch (ValidationException $exception) {
            self::fail('valid update payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsEmptyAttendanceDateOnUpdate(): void
    {
        $validator = new AttendanceValidator();

        try {
            $validator->validateUpdate(['attendance_date' => '   ']);
            self::fail('empty attendance_date should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['attendance_date']), 'attendance_date error present');
        }
    }

    private static function testValidatorRejectsInvalidStatusOnUpdate(): void
    {
        $validator = new AttendanceValidator();

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
        $exception = new AttendanceException('Attendance already exists.');

        self::assertTrue($exception instanceof \RuntimeException, 'AttendanceException extends RuntimeException');
        self::assertSame('Attendance already exists.', $exception->getMessage(), 'exception message');
    }

    // -------------------------------------------------------------------------
    // Repository — no-DB
    // -------------------------------------------------------------------------

    private static function testRepositoryNoDatabaseBehavior(): void
    {
        $repository = new AttendanceRepository(null);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repository->paginate(1, 15), 'paginate no-db empty');
        self::assertSame(null, $repository->findById(1), 'findById no-db null');
        self::assertSame(null, $repository->findByName('2026-01-01'), 'findByName no-db null');
        self::assertSame(false, $repository->delete(1), 'delete no-db false');

        $created = $repository->create([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
        ]);
        self::assertTrue($created instanceof Attendance, 'create no-db returns entity');
        if ($created instanceof Attendance) {
            self::assertSame('2026-01-01', $created->attendanceDate(), 'no-db created attendance_date');
            self::assertSame(1, $created->studentId(), 'no-db created student_id');
            self::assertSame('present', $created->status(), 'no-db created default status');
            self::assertSame(null, $created->id(), 'no-db created has no id');
        }

        self::assertSame(null, $repository->update(1, ['status' => 'late']), 'update no-db null');
    }

    // -------------------------------------------------------------------------
    // Repository — SQLite-backed
    // -------------------------------------------------------------------------

    private static function testRepositoryCreatePersistsAndFindById(): void
    {
        $repository = new AttendanceRepository(self::sqlite());
        $created = $repository->create([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
            'remarks' => 'On time',
            'marked_by' => 5,
        ]);

        self::assertTrue($created instanceof Attendance, 'create returns entity');
        self::assertTrue($created->id() !== null, 'create assigns id');
        if ($created instanceof Attendance) {
            self::assertSame('2026-01-01', $created->attendanceDate(), 'persisted attendance_date');
            self::assertSame(1, $created->academicSessionId(), 'persisted academic_session_id');
            self::assertSame(1, $created->classId(), 'persisted class_id');
            self::assertSame(1, $created->sectionId(), 'persisted section_id');
            self::assertSame(1, $created->studentId(), 'persisted student_id');
            self::assertSame('present', $created->status(), 'persisted status');
            self::assertSame('On time', $created->remarks(), 'persisted remarks');
            self::assertSame(5, $created->markedBy(), 'persisted marked_by');
        }

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Attendance, 'findById returns entity');
        if ($found instanceof Attendance) {
            self::assertSame('2026-01-01', $found->attendanceDate(), 'findById attendance_date');
            self::assertSame(1, $found->studentId(), 'findById student_id');
            self::assertSame('present', $found->status(), 'findById status');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function testRepositoryCreateDefaultsStatusToPresent(): void
    {
        $repository = new AttendanceRepository(self::sqlite());
        $created = $repository->create([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
        ]);

        self::assertTrue($created instanceof Attendance, 'create returns entity');
        if ($created instanceof Attendance) {
            self::assertSame('present', $created->status(), 'default status is present (matches schema)');
        }
    }

    private static function testRepositoryFindByNameAndDuplicates(): void
    {
        $repository = new AttendanceRepository(self::sqlite());
        $repository->create([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]);
        $repository->create([
            'attendance_date' => '2026-01-02',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 2,
            'status' => 'absent',
        ]);

        $found = $repository->findByName('2026-01-01');
        self::assertTrue($found instanceof Attendance, 'findByName finds existing');
        if ($found instanceof Attendance) {
            self::assertSame(1, $found->id(), 'findByName id');
        }

        self::assertSame(null, $repository->findByName('2026-01-03'), 'findByName missing null');
    }

    private static function testRepositoryPaginateWithSearch(): void
    {
        $repository = new AttendanceRepository(self::sqlite());
        $repository->create([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]);
        $repository->create([
            'attendance_date' => '2026-01-02',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 2,
            'status' => 'present',
        ]);
        $repository->create([
            'attendance_date' => '2026-01-03',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 3,
            'status' => 'present',
        ]);

        $all = $repository->paginate(1, 15);
        self::assertSame(3, $all['total'], 'paginate total');
        self::assertSame(3, count($all['items']), 'paginate item count');

        $filtered = $repository->paginate(1, 15, '2026-01-01');
        self::assertSame(1, $filtered['total'], 'search total');
        self::assertSame(1, count($filtered['items']), 'search item count');

        $paged = $repository->paginate(2, 2);
        self::assertSame(3, $paged['total'], 'page 2 total retains full count');
        self::assertSame(1, count($paged['items']), 'page 2 has 1 item');
    }

    private static function testRepositoryUpdatePersistsStatus(): void
    {
        $repository = new AttendanceRepository(self::sqlite());
        $created = $repository->create([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]);

        $updated = $repository->update((int) $created->id(), ['status' => 'late']);
        self::assertTrue($updated instanceof Attendance, 'update returns entity');
        if ($updated instanceof Attendance) {
            self::assertSame('late', $updated->status(), 'updated status persisted');
            self::assertSame('2026-01-01', $updated->attendanceDate(), 'attendance_date retained on update');
            self::assertSame(1, $updated->studentId(), 'student_id retained on update');
        }
    }

    private static function testRepositoryUpdatePreservesFieldsOnPartialUpdate(): void
    {
        $repository = new AttendanceRepository(self::sqlite());
        $created = $repository->create([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
            'remarks' => 'On time',
            'marked_by' => 5,
        ]);

        $updated = $repository->update((int) $created->id(), ['status' => 'late']);
        self::assertTrue($updated instanceof Attendance, 'partial update returns entity');
        if ($updated instanceof Attendance) {
            self::assertSame('late', $updated->status(), 'status updated');
            self::assertSame('On time', $updated->remarks(), 'remarks preserved on status update');
            self::assertSame(5, $updated->markedBy(), 'marked_by preserved on status update');
            self::assertSame(1, $updated->academicSessionId(), 'academic_session_id preserved');
            self::assertSame(1, $updated->classId(), 'class_id preserved');
            self::assertSame(1, $updated->sectionId(), 'section_id preserved');
        }
    }

    private static function testRepositoryUpdateMissingIdReturnsNull(): void
    {
        $repository = new AttendanceRepository(self::sqlite());

        self::assertSame(null, $repository->update(999, ['status' => 'late']), 'update missing id returns null');
    }

    private static function testRepositoryDeleteNotSupportedOnSqlite(): void
    {
        $repository = new AttendanceRepository(self::sqlite());
        $created = $repository->create([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]);

        $result = $repository->delete((int) $created->id());
        self::assertSame(false, $result, 'delete uses NOW() (MySQL-only) so false on SQLite');

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Attendance, 'row remains when delete unsupported on SQLite');
    }

    // -------------------------------------------------------------------------
    // Service
    // -------------------------------------------------------------------------

    private static function testServiceNoDatabaseCreateMapsEntity(): void
    {
        $service = new AttendanceService(new AttendanceRepository(null), new AttendanceValidator());

        $result = $service->create(new CreateAttendanceRequest([
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
        ]));

        self::assertTrue($result instanceof Attendance, 'no-db create returns entity');
        if ($result instanceof Attendance) {
            self::assertSame('2026-01-01', $result->attendanceDate(), 'no-db service attendance_date');
            self::assertSame('present', $result->status(), 'no-db service default status');
        }
    }

    private static function testServiceCreatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeAttendanceRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true);

        $result = $service->create(new CreateAttendanceRequest([
            'attendance_date' => '2026-01-03',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]));

        self::assertTrue($result instanceof Attendance, 'create returns entity');
        if ($result instanceof Attendance) {
            self::assertSame('2026-01-03', $result->attendanceDate(), 'created attendance_date');
            self::assertSame('present', $result->status(), 'created status');
        }
        self::assertSame('2026-01-03', $repository->rows[3]['attendance_date'], 'create stored in repository');
        self::assertSame('CREATE', $logger->lastAction, 'create audit logged');
    }

    private static function testServiceCreateRejectsDuplicateDate(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true);

        try {
            $service->create(new CreateAttendanceRequest([
                'attendance_date' => '2026-01-01',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'student_id' => 1,
            ]));
            self::fail('duplicate attendance date should be rejected');
        } catch (AttendanceException $exception) {
            self::assertSame('Attendance already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceCreateRejectsMissingSessionReference(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, false, true, true, true);

        try {
            $service->create(new CreateAttendanceRequest([
                'attendance_date' => '2026-01-10',
                'academic_session_id' => 99,
                'class_id' => 1,
                'section_id' => 1,
                'student_id' => 1,
            ]));
            self::fail('missing academic session reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['academic_session_id']), 'academic_session_id error present');
            self::assertSame('Academic session not found.', $errors['academic_session_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsMissingClassReference(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, true, false, true, true);

        try {
            $service->create(new CreateAttendanceRequest([
                'attendance_date' => '2026-01-10',
                'academic_session_id' => 1,
                'class_id' => 99,
                'section_id' => 1,
                'student_id' => 1,
            ]));
            self::fail('missing academic class reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['class_id']), 'class_id error present');
            self::assertSame('Academic class not found.', $errors['class_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsMissingSectionReference(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, true, true, false, true);

        try {
            $service->create(new CreateAttendanceRequest([
                'attendance_date' => '2026-01-10',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 99,
                'student_id' => 1,
            ]));
            self::fail('missing section reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['section_id']), 'section_id error present');
            self::assertSame('Section not found.', $errors['section_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsMissingStudentReference(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, false);

        try {
            $service->create(new CreateAttendanceRequest([
                'attendance_date' => '2026-01-10',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'student_id' => 99,
            ]));
            self::fail('missing student reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['student_id']), 'student_id error present');
            self::assertSame('Student not found.', $errors['student_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsInvalidPayload(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true);

        try {
            $service->create(new CreateAttendanceRequest(['status' => 'present']));
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['attendance_date']), 'attendance_date error present');
            self::assertTrue(isset($errors['student_id']), 'student_id error present');
        }
    }

    private static function testServiceCreateRejectsInvalidStatus(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true);

        try {
            $service->create(new CreateAttendanceRequest([
                'attendance_date' => '2026-01-10',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
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
        $repository = new FakeAttendanceRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true);

        $result = $service->update(new UpdateAttendanceRequest(1, ['status' => 'late']));

        self::assertTrue($result instanceof Attendance, 'update returns entity');
        if ($result instanceof Attendance) {
            self::assertSame('late', $result->status(), 'updated status');
            self::assertSame('2026-01-01', $result->attendanceDate(), 'attendance_date retained');
            self::assertSame('On time', $result->remarks(), 'remarks retained');
        }
        self::assertSame('late', $repository->rows[1]['status'], 'update stored in repository');
        self::assertSame('UPDATE', $logger->lastAction, 'update audit logged');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);

        $result = $service->update(new UpdateAttendanceRequest(999, ['status' => 'late']));

        self::assertSame(null, $result, 'missing attendance returns null');
    }

    private static function testServiceUpdateRejectsDuplicateDate(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true);

        try {
            $service->update(new UpdateAttendanceRequest(1, ['attendance_date' => '2026-01-02']));
            self::fail('moving an attendance to an existing date should be rejected');
        } catch (AttendanceException $exception) {
            self::assertSame('Attendance already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceUpdateRejectsInvalidPayload(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);

        try {
            $service->update(new UpdateAttendanceRequest(1, ['status' => 'active']));
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceDeleteReturnsTrueWhenDeleted(): void
    {
        $repository = new FakeAttendanceRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true);

        $result = $service->delete(1);

        self::assertSame(true, $result, 'delete returns true');
        self::assertSame('DELETE', $logger->lastAction, 'delete audit logged');
    }

    private static function testServiceDeleteReturnsFalseWhenMissing(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);

        $result = $service->delete(999);

        self::assertSame(false, $result, 'missing attendance delete returns false');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $repository = new FakeAttendanceRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true);

        $result = $service->list(new AttendanceListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'list total from repository');
        self::assertSame(1, $result['page'], 'list page');
        self::assertSame(10, $result['per_page'], 'list per_page');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);

        $found = $service->find(1);
        self::assertTrue($found instanceof Attendance, 'find returns entity');
        if ($found instanceof Attendance) {
            self::assertSame('2026-01-01', $found->attendanceDate(), 'found attendance_date');
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

        (new AttendanceServiceProvider())->register($container);

        $service = $container->get(AttendanceService::class);
        self::assertTrue($service instanceof AttendanceServiceInterface, 'AttendanceService bound to interface');

        $repository = $container->get(AttendanceRepository::class);
        self::assertTrue($repository instanceof AttendanceRepositoryInterface, 'AttendanceRepository bound to interface');

        $controller = $container->get(AttendanceController::class);
        self::assertTrue($controller instanceof AttendanceController, 'AttendanceController bound');

        self::assertTrue($container->get('attendance.validator') instanceof AttendanceValidator, 'attendance.validator alias');
        self::assertTrue($container->get('attendance.repository') instanceof AttendanceRepositoryInterface, 'attendance.repository alias');
        self::assertTrue($container->get('attendance.service') instanceof AttendanceServiceInterface, 'attendance.service alias');
        self::assertTrue($container->get('attendance.controller') instanceof AttendanceController, 'attendance.controller alias');
    }

    // -------------------------------------------------------------------------
    // Controller
    // -------------------------------------------------------------------------

    private static function testControllerIndexSuccess(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);
        $controller = new AttendanceController($service);

        $request = new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null);
        $response = $controller->index($request);

        self::assertSame('success', $response['status'], 'index success status');
        self::assertSame(2, count($response['data']['items']), 'index items count');
        self::assertSame(2, $response['data']['pagination']['total'], 'index total');
    }

    private static function testControllerShowNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);
        $controller = new AttendanceController($service);

        $response = $controller->show(999);

        self::assertSame('error', $response['status'], 'show missing error status');
        self::assertSame('Attendance not found.', $response['message'], 'show 404 message');
    }

    private static function testControllerStoreValidationError(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);
        $controller = new AttendanceController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'present']);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store validation error status');
        self::assertTrue(isset($response['details']['validation']), 'store validation details present');
    }

    private static function testControllerStoreConflict(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);
        $controller = new AttendanceController($service);

        $request = new RequestHelper([], [], [], [], [
            'attendance_date' => '2026-01-01',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
        ]);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store conflict error status');
        self::assertSame('Attendance already exists.', $response['message'], 'store conflict message');
    }

    private static function testControllerStoreSuccess(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);
        $controller = new AttendanceController($service);

        $request = new RequestHelper([], [], [], [], [
            'attendance_date' => '2026-01-03',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]);
        $response = $controller->store($request);

        self::assertSame('success', $response['status'], 'store success status');
        self::assertSame('2026-01-03', $response['data']['attendance_date'], 'store success attendance_date');
        self::assertSame('present', $response['data']['status'], 'store success status');
    }

    private static function testControllerUpdateNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);
        $controller = new AttendanceController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'late']);
        $response = $controller->update(999, $request);

        self::assertSame('error', $response['status'], 'update missing error status');
        self::assertSame('Attendance not found.', $response['message'], 'update missing message');
    }

    private static function testControllerDestroyNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true);
        $controller = new AttendanceController($service);

        $response = $controller->destroy(999);

        self::assertSame('error', $response['status'], 'destroy missing error status');
        self::assertSame('Attendance not found.', $response['message'], 'destroy missing message');
    }

    // -------------------------------------------------------------------------
    // Router dispatch regression
    // -------------------------------------------------------------------------

    private static function testRouterDispatchRoutes(): void
    {
        $container = new AppContainer();
        $controller = new AttendanceController(self::serviceWithReferences(new FakeAttendanceRepository(), null, true, true, true, true));
        $container->set(AttendanceController::class, $controller);
        $router = new Router($container);

        $index = $router->dispatch('GET', '/attendance', new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null));
        self::assertSame('success', $index['status'], 'GET /attendance index status');
        self::assertSame(2, $index['data']['pagination']['total'], 'GET /attendance index total');

        $show = $router->dispatch('GET', '/attendance/1');
        self::assertSame('success', $show['status'], 'GET /attendance/1 show status');
        self::assertSame('2026-01-01', $show['data']['attendance_date'], 'GET /attendance/1 attendance_date');

        $store = $router->dispatch('POST', '/attendance', new RequestHelper([], [], [], [], [
            'attendance_date' => '2026-01-03',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'student_id' => 1,
            'status' => 'present',
        ]));
        self::assertSame('success', $store['status'], 'POST /attendance store status');
        self::assertSame('2026-01-03', $store['data']['attendance_date'], 'POST /attendance store attendance_date');

        $update = $router->dispatch('PUT', '/attendance/1', new RequestHelper([], [], [], [], ['status' => 'late']));
        self::assertSame('success', $update['status'], 'PUT /attendance/1 update status');
        self::assertSame('late', $update['data']['status'], 'PUT /attendance/1 updated status');

        $destroy = $router->dispatch('DELETE', '/attendance/1');
        self::assertSame('success', $destroy['status'], 'DELETE /attendance/1 destroy status');

        $missing = $router->dispatch('GET', '/attendance/999');
        self::assertSame('error', $missing['status'], 'GET /attendance/999 not found status');
        self::assertSame('Attendance not found.', $missing['message'], 'GET /attendance/999 not found message');

        $unknown = $router->dispatch('GET', '/does-not-exist');
        self::assertSame(false, $unknown['success'], 'unknown route error flag');
        self::assertSame(404, $unknown['status'], 'unknown route 404');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function serviceWithReferences(
        AttendanceRepositoryInterface $repository,
        ?FakeAuditLogger $logger,
        bool $sessionExists,
        bool $classExists,
        bool $sectionExists,
        bool $studentExists
    ): AttendanceService {
        return new AttendanceService(
            $repository,
            new AttendanceValidator(),
            new FakeAcademicSessionRepository($sessionExists),
            new FakeAcademicClassRepository($classExists),
            new FakeSectionRepository($sectionExists),
            new FakeStudentRepository($studentExists),
            null,
            $logger
        );
    }

    private static function sqlite(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE attendance (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                attendance_date TEXT NOT NULL,
                academic_session_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                section_id INTEGER NULL,
                student_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT "present",
                remarks TEXT NULL,
                marked_by INTEGER NULL,
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

final class FakeAttendanceRepository implements AttendanceRepositoryInterface
{
    public array $rows = [
        1 => ['id' => 1, 'attendance_date' => '2026-01-01', 'academic_session_id' => 1, 'class_id' => 1, 'section_id' => 1, 'student_id' => 1, 'status' => 'present', 'remarks' => 'On time', 'marked_by' => 5, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'attendance_date' => '2026-01-02', 'academic_session_id' => 1, 'class_id' => 1, 'section_id' => 1, 'student_id' => 2, 'status' => 'absent', 'remarks' => null, 'marked_by' => 5, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        $rows = array_values($this->rows);
        if ($search !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => stripos((string) $row['attendance_date'], $search) !== false));
        }
        $items = array_map(fn (array $row): Attendance => $this->map($row), $rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Attendance
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByName(string $name): ?Attendance
    {
        foreach ($this->rows as $row) {
            if ($row['attendance_date'] === $name) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): Attendance
    {
        $nextId = max(array_keys($this->rows) ?: [0]) + 1;
        $this->rows[$nextId] = [
            'id' => $nextId,
            'attendance_date' => $attributes['attendance_date'] ?? null,
            'academic_session_id' => $attributes['academic_session_id'] ?? null,
            'class_id' => $attributes['class_id'] ?? null,
            'section_id' => $attributes['section_id'] ?? null,
            'student_id' => $attributes['student_id'] ?? null,
            'status' => $attributes['status'] ?? 'present',
            'remarks' => $attributes['remarks'] ?? null,
            'marked_by' => $attributes['marked_by'] ?? null,
            'created_at' => '2026-01-01',
            'updated_at' => '2026-01-01',
        ];

        return $this->map($this->rows[$nextId]);
    }

    public function update(int $id, array $attributes): ?Attendance
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        foreach (['attendance_date', 'academic_session_id', 'class_id', 'section_id', 'student_id', 'status', 'remarks', 'marked_by'] as $field) {
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

    private function map(array $row): Attendance
    {
        return new Attendance(
            (int) $row['id'],
            $row['attendance_date'],
            isset($row['academic_session_id']) ? (int) $row['academic_session_id'] : null,
            isset($row['class_id']) ? (int) $row['class_id'] : null,
            isset($row['section_id']) ? (int) $row['section_id'] : null,
            isset($row['student_id']) ? (int) $row['student_id'] : null,
            $row['status'] ?? 'present',
            $row['remarks'] ?? null,
            isset($row['marked_by']) ? (int) $row['marked_by'] : null,
            $row['created_at'],
            $row['updated_at'],
            null
        );
    }
}

final class FakeAcademicSessionRepository implements AcademicSessionRepositoryInterface
{
    public function __construct(private bool $exists)
    {
    }

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?AcademicSession
    {
        return $this->exists ? new AcademicSession($id, '2026-2027', 'active', '2026-01-01', '2027-03-31', 1) : null;
    }

    public function findByName(string $name): ?AcademicSession
    {
        return $this->exists ? new AcademicSession(1, $name, 'active', '2026-01-01', '2027-03-31', 1) : null;
    }

    public function create(array $attributes): AcademicSession
    {
        return new AcademicSession(1, $attributes['session_name'] ?? null, 'active', null, null, 1);
    }

    public function update(int $id, array $attributes): ?AcademicSession
    {
        return null;
    }

    public function delete(int $id): bool
    {
        return false;
    }
}

final class FakeAcademicClassRepository implements AcademicClassRepositoryInterface
{
    public function __construct(private bool $exists)
    {
    }

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?AcademicClass
    {
        return $this->exists ? new AcademicClass($id, 'Class 1', null, 1, 'active') : null;
    }

    public function findByName(string $name): ?AcademicClass
    {
        return $this->exists ? new AcademicClass(1, $name, null, 1, 'active') : null;
    }

    public function create(array $attributes): AcademicClass
    {
        return new AcademicClass(1, $attributes['class_name'] ?? null, null, 1, 'active');
    }

    public function update(int $id, array $attributes): ?AcademicClass
    {
        return null;
    }

    public function delete(int $id): bool
    {
        return false;
    }
}

final class FakeSectionRepository implements SectionRepositoryInterface
{
    public function __construct(private bool $exists)
    {
    }

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Section
    {
        return $this->exists ? new Section($id, 'A', null, 1, 'active', 30, null, null, null) : null;
    }

    public function findByName(string $name): ?Section
    {
        return $this->exists ? new Section(1, $name, null, 1, 'active', 30, null, null, null) : null;
    }

    public function create(array $attributes): Section
    {
        return new Section(1, $attributes['section_name'] ?? null, null, 1, 'active', 30, null, null, null);
    }

    public function update(int $id, array $attributes): ?Section
    {
        return null;
    }

    public function delete(int $id): bool
    {
        return false;
    }
}

final class FakeStudentRepository implements StudentRepositoryInterface
{
    public function __construct(private bool $exists)
    {
    }

    public function paginate(int $page, int $perPage, ?string $name = null, ?string $admissionNumber = null): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Student
    {
        return $this->exists ? new Student($id, 'ADM-001', 'John', 'Doe', null, null, null, null, 'active') : null;
    }

    public function findByAdmissionNumber(string $admissionNumber): ?Student
    {
        return $this->exists ? new Student(1, $admissionNumber, 'John', 'Doe', null, null, null, null, 'active') : null;
    }

    public function create(array $attributes): Student
    {
        return new Student(1, $attributes['admission_number'] ?? null, null, null, null, null, null, null, 'active');
    }

    public function update(int $id, array $attributes): ?Student
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
        return $entity instanceof Attendance ? ['id' => $entity->id()] : null;
    }
}

AttendanceModuleTest::run();
