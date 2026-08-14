<?php

declare(strict_types=1);

/**
 * Unit tests for the AcademicClass module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/AcademicClassModuleTest.php
 *
 * Covers:
 * - AcademicClass entity getters
 * - AcademicClassResponse shape (code exposed as null, status default)
 * - AcademicClassListRequest pagination and search parsing
 * - CreateAcademicClassRequest / UpdateAcademicClassRequest accessors
 * - AcademicClassValidator create/update validation
 * - AcademicClassException type
 * - AcademicClassRepository no-DB behavior
 * - AcademicClassRepository SQLite-backed CRUD + search
 * - AcademicClassService create/update/delete flows (via fake repository)
 * - Reference validation (academic session) — regression for TypeError fix
 * - Duplicate class-name checks
 * - AcademicClassServiceProvider DI bindings and aliases
 * - AcademicClassController request/response handling
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\AcademicClass\AcademicClass;
use App\AcademicClass\AcademicClassController;
use App\AcademicClass\AcademicClassException;
use App\AcademicClass\AcademicClassListRequest;
use App\AcademicClass\AcademicClassRepository;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\AcademicClass\AcademicClassResponse;
use App\AcademicClass\AcademicClassService;
use App\AcademicClass\AcademicClassServiceInterface;
use App\AcademicClass\AcademicClassServiceProvider;
use App\AcademicClass\AcademicClassValidator;
use App\AcademicClass\CreateAcademicClassRequest;
use App\AcademicClass\UpdateAcademicClassRequest;
use App\AcademicSession\AcademicSession;
use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use App\Http\RequestHelper;
use App\Support\AppContainer;

final class AcademicClassModuleTest
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
        self::testValidatorRejectsMissingFields();
        self::testValidatorRejectsInvalidStatus();
        self::testValidatorAcceptsValidUpdate();
        self::testValidatorRejectsEmptyClassNameOnUpdate();
        self::testValidatorRejectsInvalidStatusOnUpdate();
        self::testExceptionExtendsRuntimeException();
        self::testRepositoryNoDatabaseBehavior();
        self::testRepositoryCreateAndFindById();
        self::testRepositoryFindByNameAndDuplicates();
        self::testRepositoryPaginateWithSearch();
        self::testRepositoryUpdatePersists();
        self::testRepositoryDeleteNotSupportedOnSqlite();
        self::testServiceNoDatabaseCreateMapsEntity();
        self::testServiceCreatePersistsAndReturnsEntity();
        self::testServiceCreateRejectsDuplicateName();
        self::testServiceCreateRejectsMissingSessionReference();
        self::testServiceCreateRejectsInvalidPayload();
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

        echo PHP_EOL;
        echo sprintf("Assertions: %d, Failures: %d%s", self::$assertions, self::$failures, PHP_EOL);
        exit(self::$failures === 0 ? 0 : 1);
    }

    // -------------------------------------------------------------------------
    // Entity
    // -------------------------------------------------------------------------

    private static function testEntityGetters(): void
    {
        $item = new AcademicClass(1, 'Grade 1', 'G1', 2, 'active', 1, '2026-01-01', '2026-01-02', null);

        self::assertSame(1, $item->id(), 'id');
        self::assertSame('Grade 1', $item->className(), 'class_name');
        self::assertSame('G1', $item->code(), 'code');
        self::assertSame(2, $item->academicSessionId(), 'academic_session_id');
        self::assertSame('active', $item->status(), 'status');
        self::assertSame(1, $item->gradeLevel(), 'grade_level');
        self::assertSame('2026-01-01', $item->createdAt(), 'created_at');
        self::assertSame('2026-01-02', $item->updatedAt(), 'updated_at');
        self::assertSame(null, $item->deletedAt(), 'deleted_at default null');
    }

    // -------------------------------------------------------------------------
    // Response DTO
    // -------------------------------------------------------------------------

    private static function testResponseExposesExpectedFields(): void
    {
        $item = new AcademicClass(1, 'Grade 1', null, 2, 'active', 1, null, null, null);
        $data = AcademicClassResponse::fromEntity($item);

        self::assertSame(1, $data['id'], 'response id');
        self::assertSame('Grade 1', $data['class_name'], 'response class_name');
        self::assertSame(null, $data['code'], 'response code is null (not stored)');
        self::assertSame(2, $data['academic_session_id'], 'response academic_session_id');
        self::assertSame('active', $data['status'], 'response status');
        self::assertSame(5, count($data), 'response has exactly 5 fields');
    }

    private static function testResponseCollection(): void
    {
        $items = [
            new AcademicClass(1, 'Grade 1', null, 2, 'active', 1, null, null, null),
            new AcademicClass(2, 'Grade 2', null, 2, 'inactive', 2, null, null, null),
        ];

        $collection = AcademicClassResponse::collection($items);

        self::assertSame(2, count($collection), 'collection length');
        self::assertSame('Grade 1', $collection[0]['class_name'], 'first class_name');
        self::assertSame('inactive', $collection[1]['status'], 'second status');
    }

    // -------------------------------------------------------------------------
    // ListRequest
    // -------------------------------------------------------------------------

    private static function testListRequestParsesQuery(): void
    {
        $request = new AcademicClassListRequest([
            'page' => 2,
            'per_page' => 50,
            'search' => 'Grade',
        ]);

        self::assertSame(2, $request->page(), 'page parsed');
        self::assertSame(50, $request->perPage(), 'per_page parsed');
        self::assertSame('Grade', $request->search(), 'search parsed');

        $default = new AcademicClassListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search null');

        $nameFallback = new AcademicClassListRequest(['name' => 'Grade 1']);
        self::assertSame('Grade 1', $nameFallback->search(), 'name fallback to search');

        $bounded = new AcademicClassListRequest(['page' => 0, 'per_page' => 500]);
        self::assertSame(1, $bounded->page(), 'page clamped to 1');
        self::assertSame(100, $bounded->perPage(), 'per_page capped at 100');
    }

    // -------------------------------------------------------------------------
    // Create/Update requests
    // -------------------------------------------------------------------------

    private static function testCreateRequestPayload(): void
    {
        $payload = ['class_name' => 'Grade 1', 'academic_session_id' => 2];
        $request = new CreateAcademicClassRequest($payload);

        self::assertSame($payload, $request->payload(), 'create payload passthrough');
    }

    private static function testUpdateRequestIdAndPayload(): void
    {
        $payload = ['status' => 'inactive'];
        $request = new UpdateAcademicClassRequest(7, $payload);

        self::assertSame(7, $request->id(), 'update id');
        self::assertSame($payload, $request->payload(), 'update payload passthrough');
    }

    // -------------------------------------------------------------------------
    // Validator
    // -------------------------------------------------------------------------

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new AcademicClassValidator();

        try {
            $validator->validateCreate(['class_name' => 'Grade 1', 'academic_session_id' => 2, 'status' => 'active']);
            $validator->validateCreate(['class_name' => 'Grade 1', 'academic_session_id' => 2]);
            self::pass('valid create payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid create payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsMissingFields(): void
    {
        $validator = new AcademicClassValidator();

        try {
            $validator->validateCreate([]);
            self::fail('empty payload should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['class_name']), 'class_name error present');
            self::assertTrue(isset($exception->errors()['academic_session_id']), 'academic_session_id error present');
        }

        try {
            $validator->validateCreate(['class_name' => 'Grade 1']);
            self::fail('missing academic_session_id should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['academic_session_id']), 'academic_session_id error on partial payload');
        }

        try {
            $validator->validateCreate(['academic_session_id' => 2]);
            self::fail('missing class_name should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['class_name']), 'class_name error on partial payload');
        }
    }

    private static function testValidatorRejectsInvalidStatus(): void
    {
        $validator = new AcademicClassValidator();

        try {
            $validator->validateCreate(['class_name' => 'Grade 1', 'academic_session_id' => 2, 'status' => 'archived']);
            self::fail('invalid status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new AcademicClassValidator();

        try {
            $validator->validateUpdate(['class_name' => 'Grade 1']);
            $validator->validateUpdate(['status' => 'inactive']);
            $validator->validateUpdate(['class_name' => 'Grade 1', 'status' => 'active']);
            $validator->validateUpdate([]);
            self::pass('valid update payloads accepted');
        } catch (ValidationException $exception) {
            self::fail('valid update payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsEmptyClassNameOnUpdate(): void
    {
        $validator = new AcademicClassValidator();

        try {
            $validator->validateUpdate(['class_name' => '   ']);
            self::fail('empty class_name should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['class_name']), 'class_name error present');
        }
    }

    private static function testValidatorRejectsInvalidStatusOnUpdate(): void
    {
        $validator = new AcademicClassValidator();

        try {
            $validator->validateUpdate(['status' => 'archived']);
            self::fail('invalid status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    // -------------------------------------------------------------------------
    // Exception
    // -------------------------------------------------------------------------

    private static function testExceptionExtendsRuntimeException(): void
    {
        $exception = new AcademicClassException('Class already exists.');

        self::assertTrue($exception instanceof \RuntimeException, 'AcademicClassException extends RuntimeException');
        self::assertSame('Class already exists.', $exception->getMessage(), 'exception message');
    }

    // -------------------------------------------------------------------------
    // Repository — no-DB
    // -------------------------------------------------------------------------

    private static function testRepositoryNoDatabaseBehavior(): void
    {
        $repository = new AcademicClassRepository(null);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repository->paginate(1, 15), 'paginate no-db empty');
        self::assertSame(null, $repository->findById(1), 'findById no-db null');
        self::assertSame(null, $repository->findByName('Grade 1'), 'findByName no-db null');
        self::assertSame(false, $repository->delete(1), 'delete no-db false');

        $created = $repository->create(['class_name' => 'Grade 1', 'academic_session_id' => 2]);
        self::assertTrue($created instanceof AcademicClass, 'create no-db returns entity');
        if ($created instanceof AcademicClass) {
            self::assertSame('Grade 1', $created->className(), 'no-db created class_name');
            self::assertSame(2, $created->academicSessionId(), 'no-db created session id');
            self::assertSame('active', $created->status(), 'no-db created default status');
            self::assertSame(null, $created->id(), 'no-db created has no id');
        }

        self::assertSame(null, $repository->update(1, ['status' => 'inactive']), 'update no-db null');
    }

    // -------------------------------------------------------------------------
    // Repository — SQLite-backed
    // -------------------------------------------------------------------------

    private static function testRepositoryCreateAndFindById(): void
    {
        $repository = new AcademicClassRepository(self::sqlite());
        $created = $repository->create([
            'class_name' => 'Grade 1',
            'grade_level' => 1,
            'academic_session_id' => 2,
            'status' => 'active',
        ]);

        self::assertTrue($created instanceof AcademicClass, 'create returns entity');
        self::assertTrue($created->id() !== null, 'create assigns id');
        if ($created instanceof AcademicClass) {
            self::assertSame('Grade 1', $created->className(), 'persisted class_name');
            self::assertSame(1, $created->gradeLevel(), 'persisted grade_level');
            self::assertSame(2, $created->academicSessionId(), 'persisted session id');
            self::assertSame('active', $created->status(), 'persisted status');
            self::assertSame(null, $created->code(), 'code not stored, null');
        }

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof AcademicClass, 'findById returns entity');
        if ($found instanceof AcademicClass) {
            self::assertSame('Grade 1', $found->className(), 'findById class_name');
            self::assertSame(2, $found->academicSessionId(), 'findById session id');
            self::assertSame('active', $found->status(), 'findById status default');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function testRepositoryFindByNameAndDuplicates(): void
    {
        $repository = new AcademicClassRepository(self::sqlite());
        $repository->create(['class_name' => 'Grade 1', 'academic_session_id' => 2, 'status' => 'active']);
        $repository->create(['class_name' => 'Grade 2', 'academic_session_id' => 2, 'status' => 'inactive']);

        $found = $repository->findByName('Grade 1');
        self::assertTrue($found instanceof AcademicClass, 'findByName finds existing');
        if ($found instanceof AcademicClass) {
            self::assertSame(1, $found->id(), 'findByName id');
        }

        self::assertSame(null, $repository->findByName('Grade 99'), 'findByName missing null');
    }

    private static function testRepositoryPaginateWithSearch(): void
    {
        $repository = new AcademicClassRepository(self::sqlite());
        $repository->create(['class_name' => 'Grade 1', 'academic_session_id' => 2, 'status' => 'active']);
        $repository->create(['class_name' => 'Grade 2', 'academic_session_id' => 2, 'status' => 'active']);
        $repository->create(['class_name' => 'Form 3', 'academic_session_id' => 3, 'status' => 'active']);

        $all = $repository->paginate(1, 15);
        self::assertSame(3, $all['total'], 'paginate total');
        self::assertSame(3, count($all['items']), 'paginate item count');

        $filtered = $repository->paginate(1, 15, 'Grade');
        self::assertSame(2, $filtered['total'], 'search total');
        self::assertSame(2, count($filtered['items']), 'search item count');

        $paged = $repository->paginate(2, 2);
        self::assertSame(3, $paged['total'], 'page 2 total retains full count');
        self::assertSame(1, count($paged['items']), 'page 2 has 1 item');
    }

    private static function testRepositoryUpdatePersists(): void
    {
        $repository = new AcademicClassRepository(self::sqlite());
        $created = $repository->create(['class_name' => 'Grade 1', 'academic_session_id' => 2, 'status' => 'active']);

        $updated = $repository->update((int) $created->id(), ['status' => 'inactive']);
        self::assertTrue($updated instanceof AcademicClass, 'update returns entity');
        if ($updated instanceof AcademicClass) {
            self::assertSame('inactive', $updated->status(), 'updated status persisted');
            self::assertSame('Grade 1', $updated->className(), 'class_name retained on update');
        }

        self::assertSame(null, $repository->update(999, ['status' => 'inactive']), 'update missing id returns null');
    }

    private static function testRepositoryDeleteNotSupportedOnSqlite(): void
    {
        $repository = new AcademicClassRepository(self::sqlite());
        $created = $repository->create(['class_name' => 'Grade 1', 'academic_session_id' => 2, 'status' => 'active']);

        $result = $repository->delete((int) $created->id());
        self::assertSame(false, $result, 'delete uses NOW() (MySQL-only) so false on SQLite');

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof AcademicClass, 'row remains when delete unsupported on SQLite');
    }

    // -------------------------------------------------------------------------
    // Service
    // -------------------------------------------------------------------------

    private static function testServiceNoDatabaseCreateMapsEntity(): void
    {
        $service = new AcademicClassService(new AcademicClassRepository(null), new AcademicClassValidator());

        $result = $service->create(new CreateAcademicClassRequest(['class_name' => 'Grade 1', 'academic_session_id' => 2]));

        self::assertTrue($result instanceof AcademicClass, 'no-db create returns entity');
        if ($result instanceof AcademicClass) {
            self::assertSame('Grade 1', $result->className(), 'no-db service class_name');
            self::assertSame('active', $result->status(), 'no-db service default status');
        }
    }

    private static function testServiceCreatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeAcademicClassRepository();
        $logger = new FakeAuditLogger();
        $service = new AcademicClassService($repository, new AcademicClassValidator(), self::fakeSessionRepo(true), null, $logger);

        $result = $service->create(new CreateAcademicClassRequest([
            'class_name' => 'Grade 3',
            'academic_session_id' => 2,
            'status' => 'active',
        ]));

        self::assertTrue($result instanceof AcademicClass, 'create returns entity');
        if ($result instanceof AcademicClass) {
            self::assertSame('Grade 3', $result->className(), 'created class_name');
            self::assertSame(2, $result->academicSessionId(), 'created session id');
            self::assertSame('active', $result->status(), 'created status');
        }
        self::assertSame('Grade 3', $repository->rows[3]['class_name'], 'create stored in repository');
        self::assertSame('CREATE', $logger->lastAction, 'create audit logged');
    }

    private static function testServiceCreateRejectsDuplicateName(): void
    {
        $repository = new FakeAcademicClassRepository();
        $service = new AcademicClassService($repository, new AcademicClassValidator(), self::fakeSessionRepo(true));

        try {
            $service->create(new CreateAcademicClassRequest([
                'class_name' => 'Grade 1',
                'academic_session_id' => 2,
            ]));
            self::fail('duplicate class name should be rejected');
        } catch (AcademicClassException $exception) {
            self::assertSame('Class already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceCreateRejectsMissingSessionReference(): void
    {
        $repository = new FakeAcademicClassRepository();
        $service = new AcademicClassService($repository, new AcademicClassValidator(), self::fakeSessionRepo(false));

        try {
            $service->create(new CreateAcademicClassRequest([
                'class_name' => 'New Class',
                'academic_session_id' => 99,
            ]));
            self::fail('missing academic session reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['academic_session_id']), 'academic_session_id error present');
            self::assertSame('Academic session not found.', $errors['academic_session_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsInvalidPayload(): void
    {
        $repository = new FakeAcademicClassRepository();
        $service = new AcademicClassService($repository, new AcademicClassValidator(), self::fakeSessionRepo(true));

        try {
            $service->create(new CreateAcademicClassRequest(['status' => 'active']));
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['class_name']), 'class_name error present');
            self::assertTrue(isset($errors['academic_session_id']), 'academic_session_id error present');
        }
    }

    private static function testServiceUpdatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeAcademicClassRepository();
        $logger = new FakeAuditLogger();
        $service = new AcademicClassService($repository, new AcademicClassValidator(), self::fakeSessionRepo(true), null, $logger);

        $result = $service->update(new UpdateAcademicClassRequest(1, ['status' => 'inactive']));

        self::assertTrue($result instanceof AcademicClass, 'update returns entity');
        if ($result instanceof AcademicClass) {
            self::assertSame('inactive', $result->status(), 'updated status');
            self::assertSame('Grade 1', $result->className(), 'class_name retained');
        }
        self::assertSame('inactive', $repository->rows[1]['status'], 'update stored in repository');
        self::assertSame('UPDATE', $logger->lastAction, 'update audit logged');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));

        $result = $service->update(new UpdateAcademicClassRequest(999, ['status' => 'inactive']));

        self::assertSame(null, $result, 'missing class returns null');
    }

    private static function testServiceUpdateRejectsDuplicateName(): void
    {
        $repository = new FakeAcademicClassRepository();
        $service = new AcademicClassService($repository, new AcademicClassValidator(), self::fakeSessionRepo(true));

        try {
            $service->update(new UpdateAcademicClassRequest(1, ['class_name' => 'Grade 2']));
            self::fail('renaming to an existing class name should be rejected');
        } catch (AcademicClassException $exception) {
            self::assertSame('Class already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceUpdateRejectsInvalidPayload(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));

        try {
            $service->update(new UpdateAcademicClassRequest(1, ['status' => 'archived']));
            self::fail('invalid status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceDeleteReturnsTrueWhenDeleted(): void
    {
        $repository = new FakeAcademicClassRepository();
        $logger = new FakeAuditLogger();
        $service = new AcademicClassService($repository, new AcademicClassValidator(), self::fakeSessionRepo(true), null, $logger);

        $result = $service->delete(1);

        self::assertSame(true, $result, 'delete returns true');
        self::assertSame('DELETE', $logger->lastAction, 'delete audit logged');
    }

    private static function testServiceDeleteReturnsFalseWhenMissing(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));

        $result = $service->delete(999);

        self::assertSame(false, $result, 'missing class delete returns false');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $repository = new FakeAcademicClassRepository();
        $service = new AcademicClassService($repository, new AcademicClassValidator(), self::fakeSessionRepo(true));

        $result = $service->list(new AcademicClassListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'list total from repository');
        self::assertSame(1, $result['page'], 'list page');
        self::assertSame(10, $result['per_page'], 'list per_page');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));

        $found = $service->find(1);
        self::assertTrue($found instanceof AcademicClass, 'find returns entity');
        if ($found instanceof AcademicClass) {
            self::assertSame('Grade 1', $found->className(), 'found class_name');
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

        (new AcademicClassServiceProvider())->register($container);

        $service = $container->get(AcademicClassService::class);
        self::assertTrue($service instanceof AcademicClassServiceInterface, 'AcademicClassService bound to interface');

        $repository = $container->get(AcademicClassRepository::class);
        self::assertTrue($repository instanceof AcademicClassRepositoryInterface, 'AcademicClassRepository bound to interface');

        $controller = $container->get(AcademicClassController::class);
        self::assertTrue($controller instanceof AcademicClassController, 'AcademicClassController bound');

        self::assertTrue($container->get('academicclass.validator') instanceof AcademicClassValidator, 'academicclass.validator alias');
        self::assertTrue($container->get('academicclass.repository') instanceof AcademicClassRepositoryInterface, 'academicclass.repository alias');
        self::assertTrue($container->get('academicclass.service') instanceof AcademicClassServiceInterface, 'academicclass.service alias');
        self::assertTrue($container->get('academicclass.controller') instanceof AcademicClassController, 'academicclass.controller alias');
    }

    // -------------------------------------------------------------------------
    // Controller
    // -------------------------------------------------------------------------

    private static function testControllerIndexSuccess(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));
        $controller = new AcademicClassController($service);

        $request = new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null);
        $response = $controller->index($request);

        self::assertSame('success', $response['status'], 'index success status');
        self::assertSame(2, count($response['data']['items']), 'index items count');
        self::assertSame(2, $response['data']['pagination']['total'], 'index total');
    }

    private static function testControllerShowNotFound(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));
        $controller = new AcademicClassController($service);

        $response = $controller->show(999);

        self::assertSame('error', $response['status'], 'show missing error status');
        self::assertSame('Class not found.', $response['message'], 'show 404 message');
    }

    private static function testControllerStoreValidationError(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));
        $controller = new AcademicClassController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'active']);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store validation error status');
        self::assertTrue(isset($response['details']['validation']), 'store validation details present');
    }

    private static function testControllerStoreConflict(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));
        $controller = new AcademicClassController($service);

        $request = new RequestHelper([], [], [], [], ['class_name' => 'Grade 1', 'academic_session_id' => 2]);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store conflict error status');
        self::assertSame('Class already exists.', $response['message'], 'store conflict message');
    }

    private static function testControllerStoreSuccess(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));
        $controller = new AcademicClassController($service);

        $request = new RequestHelper([], [], [], [], ['class_name' => 'New Class', 'academic_session_id' => 2, 'status' => 'active']);
        $response = $controller->store($request);

        self::assertSame('success', $response['status'], 'store success status');
        self::assertSame('New Class', $response['data']['class_name'], 'store success class_name');
        self::assertSame(2, $response['data']['academic_session_id'], 'store success session id');
    }

    private static function testControllerUpdateNotFound(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));
        $controller = new AcademicClassController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'inactive']);
        $response = $controller->update(999, $request);

        self::assertSame('error', $response['status'], 'update missing error status');
        self::assertSame('Class not found.', $response['message'], 'update missing message');
    }

    private static function testControllerDestroyNotFound(): void
    {
        $service = new AcademicClassService(new FakeAcademicClassRepository(), new AcademicClassValidator(), self::fakeSessionRepo(true));
        $controller = new AcademicClassController($service);

        $response = $controller->destroy(999);

        self::assertSame('error', $response['status'], 'destroy missing error status');
        self::assertSame('Class not found.', $response['message'], 'destroy missing message');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function sqlite(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE academic_classes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class_name VARCHAR(100) NOT NULL,
                grade_level INTEGER NULL,
                academic_session_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT "active",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TEXT NULL
            )'
        );

        return $pdo;
    }

    private static function fakeSessionRepo(bool $exists): AcademicSessionRepositoryInterface
    {
        return new FakeAcademicSessionRepository($exists);
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

final class FakeAcademicClassRepository implements AcademicClassRepositoryInterface
{
    public array $rows = [
        1 => ['id' => 1, 'class_name' => 'Grade 1', 'grade_level' => 1, 'academic_session_id' => 2, 'status' => 'active', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'class_name' => 'Grade 2', 'grade_level' => 2, 'academic_session_id' => 2, 'status' => 'inactive', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        $rows = array_values($this->rows);
        if ($search !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => stripos((string) $row['class_name'], $search) !== false));
        }
        $items = array_map(fn (array $row): AcademicClass => $this->map($row), $rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?AcademicClass
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByName(string $name): ?AcademicClass
    {
        foreach ($this->rows as $row) {
            if ($row['class_name'] === $name) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): AcademicClass
    {
        $nextId = max(array_keys($this->rows) ?: [0]) + 1;
        $this->rows[$nextId] = [
            'id' => $nextId,
            'class_name' => $attributes['class_name'] ?? null,
            'grade_level' => $attributes['grade_level'] ?? null,
            'academic_session_id' => $attributes['academic_session_id'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'created_at' => '2026-01-01',
            'updated_at' => '2026-01-01',
        ];

        return $this->map($this->rows[$nextId]);
    }

    public function update(int $id, array $attributes): ?AcademicClass
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        foreach (['class_name', 'grade_level', 'academic_session_id', 'status'] as $field) {
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

    private function map(array $row): AcademicClass
    {
        return new AcademicClass(
            (int) $row['id'],
            $row['class_name'],
            null,
            isset($row['academic_session_id']) ? (int) $row['academic_session_id'] : null,
            $row['status'] ?? 'active',
            isset($row['grade_level']) ? (int) $row['grade_level'] : null,
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
        return $this->exists ? new AcademicSession($id, 'Session 2026', 'active') : null;
    }

    public function findByName(string $name): ?AcademicSession
    {
        return $this->exists ? new AcademicSession(1, $name, 'active') : null;
    }

    public function create(array $attributes): AcademicSession
    {
        return new AcademicSession(1, $attributes['session_name'] ?? null, 'active');
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

final class FakeAuditLogger implements AuditLoggerInterface
{
    public ?string $lastAction = null;

    public function log(string $action, string $entityType, ?int $entityId, ?array $oldValues = null, ?array $newValues = null): void
    {
        $this->lastAction = $action;
    }

    public function entityToArray(?object $entity): ?array
    {
        return $entity instanceof AcademicClass ? ['id' => $entity->id()] : null;
    }
}

AcademicClassModuleTest::run();
