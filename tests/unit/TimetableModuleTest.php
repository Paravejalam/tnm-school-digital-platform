<?php

declare(strict_types=1);

/**
 * Unit tests for the Timetable module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/TimetableModuleTest.php
 *
 * Covers:
 * - Timetable entity getters
 * - TimetableResponse shape (id, timetable_name, academic_session_id,
 *   class_id, section_id, subject_id, teacher_id, status)
 * - TimetableListRequest pagination and search parsing
 * - CreateTimetableRequest / UpdateTimetableRequest accessors
 * - TimetableValidator create/update validation
 *   (statuses aligned with migration ENUM: active/inactive/draft)
 * - TimetableException type
 * - TimetableRepository no-DB behavior
 * - TimetableRepository SQLite-backed CRUD + search
 * - Partial update preserves all columns
 * - Default status is 'active' (matches migration default)
 * - TimetableService create/update/delete flows (via fake repository)
 * - Duplicate timetable_name checks
 * - Reference validation for session/class/section/subject/teacher
 * - TimetableServiceProvider DI bindings and aliases
 * - TimetableController request/response handling
 * - Router dispatch regression for /timetables resource routes
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\AcademicClass\AcademicClass;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\AcademicSession\AcademicSession;
use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use App\Core\Router;
use App\Http\RequestHelper;
use App\Section\Section;
use App\Section\SectionRepositoryInterface;
use App\Subject\Subject;
use App\Subject\SubjectRepositoryInterface;
use App\Support\AppContainer;
use App\Teacher\Teacher;
use App\Teacher\TeacherRepositoryInterface;
use App\Timetable\CreateTimetableRequest;
use App\Timetable\Timetable;
use App\Timetable\TimetableController;
use App\Timetable\TimetableException;
use App\Timetable\TimetableListRequest;
use App\Timetable\TimetableRepository;
use App\Timetable\TimetableRepositoryInterface;
use App\Timetable\TimetableResponse;
use App\Timetable\TimetableService;
use App\Timetable\TimetableServiceInterface;
use App\Timetable\TimetableServiceProvider;
use App\Timetable\TimetableValidator;
use App\Timetable\UpdateTimetableRequest;

final class TimetableModuleTest
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
        self::testValidatorRejectsEmptyTimetableNameOnUpdate();
        self::testValidatorRejectsInvalidStatusOnUpdate();
        self::testExceptionExtendsRuntimeException();
        self::testRepositoryNoDatabaseBehavior();
        self::testRepositoryCreatePersistsAndFindById();
        self::testRepositoryCreateDefaultsStatusToActive();
        self::testRepositoryFindByNameAndDuplicates();
        self::testRepositoryPaginateWithSearch();
        self::testRepositoryUpdatePersistsStatus();
        self::testRepositoryUpdatePreservesFieldsOnPartialUpdate();
        self::testRepositoryUpdateMissingIdReturnsNull();
        self::testRepositoryDeleteNotSupportedOnSqlite();
        self::testServiceNoDatabaseCreateMapsEntity();
        self::testServiceCreatePersistsAndReturnsEntity();
        self::testServiceCreateRejectsDuplicateName();
        self::testServiceCreateRejectsMissingSessionReference();
        self::testServiceCreateRejectsMissingClassReference();
        self::testServiceCreateRejectsMissingSectionReference();
        self::testServiceCreateRejectsMissingSubjectReference();
        self::testServiceCreateRejectsMissingTeacherReference();
        self::testServiceCreateRejectsInvalidPayload();
        self::testServiceCreateRejectsInvalidStatus();
        self::testServiceUpdatePersistsAndReturnsEntity();
        self::testServiceUpdateNotFoundReturnsNull();
        self::testServiceUpdateRejectsDuplicateName();
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
        $item = new Timetable(1, 'Morning Timetable', 1, 1, 1, 1, 1, 'active', '2026-01-01', '2026-01-02', null);

        self::assertSame(1, $item->id(), 'id');
        self::assertSame('Morning Timetable', $item->timetableName(), 'timetable_name');
        self::assertSame(1, $item->academicSessionId(), 'academic_session_id');
        self::assertSame(1, $item->classId(), 'class_id');
        self::assertSame(1, $item->sectionId(), 'section_id');
        self::assertSame(1, $item->subjectId(), 'subject_id');
        self::assertSame(1, $item->teacherId(), 'teacher_id');
        self::assertSame('active', $item->status(), 'status');
        self::assertSame('2026-01-01', $item->createdAt(), 'created_at');
        self::assertSame('2026-01-02', $item->updatedAt(), 'updated_at');
        self::assertSame(null, $item->deletedAt(), 'deleted_at default null');
    }

    // -------------------------------------------------------------------------
    // Response DTO
    // -------------------------------------------------------------------------

    private static function testResponseExposesExpectedFields(): void
    {
        $item = new Timetable(1, 'Morning Timetable', 1, 1, 1, 1, 1, 'active', null, null, null);
        $data = TimetableResponse::fromEntity($item);

        self::assertSame(1, $data['id'], 'response id');
        self::assertSame('Morning Timetable', $data['timetable_name'], 'response timetable_name');
        self::assertSame(1, $data['academic_session_id'], 'response academic_session_id');
        self::assertSame(1, $data['class_id'], 'response class_id');
        self::assertSame(1, $data['section_id'], 'response section_id');
        self::assertSame(1, $data['subject_id'], 'response subject_id');
        self::assertSame(1, $data['teacher_id'], 'response teacher_id');
        self::assertSame('active', $data['status'], 'response status');
        self::assertSame(8, count($data), 'response has exactly 8 fields');
    }

    private static function testResponseCollection(): void
    {
        $items = [
            new Timetable(1, 'Morning Timetable', 1, 1, 1, 1, 1, 'active', null, null, null),
            new Timetable(2, 'Afternoon Timetable', 1, 1, 1, 2, 2, 'draft', null, null, null),
        ];

        $collection = TimetableResponse::collection($items);

        self::assertSame(2, count($collection), 'collection length');
        self::assertSame('Morning Timetable', $collection[0]['timetable_name'], 'first timetable_name');
        self::assertSame('draft', $collection[1]['status'], 'second status');
    }

    // -------------------------------------------------------------------------
    // ListRequest
    // -------------------------------------------------------------------------

    private static function testListRequestParsesQuery(): void
    {
        $request = new TimetableListRequest([
            'page' => 2,
            'per_page' => 50,
            'search' => 'Morning',
        ]);

        self::assertSame(2, $request->page(), 'page parsed');
        self::assertSame(50, $request->perPage(), 'per_page parsed');
        self::assertSame('Morning', $request->search(), 'search parsed');

        $default = new TimetableListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search null');

        $nameFallback = new TimetableListRequest(['name' => 'Morning']);
        self::assertSame('Morning', $nameFallback->search(), 'name fallback to search');

        $bounded = new TimetableListRequest(['page' => 0, 'per_page' => 500]);
        self::assertSame(1, $bounded->page(), 'page clamped to 1');
        self::assertSame(100, $bounded->perPage(), 'per_page capped at 100');
    }

    // -------------------------------------------------------------------------
    // Create/Update requests
    // -------------------------------------------------------------------------

    private static function testCreateRequestPayload(): void
    {
        $payload = ['timetable_name' => 'Morning Timetable', 'academic_session_id' => 1];
        $request = new CreateTimetableRequest($payload);

        self::assertSame($payload, $request->payload(), 'create payload passthrough');
    }

    private static function testUpdateRequestIdAndPayload(): void
    {
        $payload = ['status' => 'draft'];
        $request = new UpdateTimetableRequest(7, $payload);

        self::assertSame(7, $request->id(), 'update id');
        self::assertSame($payload, $request->payload(), 'update payload passthrough');
    }

    // -------------------------------------------------------------------------
    // Validator
    // -------------------------------------------------------------------------

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new TimetableValidator();

        try {
            $validator->validateCreate([
                'timetable_name' => 'Morning Timetable',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'subject_id' => 1,
                'teacher_id' => 1,
                'status' => 'active',
            ]);
            self::pass('valid create payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid create payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorAcceptsAllSchemaStatuses(): void
    {
        $validator = new TimetableValidator();

        try {
            foreach (['active', 'inactive', 'draft'] as $status) {
                $validator->validateCreate([
                    'timetable_name' => 'Morning Timetable',
                    'academic_session_id' => 1,
                    'class_id' => 1,
                    'section_id' => 1,
                    'subject_id' => 1,
                    'teacher_id' => 1,
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
        $validator = new TimetableValidator();

        try {
            $validator->validateCreate(['status' => 'active']);
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['timetable_name']), 'timetable_name error present');
            self::assertTrue(isset($errors['academic_session_id']), 'academic_session_id error present');
            self::assertTrue(isset($errors['class_id']), 'class_id error present');
            self::assertTrue(isset($errors['section_id']), 'section_id error present');
            self::assertTrue(isset($errors['subject_id']), 'subject_id error present');
            self::assertTrue(isset($errors['teacher_id']), 'teacher_id error present');
        }
    }

    private static function testValidatorRejectsInvalidStatus(): void
    {
        $validator = new TimetableValidator();

        try {
            $validator->validateCreate([
                'timetable_name' => 'Morning Timetable',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'subject_id' => 1,
                'teacher_id' => 1,
                'status' => 'present',
            ]);
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['status']), 'status error present');
            self::assertSame('Status must be active, inactive or draft.', $errors['status'][0], 'status error message');
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new TimetableValidator();

        try {
            $validator->validateUpdate(['status' => 'draft']);
            $validator->validateUpdate(['timetable_name' => 'Evening Timetable']);
            $validator->validateUpdate(['status' => 'inactive', 'timetable_name' => 'Weekend Timetable']);
            self::pass('valid update payloads accepted');
        } catch (ValidationException $exception) {
            self::fail('valid update payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsEmptyTimetableNameOnUpdate(): void
    {
        $validator = new TimetableValidator();

        try {
            $validator->validateUpdate(['timetable_name' => '   ']);
            self::fail('empty timetable_name should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['timetable_name']), 'timetable_name error present');
            self::assertSame('Timetable name cannot be empty.', $errors['timetable_name'][0], 'timetable_name error message');
        }
    }

    private static function testValidatorRejectsInvalidStatusOnUpdate(): void
    {
        $validator = new TimetableValidator();

        try {
            $validator->validateUpdate(['status' => 'present']);
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
        self::assertTrue(new TimetableException('boom') instanceof \RuntimeException, 'TimetableException extends RuntimeException');
        self::assertSame('boom', (new TimetableException('boom'))->getMessage(), 'exception message');
    }

    // -------------------------------------------------------------------------
    // Repository — no DB
    // -------------------------------------------------------------------------

    private static function testRepositoryNoDatabaseBehavior(): void
    {
        $repository = new TimetableRepository(null);

        self::assertSame(0, $repository->paginate(1, 15)['total'], 'paginate no-db empty');
        self::assertSame([], $repository->paginate(1, 15)['items'], 'paginate no-db items');
        self::assertSame(null, $repository->findById(1), 'findById no-db null');
        self::assertSame(null, $repository->findByName('Morning Timetable'), 'findByName no-db null');
        self::assertSame(false, $repository->delete(1), 'delete no-db false');

        $created = $repository->create([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);
        self::assertTrue($created instanceof Timetable, 'create no-db returns entity');
        if ($created instanceof Timetable) {
            self::assertSame(null, $created->id(), 'no-db created has no id');
            self::assertSame('Morning Timetable', $created->timetableName(), 'no-db created timetable_name');
            self::assertSame(1, $created->academicSessionId(), 'no-db created academic_session_id');
            self::assertSame('active', $created->status(), 'no-db created default status');
        }

        self::assertSame(null, $repository->update(1, ['status' => 'draft']), 'update no-db null');
    }

    // -------------------------------------------------------------------------
    // Repository — SQLite CRUD
    // -------------------------------------------------------------------------

    private static function testRepositoryCreatePersistsAndFindById(): void
    {
        $repository = new TimetableRepository(self::sqlite());
        $created = $repository->create([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'draft',
        ]);

        self::assertTrue($created instanceof Timetable, 'create returns entity');
        if ($created instanceof Timetable) {
            self::assertTrue($created->id() > 0, 'create assigns id');
        }

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Timetable, 'findById returns entity');
        if ($found instanceof Timetable) {
            self::assertSame('Morning Timetable', $found->timetableName(), 'findById timetable_name');
            self::assertSame(1, $found->academicSessionId(), 'findById academic_session_id');
            self::assertSame(1, $found->classId(), 'findById class_id');
            self::assertSame(1, $found->sectionId(), 'findById section_id');
            self::assertSame(1, $found->subjectId(), 'findById subject_id');
            self::assertSame(1, $found->teacherId(), 'findById teacher_id');
            self::assertSame('draft', $found->status(), 'findById status');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function testRepositoryCreateDefaultsStatusToActive(): void
    {
        $repository = new TimetableRepository(self::sqlite());
        $created = $repository->create([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);

        self::assertTrue($created instanceof Timetable, 'create returns entity');
        if ($created instanceof Timetable) {
            self::assertSame('active', $created->status(), 'default status is active (matches schema)');
        }
    }

    private static function testRepositoryFindByNameAndDuplicates(): void
    {
        $repository = new TimetableRepository(self::sqlite());
        $repository->create([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]);
        $repository->create([
            'timetable_name' => 'Afternoon Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 2,
            'teacher_id' => 2,
            'status' => 'draft',
        ]);

        $found = $repository->findByName('Morning Timetable');
        self::assertTrue($found instanceof Timetable, 'findByName finds existing');
        if ($found instanceof Timetable) {
            self::assertSame(1, $found->id(), 'findByName id');
        }

        self::assertSame(null, $repository->findByName('Evening Timetable'), 'findByName missing null');
    }

    private static function testRepositoryPaginateWithSearch(): void
    {
        $repository = new TimetableRepository(self::sqlite());
        $repository->create([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]);
        $repository->create([
            'timetable_name' => 'Afternoon Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 2,
            'teacher_id' => 2,
            'status' => 'active',
        ]);
        $repository->create([
            'timetable_name' => 'Evening Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 3,
            'teacher_id' => 3,
            'status' => 'active',
        ]);

        $all = $repository->paginate(1, 15);
        self::assertSame(3, $all['total'], 'paginate total');
        self::assertSame(3, count($all['items']), 'paginate item count');

        $filtered = $repository->paginate(1, 15, 'Afternoon');
        self::assertSame(1, $filtered['total'], 'search total');
        self::assertSame(1, count($filtered['items']), 'search item count');

        $paged = $repository->paginate(2, 2);
        self::assertSame(3, $paged['total'], 'page 2 total retains full count');
        self::assertSame(1, count($paged['items']), 'page 2 has 1 item');
    }

    private static function testRepositoryUpdatePersistsStatus(): void
    {
        $repository = new TimetableRepository(self::sqlite());
        $created = $repository->create([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]);

        $updated = $repository->update((int) $created->id(), ['status' => 'draft']);
        self::assertTrue($updated instanceof Timetable, 'update returns entity');
        if ($updated instanceof Timetable) {
            self::assertSame('draft', $updated->status(), 'updated status persisted');
            self::assertSame('Morning Timetable', $updated->timetableName(), 'timetable_name retained on update');
            self::assertSame(1, $updated->teacherId(), 'teacher_id retained on update');
        }
    }

    private static function testRepositoryUpdatePreservesFieldsOnPartialUpdate(): void
    {
        $repository = new TimetableRepository(self::sqlite());
        $created = $repository->create([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]);

        $updated = $repository->update((int) $created->id(), ['status' => 'draft']);
        self::assertTrue($updated instanceof Timetable, 'partial update returns entity');
        if ($updated instanceof Timetable) {
            self::assertSame('draft', $updated->status(), 'status updated');
            self::assertSame('Morning Timetable', $updated->timetableName(), 'timetable_name preserved');
            self::assertSame(1, $updated->academicSessionId(), 'academic_session_id preserved');
            self::assertSame(1, $updated->classId(), 'class_id preserved');
            self::assertSame(1, $updated->sectionId(), 'section_id preserved');
            self::assertSame(1, $updated->subjectId(), 'subject_id preserved');
            self::assertSame(1, $updated->teacherId(), 'teacher_id preserved');
        }
    }

    private static function testRepositoryUpdateMissingIdReturnsNull(): void
    {
        $repository = new TimetableRepository(self::sqlite());

        self::assertSame(null, $repository->update(999, ['status' => 'draft']), 'update missing id returns null');
    }

    private static function testRepositoryDeleteNotSupportedOnSqlite(): void
    {
        $repository = new TimetableRepository(self::sqlite());
        $created = $repository->create([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]);

        $result = $repository->delete((int) $created->id());
        self::assertSame(false, $result, 'delete uses NOW() (MySQL-only) so false on SQLite');

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Timetable, 'row remains when delete unsupported on SQLite');
    }

    // -------------------------------------------------------------------------
    // Service
    // -------------------------------------------------------------------------

    private static function testServiceNoDatabaseCreateMapsEntity(): void
    {
        $service = new TimetableService(new TimetableRepository(null), new TimetableValidator());

        $result = $service->create(new CreateTimetableRequest([
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]));

        self::assertTrue($result instanceof Timetable, 'no-db create returns entity');
        if ($result instanceof Timetable) {
            self::assertSame('Morning Timetable', $result->timetableName(), 'no-db service timetable_name');
            self::assertSame('active', $result->status(), 'no-db service default status');
        }
    }

    private static function testServiceCreatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeTimetableRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true, true);

        $result = $service->create(new CreateTimetableRequest([
            'timetable_name' => 'Evening Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]));

        self::assertTrue($result instanceof Timetable, 'create returns entity');
        if ($result instanceof Timetable) {
            self::assertSame('Evening Timetable', $result->timetableName(), 'created timetable_name');
            self::assertSame('active', $result->status(), 'created status');
        }
        self::assertSame('Evening Timetable', $repository->rows[3]['timetable_name'], 'create stored in repository');
        self::assertSame('CREATE', $logger->lastAction, 'create audit logged');
    }

    private static function testServiceCreateRejectsDuplicateName(): void
    {
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true, true);

        try {
            $service->create(new CreateTimetableRequest([
                'timetable_name' => 'Morning Timetable',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'subject_id' => 1,
                'teacher_id' => 1,
            ]));
            self::fail('duplicate timetable name should be rejected');
        } catch (TimetableException $exception) {
            self::assertSame('Timetable already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceCreateRejectsMissingSessionReference(): void
    {
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, false, true, true, true, true);

        try {
            $service->create(new CreateTimetableRequest([
                'timetable_name' => 'Evening Timetable',
                'academic_session_id' => 99,
                'class_id' => 1,
                'section_id' => 1,
                'subject_id' => 1,
                'teacher_id' => 1,
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
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, false, true, true, true);

        try {
            $service->create(new CreateTimetableRequest([
                'timetable_name' => 'Evening Timetable',
                'academic_session_id' => 1,
                'class_id' => 99,
                'section_id' => 1,
                'subject_id' => 1,
                'teacher_id' => 1,
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
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, true, false, true, true);

        try {
            $service->create(new CreateTimetableRequest([
                'timetable_name' => 'Evening Timetable',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 99,
                'subject_id' => 1,
                'teacher_id' => 1,
            ]));
            self::fail('missing section reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['section_id']), 'section_id error present');
            self::assertSame('Section not found.', $errors['section_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsMissingSubjectReference(): void
    {
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, false, true);

        try {
            $service->create(new CreateTimetableRequest([
                'timetable_name' => 'Evening Timetable',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'subject_id' => 99,
                'teacher_id' => 1,
            ]));
            self::fail('missing subject reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['subject_id']), 'subject_id error present');
            self::assertSame('Subject not found.', $errors['subject_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsMissingTeacherReference(): void
    {
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true, false);

        try {
            $service->create(new CreateTimetableRequest([
                'timetable_name' => 'Evening Timetable',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'subject_id' => 1,
                'teacher_id' => 99,
            ]));
            self::fail('missing teacher reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['teacher_id']), 'teacher_id error present');
            self::assertSame('Teacher not found.', $errors['teacher_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsInvalidPayload(): void
    {
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true, true);

        try {
            $service->create(new CreateTimetableRequest(['status' => 'active']));
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['timetable_name']), 'timetable_name error present');
            self::assertTrue(isset($errors['academic_session_id']), 'academic_session_id error present');
            self::assertTrue(isset($errors['teacher_id']), 'teacher_id error present');
        }
    }

    private static function testServiceCreateRejectsInvalidStatus(): void
    {
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true, true);

        try {
            $service->create(new CreateTimetableRequest([
                'timetable_name' => 'Evening Timetable',
                'academic_session_id' => 1,
                'class_id' => 1,
                'section_id' => 1,
                'subject_id' => 1,
                'teacher_id' => 1,
                'status' => 'present',
            ]));
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceUpdatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeTimetableRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true, true);

        $result = $service->update(new UpdateTimetableRequest(1, ['status' => 'draft']));

        self::assertTrue($result instanceof Timetable, 'update returns entity');
        if ($result instanceof Timetable) {
            self::assertSame('draft', $result->status(), 'updated status');
            self::assertSame('Morning Timetable', $result->timetableName(), 'timetable_name retained');
            self::assertSame(1, $result->teacherId(), 'teacher_id retained');
        }
        self::assertSame('draft', $repository->rows[1]['status'], 'update stored in repository');
        self::assertSame('UPDATE', $logger->lastAction, 'update audit logged');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);

        $result = $service->update(new UpdateTimetableRequest(999, ['status' => 'draft']));

        self::assertSame(null, $result, 'missing timetable returns null');
    }

    private static function testServiceUpdateRejectsDuplicateName(): void
    {
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true, true);

        try {
            $service->update(new UpdateTimetableRequest(1, ['timetable_name' => 'Afternoon Timetable']));
            self::fail('renaming a timetable to an existing name should be rejected');
        } catch (TimetableException $exception) {
            self::assertSame('Timetable already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceUpdateRejectsInvalidPayload(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);

        try {
            $service->update(new UpdateTimetableRequest(1, ['status' => 'present']));
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceDeleteReturnsTrueWhenDeleted(): void
    {
        $repository = new FakeTimetableRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true, true);

        $result = $service->delete(1);

        self::assertSame(true, $result, 'delete returns true');
        self::assertSame('DELETE', $logger->lastAction, 'delete audit logged');
    }

    private static function testServiceDeleteReturnsFalseWhenMissing(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);

        $result = $service->delete(999);

        self::assertSame(false, $result, 'missing timetable delete returns false');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $repository = new FakeTimetableRepository();
        $service = self::serviceWithReferences($repository, null, true, true, true, true, true);

        $result = $service->list(new TimetableListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'list total from repository');
        self::assertSame(1, $result['page'], 'list page');
        self::assertSame(10, $result['per_page'], 'list per_page');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);

        $found = $service->find(1);
        self::assertTrue($found instanceof Timetable, 'find returns entity');
        if ($found instanceof Timetable) {
            self::assertSame('Morning Timetable', $found->timetableName(), 'found timetable_name');
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

        (new TimetableServiceProvider())->register($container);

        $service = $container->get(TimetableService::class);
        self::assertTrue($service instanceof TimetableServiceInterface, 'TimetableService bound to interface');

        $repository = $container->get(TimetableRepository::class);
        self::assertTrue($repository instanceof TimetableRepositoryInterface, 'TimetableRepository bound to interface');

        $controller = $container->get(TimetableController::class);
        self::assertTrue($controller instanceof TimetableController, 'TimetableController bound');

        self::assertTrue($container->get('timetable.validator') instanceof TimetableValidator, 'timetable.validator alias');
        self::assertTrue($container->get('timetable.repository') instanceof TimetableRepositoryInterface, 'timetable.repository alias');
        self::assertTrue($container->get('timetable.service') instanceof TimetableServiceInterface, 'timetable.service alias');
        self::assertTrue($container->get('timetable.controller') instanceof TimetableController, 'timetable.controller alias');
    }

    // -------------------------------------------------------------------------
    // Controller
    // -------------------------------------------------------------------------

    private static function testControllerIndexSuccess(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);
        $controller = new TimetableController($service);

        $request = new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null);
        $response = $controller->index($request);

        self::assertSame('success', $response['status'], 'index success status');
        self::assertSame(2, count($response['data']['items']), 'index items count');
        self::assertSame(2, $response['data']['pagination']['total'], 'index total');
    }

    private static function testControllerShowNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);
        $controller = new TimetableController($service);

        $response = $controller->show(999);

        self::assertSame('error', $response['status'], 'show missing error status');
        self::assertSame('Timetable not found.', $response['message'], 'show 404 message');
    }

    private static function testControllerStoreValidationError(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);
        $controller = new TimetableController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'active']);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store validation error status');
        self::assertTrue(isset($response['details']['validation']), 'store validation details present');
    }

    private static function testControllerStoreConflict(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);
        $controller = new TimetableController($service);

        $request = new RequestHelper([], [], [], [], [
            'timetable_name' => 'Morning Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
        ]);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store conflict error status');
        self::assertSame('Timetable already exists.', $response['message'], 'store conflict message');
    }

    private static function testControllerStoreSuccess(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);
        $controller = new TimetableController($service);

        $request = new RequestHelper([], [], [], [], [
            'timetable_name' => 'Evening Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]);
        $response = $controller->store($request);

        self::assertSame('success', $response['status'], 'store success status');
        self::assertSame('Evening Timetable', $response['data']['timetable_name'], 'store success timetable_name');
        self::assertSame('active', $response['data']['status'], 'store success status');
    }

    private static function testControllerUpdateNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);
        $controller = new TimetableController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'draft']);
        $response = $controller->update(999, $request);

        self::assertSame('error', $response['status'], 'update missing error status');
        self::assertSame('Timetable not found.', $response['message'], 'update missing message');
    }

    private static function testControllerDestroyNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true);
        $controller = new TimetableController($service);

        $response = $controller->destroy(999);

        self::assertSame('error', $response['status'], 'destroy missing error status');
        self::assertSame('Timetable not found.', $response['message'], 'destroy missing message');
    }

    // -------------------------------------------------------------------------
    // Router dispatch regression
    // -------------------------------------------------------------------------

    private static function testRouterDispatchRoutes(): void
    {
        $container = new AppContainer();
        $controller = new TimetableController(self::serviceWithReferences(new FakeTimetableRepository(), null, true, true, true, true, true));
        $container->set(TimetableController::class, $controller);
        $router = new Router($container);

        $index = $router->dispatch('GET', '/timetables', new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null));
        self::assertSame('success', $index['status'], 'GET /timetables index status');
        self::assertSame(2, $index['data']['pagination']['total'], 'GET /timetables index total');

        $show = $router->dispatch('GET', '/timetables/1');
        self::assertSame('success', $show['status'], 'GET /timetables/1 show status');
        self::assertSame('Morning Timetable', $show['data']['timetable_name'], 'GET /timetables/1 timetable_name');

        $store = $router->dispatch('POST', '/timetables', new RequestHelper([], [], [], [], [
            'timetable_name' => 'Evening Timetable',
            'academic_session_id' => 1,
            'class_id' => 1,
            'section_id' => 1,
            'subject_id' => 1,
            'teacher_id' => 1,
            'status' => 'active',
        ]));
        self::assertSame('success', $store['status'], 'POST /timetables store status');
        self::assertSame('Evening Timetable', $store['data']['timetable_name'], 'POST /timetables store timetable_name');

        $update = $router->dispatch('PUT', '/timetables/1', new RequestHelper([], [], [], [], ['status' => 'draft']));
        self::assertSame('success', $update['status'], 'PUT /timetables/1 update status');
        self::assertSame('draft', $update['data']['status'], 'PUT /timetables/1 updated status');

        $destroy = $router->dispatch('DELETE', '/timetables/1');
        self::assertSame('success', $destroy['status'], 'DELETE /timetables/1 destroy status');

        $missing = $router->dispatch('GET', '/timetables/999');
        self::assertSame('error', $missing['status'], 'GET /timetables/999 not found status');
        self::assertSame('Timetable not found.', $missing['message'], 'GET /timetables/999 not found message');

        $unknown = $router->dispatch('GET', '/does-not-exist');
        self::assertSame(false, $unknown['success'], 'unknown route error flag');
        self::assertSame(404, $unknown['status'], 'unknown route 404');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function serviceWithReferences(
        TimetableRepositoryInterface $repository,
        ?FakeAuditLogger $logger,
        bool $sessionExists,
        bool $classExists,
        bool $sectionExists,
        bool $subjectExists,
        bool $teacherExists
    ): TimetableService {
        return new TimetableService(
            $repository,
            new TimetableValidator(),
            new FakeAcademicSessionRepository($sessionExists),
            new FakeAcademicClassRepository($classExists),
            new FakeSectionRepository($sectionExists),
            new FakeSubjectRepository($subjectExists),
            new FakeTeacherRepository($teacherExists),
            null,
            $logger
        );
    }

    private static function sqlite(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE timetables (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timetable_name TEXT NOT NULL,
                academic_session_id INTEGER NOT NULL,
                class_id INTEGER NOT NULL,
                section_id INTEGER NULL,
                subject_id INTEGER NULL,
                teacher_id INTEGER NULL,
                status TEXT NOT NULL DEFAULT "active",
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

final class FakeTimetableRepository implements TimetableRepositoryInterface
{
    public array $rows = [
        1 => ['id' => 1, 'timetable_name' => 'Morning Timetable', 'academic_session_id' => 1, 'class_id' => 1, 'section_id' => 1, 'subject_id' => 1, 'teacher_id' => 1, 'status' => 'active', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'timetable_name' => 'Afternoon Timetable', 'academic_session_id' => 1, 'class_id' => 1, 'section_id' => 1, 'subject_id' => 2, 'teacher_id' => 2, 'status' => 'draft', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        $rows = array_values($this->rows);
        if ($search !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => stripos((string) $row['timetable_name'], $search) !== false));
        }
        $items = array_map(fn (array $row): Timetable => $this->map($row), $rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Timetable
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByName(string $name): ?Timetable
    {
        foreach ($this->rows as $row) {
            if ($row['timetable_name'] === $name) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): Timetable
    {
        $nextId = max(array_keys($this->rows) ?: [0]) + 1;
        $this->rows[$nextId] = [
            'id' => $nextId,
            'timetable_name' => $attributes['timetable_name'] ?? null,
            'academic_session_id' => $attributes['academic_session_id'] ?? null,
            'class_id' => $attributes['class_id'] ?? null,
            'section_id' => $attributes['section_id'] ?? null,
            'subject_id' => $attributes['subject_id'] ?? null,
            'teacher_id' => $attributes['teacher_id'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'created_at' => '2026-01-01',
            'updated_at' => '2026-01-01',
        ];

        return $this->map($this->rows[$nextId]);
    }

    public function update(int $id, array $attributes): ?Timetable
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        foreach (['timetable_name', 'academic_session_id', 'class_id', 'section_id', 'subject_id', 'teacher_id', 'status'] as $field) {
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

    private function map(array $row): Timetable
    {
        return new Timetable(
            (int) $row['id'],
            $row['timetable_name'],
            isset($row['academic_session_id']) ? (int) $row['academic_session_id'] : null,
            isset($row['class_id']) ? (int) $row['class_id'] : null,
            isset($row['section_id']) ? (int) $row['section_id'] : null,
            isset($row['subject_id']) ? (int) $row['subject_id'] : null,
            isset($row['teacher_id']) ? (int) $row['teacher_id'] : null,
            $row['status'] ?? 'active',
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

final class FakeSubjectRepository implements SubjectRepositoryInterface
{
    public function __construct(private bool $exists)
    {
    }

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Subject
    {
        return $this->exists ? new Subject($id, 'Mathematics', 'MATH-101', 1, 'active', null, null, null, null) : null;
    }

    public function findByName(string $name): ?Subject
    {
        return $this->exists ? new Subject(1, $name, 'MATH-101', 1, 'active', null, null, null, null) : null;
    }

    public function create(array $attributes): Subject
    {
        return new Subject(1, $attributes['subject_name'] ?? null, null, 1, 'active', null, null, null, null);
    }

    public function update(int $id, array $attributes): ?Subject
    {
        return null;
    }

    public function delete(int $id): bool
    {
        return false;
    }
}

final class FakeTeacherRepository implements TeacherRepositoryInterface
{
    public function __construct(private bool $exists)
    {
    }

    public function paginate(int $page, int $perPage, ?string $name = null, ?string $employeeId = null): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Teacher
    {
        return $this->exists ? new Teacher($id, 'T-001', 'John', 'Doe', null, null, 'Science', 'Teacher', 'active', null, null, null, null, null, null) : null;
    }

    public function findByEmployeeId(string $employeeId): ?Teacher
    {
        return $this->exists ? new Teacher(1, $employeeId, 'John', 'Doe', null, null, 'Science', 'Teacher', 'active', null, null, null, null, null, null) : null;
    }

    public function create(array $attributes): Teacher
    {
        return new Teacher(1, $attributes['employee_id'] ?? null, null, null, null, null, null, null, 'active', null, null, null, null, null, null);
    }

    public function update(int $id, array $attributes): ?Teacher
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
        return $entity instanceof Timetable ? ['id' => $entity->id()] : null;
    }
}

TimetableModuleTest::run();
