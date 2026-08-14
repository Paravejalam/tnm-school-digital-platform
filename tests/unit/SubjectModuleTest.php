<?php

declare(strict_types=1);

/**
 * Unit tests for the Subject module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/SubjectModuleTest.php
 *
 * Covers:
 * - Subject entity getters
 * - SubjectResponse shape (id, subject_name, code, section_id, status)
 * - SubjectListRequest pagination and search parsing
 * - CreateSubjectRequest / UpdateSubjectRequest accessors
 * - SubjectValidator create/update validation
 * - SubjectException type
 * - SubjectRepository no-DB behavior
 * - SubjectRepository SQLite-backed CRUD + search
 * - SubjectService create/update/delete flows (via fake repository)
 * - Duplicate subject-name checks
 * - SubjectServiceProvider DI bindings and aliases
 * - SubjectController request/response handling
 * - Router dispatch regression for /subjects resource routes
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use App\Core\Router;
use App\Http\RequestHelper;
use App\Subject\CreateSubjectRequest;
use App\Subject\Subject;
use App\Subject\SubjectController;
use App\Subject\SubjectException;
use App\Subject\SubjectListRequest;
use App\Subject\SubjectRepository;
use App\Subject\SubjectRepositoryInterface;
use App\Subject\SubjectResponse;
use App\Subject\SubjectService;
use App\Subject\SubjectServiceInterface;
use App\Subject\SubjectServiceProvider;
use App\Subject\SubjectValidator;
use App\Subject\UpdateSubjectRequest;
use App\Support\AppContainer;

final class SubjectModuleTest
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
        self::testValidatorRejectsEmptySubjectNameOnUpdate();
        self::testValidatorRejectsInvalidStatusOnUpdate();
        self::testExceptionExtendsRuntimeException();
        self::testRepositoryNoDatabaseBehavior();
        self::testRepositoryCreatePersistsNameAndFindById();
        self::testRepositoryFindByNameAndDuplicates();
        self::testRepositoryPaginateWithSearch();
        self::testRepositoryUpdatePersistsName();
        self::testRepositoryUpdatePreservesFieldsOnPartialUpdate();
        self::testRepositoryDeleteNotSupportedOnSqlite();
        self::testServiceNoDatabaseCreateMapsEntity();
        self::testServiceCreatePersistsAndReturnsEntity();
        self::testServiceCreateRejectsDuplicateName();
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
        $item = new Subject(1, 'Math', 'MATH', 2, 'active', 'Mathematics', '2026-01-01', '2026-01-02', null);

        self::assertSame(1, $item->id(), 'id');
        self::assertSame('Math', $item->subjectName(), 'subject_name');
        self::assertSame('MATH', $item->code(), 'code');
        self::assertSame(2, $item->sectionId(), 'section_id');
        self::assertSame('active', $item->status(), 'status');
        self::assertSame('Mathematics', $item->description(), 'description');
        self::assertSame('2026-01-01', $item->createdAt(), 'created_at');
        self::assertSame('2026-01-02', $item->updatedAt(), 'updated_at');
        self::assertSame(null, $item->deletedAt(), 'deleted_at default null');
    }

    // -------------------------------------------------------------------------
    // Response DTO
    // -------------------------------------------------------------------------

    private static function testResponseExposesExpectedFields(): void
    {
        $item = new Subject(1, 'Math', 'MATH', 2, 'active', null, null, null, null);
        $data = SubjectResponse::fromEntity($item);

        self::assertSame(1, $data['id'], 'response id');
        self::assertSame('Math', $data['subject_name'], 'response subject_name');
        self::assertSame('MATH', $data['code'], 'response code');
        self::assertSame(2, $data['section_id'], 'response section_id');
        self::assertSame('active', $data['status'], 'response status');
        self::assertSame(5, count($data), 'response has exactly 5 fields');
    }

    private static function testResponseCollection(): void
    {
        $items = [
            new Subject(1, 'Math', 'MATH', 2, 'active', null, null, null, null),
            new Subject(2, 'Science', 'SCI', 2, 'inactive', null, null, null, null),
        ];

        $collection = SubjectResponse::collection($items);

        self::assertSame(2, count($collection), 'collection length');
        self::assertSame('Math', $collection[0]['subject_name'], 'first subject_name');
        self::assertSame('inactive', $collection[1]['status'], 'second status');
    }

    // -------------------------------------------------------------------------
    // ListRequest
    // -------------------------------------------------------------------------

    private static function testListRequestParsesQuery(): void
    {
        $request = new SubjectListRequest([
            'page' => 2,
            'per_page' => 50,
            'search' => 'Math',
        ]);

        self::assertSame(2, $request->page(), 'page parsed');
        self::assertSame(50, $request->perPage(), 'per_page parsed');
        self::assertSame('Math', $request->search(), 'search parsed');

        $default = new SubjectListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search null');

        $nameFallback = new SubjectListRequest(['name' => 'Math']);
        self::assertSame('Math', $nameFallback->search(), 'name fallback to search');

        $bounded = new SubjectListRequest(['page' => 0, 'per_page' => 500]);
        self::assertSame(1, $bounded->page(), 'page clamped to 1');
        self::assertSame(100, $bounded->perPage(), 'per_page capped at 100');
    }

    // -------------------------------------------------------------------------
    // Create/Update requests
    // -------------------------------------------------------------------------

    private static function testCreateRequestPayload(): void
    {
        $payload = ['subject_name' => 'Math', 'section_id' => 2];
        $request = new CreateSubjectRequest($payload);

        self::assertSame($payload, $request->payload(), 'create payload passthrough');
    }

    private static function testUpdateRequestIdAndPayload(): void
    {
        $payload = ['status' => 'inactive'];
        $request = new UpdateSubjectRequest(7, $payload);

        self::assertSame(7, $request->id(), 'update id');
        self::assertSame($payload, $request->payload(), 'update payload passthrough');
    }

    // -------------------------------------------------------------------------
    // Validator
    // -------------------------------------------------------------------------

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new SubjectValidator();

        try {
            $validator->validateCreate(['subject_name' => 'Math', 'section_id' => 2, 'status' => 'active']);
            $validator->validateCreate(['subject_name' => 'Math', 'section_id' => 2]);
            self::pass('valid create payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid create payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsMissingFields(): void
    {
        $validator = new SubjectValidator();

        try {
            $validator->validateCreate([]);
            self::fail('empty payload should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['subject_name']), 'subject_name error present');
            self::assertTrue(isset($exception->errors()['section_id']), 'section_id error present');
        }

        try {
            $validator->validateCreate(['subject_name' => 'Math']);
            self::fail('missing section_id should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['section_id']), 'section_id error on partial payload');
        }

        try {
            $validator->validateCreate(['section_id' => 2]);
            self::fail('missing subject_name should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['subject_name']), 'subject_name error on partial payload');
        }
    }

    private static function testValidatorRejectsInvalidStatus(): void
    {
        $validator = new SubjectValidator();

        try {
            $validator->validateCreate(['subject_name' => 'Math', 'section_id' => 2, 'status' => 'archived']);
            self::fail('invalid status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new SubjectValidator();

        try {
            $validator->validateUpdate(['subject_name' => 'Math']);
            $validator->validateUpdate(['status' => 'inactive']);
            $validator->validateUpdate(['subject_name' => 'Math', 'status' => 'active']);
            $validator->validateUpdate([]);
            self::pass('valid update payloads accepted');
        } catch (ValidationException $exception) {
            self::fail('valid update payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsEmptySubjectNameOnUpdate(): void
    {
        $validator = new SubjectValidator();

        try {
            $validator->validateUpdate(['subject_name' => '   ']);
            self::fail('empty subject_name should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['subject_name']), 'subject_name error present');
        }
    }

    private static function testValidatorRejectsInvalidStatusOnUpdate(): void
    {
        $validator = new SubjectValidator();

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
        $exception = new SubjectException('Subject already exists.');

        self::assertTrue($exception instanceof \RuntimeException, 'SubjectException extends RuntimeException');
        self::assertSame('Subject already exists.', $exception->getMessage(), 'exception message');
    }

    // -------------------------------------------------------------------------
    // Repository — no-DB
    // -------------------------------------------------------------------------

    private static function testRepositoryNoDatabaseBehavior(): void
    {
        $repository = new SubjectRepository(null);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repository->paginate(1, 15), 'paginate no-db empty');
        self::assertSame(null, $repository->findById(1), 'findById no-db null');
        self::assertSame(null, $repository->findByName('Math'), 'findByName no-db null');
        self::assertSame(false, $repository->delete(1), 'delete no-db false');

        $created = $repository->create(['subject_name' => 'Math', 'section_id' => 2]);
        self::assertTrue($created instanceof Subject, 'create no-db returns entity');
        if ($created instanceof Subject) {
            self::assertSame('Math', $created->subjectName(), 'no-db created subject_name');
            self::assertSame('active', $created->status(), 'no-db created default status');
            self::assertSame(null, $created->id(), 'no-db created has no id');
        }

        self::assertSame(null, $repository->update(1, ['status' => 'inactive']), 'update no-db null');
    }

    // -------------------------------------------------------------------------
    // Repository — SQLite-backed
    // -------------------------------------------------------------------------

    private static function testRepositoryCreatePersistsNameAndFindById(): void
    {
        $repository = new SubjectRepository(self::sqlite());
        $created = $repository->create([
            'subject_name' => 'Math',
            'code' => 'MATH',
            'description' => 'Mathematics',
            'status' => 'active',
        ]);

        self::assertTrue($created instanceof Subject, 'create returns entity');
        self::assertTrue($created->id() !== null, 'create assigns id');
        if ($created instanceof Subject) {
            self::assertSame('Math', $created->subjectName(), 'persisted subject_name');
            self::assertSame('MATH', $created->code(), 'persisted code');
            self::assertSame('Mathematics', $created->description(), 'persisted description');
            self::assertSame('active', $created->status(), 'persisted status');
            self::assertSame(null, $created->sectionId(), 'section_id not stored, null');
        }

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Subject, 'findById returns entity');
        if ($found instanceof Subject) {
            self::assertSame('Math', $found->subjectName(), 'findById subject_name');
            self::assertSame('MATH', $found->code(), 'findById code');
            self::assertSame('active', $found->status(), 'findById status default');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function testRepositoryFindByNameAndDuplicates(): void
    {
        $repository = new SubjectRepository(self::sqlite());
        $repository->create(['subject_name' => 'Math', 'code' => 'MATH', 'status' => 'active']);
        $repository->create(['subject_name' => 'Science', 'code' => 'SCI', 'status' => 'inactive']);

        $found = $repository->findByName('Math');
        self::assertTrue($found instanceof Subject, 'findByName finds existing');
        if ($found instanceof Subject) {
            self::assertSame(1, $found->id(), 'findByName id');
        }

        self::assertSame(null, $repository->findByName('History'), 'findByName missing null');
    }

    private static function testRepositoryPaginateWithSearch(): void
    {
        $repository = new SubjectRepository(self::sqlite());
        $repository->create(['subject_name' => 'Math', 'code' => 'MATH', 'status' => 'active']);
        $repository->create(['subject_name' => 'Science', 'code' => 'SCI', 'status' => 'active']);
        $repository->create(['subject_name' => 'History', 'code' => 'HIS', 'status' => 'active']);

        $all = $repository->paginate(1, 15);
        self::assertSame(3, $all['total'], 'paginate total');
        self::assertSame(3, count($all['items']), 'paginate item count');

        $filtered = $repository->paginate(1, 15, 'Math');
        self::assertSame(1, $filtered['total'], 'search total');
        self::assertSame(1, count($filtered['items']), 'search item count');

        $paged = $repository->paginate(2, 2);
        self::assertSame(3, $paged['total'], 'page 2 total retains full count');
        self::assertSame(1, count($paged['items']), 'page 2 has 1 item');
    }

    private static function testRepositoryUpdatePersistsName(): void
    {
        $repository = new SubjectRepository(self::sqlite());
        $created = $repository->create(['subject_name' => 'Math', 'code' => 'MATH', 'description' => 'desc', 'status' => 'active']);

        $updated = $repository->update((int) $created->id(), ['subject_name' => 'Physics']);
        self::assertTrue($updated instanceof Subject, 'update returns entity');
        if ($updated instanceof Subject) {
            self::assertSame('Physics', $updated->subjectName(), 'updated subject_name persisted');
            self::assertSame('MATH', $updated->code(), 'code retained on update');
            self::assertSame('desc', $updated->description(), 'description retained on update');
        }

        self::assertSame(null, $repository->update(999, ['status' => 'inactive']), 'update missing id returns null');
    }

    private static function testRepositoryUpdatePreservesFieldsOnPartialUpdate(): void
    {
        $repository = new SubjectRepository(self::sqlite());
        $created = $repository->create(['subject_name' => 'Math', 'code' => 'MATH', 'description' => 'desc', 'status' => 'active']);

        $updated = $repository->update((int) $created->id(), ['status' => 'inactive']);
        self::assertTrue($updated instanceof Subject, 'partial update returns entity');
        if ($updated instanceof Subject) {
            self::assertSame('Math', $updated->subjectName(), 'subject_name preserved on status update');
            self::assertSame('MATH', $updated->code(), 'code preserved on status update');
            self::assertSame('desc', $updated->description(), 'description preserved on status update');
            self::assertSame('inactive', $updated->status(), 'status updated');
        }
    }

    private static function testRepositoryDeleteNotSupportedOnSqlite(): void
    {
        $repository = new SubjectRepository(self::sqlite());
        $created = $repository->create(['subject_name' => 'Math', 'code' => 'MATH', 'status' => 'active']);

        $result = $repository->delete((int) $created->id());
        self::assertSame(false, $result, 'delete uses NOW() (MySQL-only) so false on SQLite');

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Subject, 'row remains when delete unsupported on SQLite');
    }

    // -------------------------------------------------------------------------
    // Service
    // -------------------------------------------------------------------------

    private static function testServiceNoDatabaseCreateMapsEntity(): void
    {
        $service = new SubjectService(new SubjectRepository(null), new SubjectValidator());

        $result = $service->create(new CreateSubjectRequest(['subject_name' => 'Math', 'section_id' => 2]));

        self::assertTrue($result instanceof Subject, 'no-db create returns entity');
        if ($result instanceof Subject) {
            self::assertSame('Math', $result->subjectName(), 'no-db service subject_name');
            self::assertSame('active', $result->status(), 'no-db service default status');
        }
    }

    private static function testServiceCreatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeSubjectRepository();
        $logger = new FakeAuditLogger();
        $service = new SubjectService($repository, new SubjectValidator(), null, $logger);

        $result = $service->create(new CreateSubjectRequest([
            'subject_name' => 'Physics',
            'code' => 'PHY',
            'section_id' => 2,
            'status' => 'active',
        ]));

        self::assertTrue($result instanceof Subject, 'create returns entity');
        if ($result instanceof Subject) {
            self::assertSame('Physics', $result->subjectName(), 'created subject_name');
            self::assertSame('active', $result->status(), 'created status');
        }
        self::assertSame('Physics', $repository->rows[3]['name'], 'create stored in repository');
        self::assertSame('CREATE', $logger->lastAction, 'create audit logged');
    }

    private static function testServiceCreateRejectsDuplicateName(): void
    {
        $repository = new FakeSubjectRepository();
        $service = new SubjectService($repository, new SubjectValidator());

        try {
            $service->create(new CreateSubjectRequest([
                'subject_name' => 'Math',
                'section_id' => 2,
            ]));
            self::fail('duplicate subject name should be rejected');
        } catch (SubjectException $exception) {
            self::assertSame('Subject already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceCreateRejectsInvalidPayload(): void
    {
        $repository = new FakeSubjectRepository();
        $service = new SubjectService($repository, new SubjectValidator());

        try {
            $service->create(new CreateSubjectRequest(['status' => 'active']));
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['subject_name']), 'subject_name error present');
            self::assertTrue(isset($errors['section_id']), 'section_id error present');
        }
    }

    private static function testServiceUpdatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeSubjectRepository();
        $logger = new FakeAuditLogger();
        $service = new SubjectService($repository, new SubjectValidator(), null, $logger);

        $result = $service->update(new UpdateSubjectRequest(1, ['status' => 'inactive']));

        self::assertTrue($result instanceof Subject, 'update returns entity');
        if ($result instanceof Subject) {
            self::assertSame('inactive', $result->status(), 'updated status');
            self::assertSame('Math', $result->subjectName(), 'subject_name retained');
        }
        self::assertSame('inactive', $repository->rows[1]['status'], 'update stored in repository');
        self::assertSame('UPDATE', $logger->lastAction, 'update audit logged');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());

        $result = $service->update(new UpdateSubjectRequest(999, ['status' => 'inactive']));

        self::assertSame(null, $result, 'missing subject returns null');
    }

    private static function testServiceUpdateRejectsDuplicateName(): void
    {
        $repository = new FakeSubjectRepository();
        $service = new SubjectService($repository, new SubjectValidator());

        try {
            $service->update(new UpdateSubjectRequest(1, ['subject_name' => 'Science']));
            self::fail('renaming to an existing subject name should be rejected');
        } catch (SubjectException $exception) {
            self::assertSame('Subject already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceUpdateRejectsInvalidPayload(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());

        try {
            $service->update(new UpdateSubjectRequest(1, ['status' => 'archived']));
            self::fail('invalid status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceDeleteReturnsTrueWhenDeleted(): void
    {
        $repository = new FakeSubjectRepository();
        $logger = new FakeAuditLogger();
        $service = new SubjectService($repository, new SubjectValidator(), null, $logger);

        $result = $service->delete(1);

        self::assertSame(true, $result, 'delete returns true');
        self::assertSame('DELETE', $logger->lastAction, 'delete audit logged');
    }

    private static function testServiceDeleteReturnsFalseWhenMissing(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());

        $result = $service->delete(999);

        self::assertSame(false, $result, 'missing subject delete returns false');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $repository = new FakeSubjectRepository();
        $service = new SubjectService($repository, new SubjectValidator());

        $result = $service->list(new SubjectListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'list total from repository');
        self::assertSame(1, $result['page'], 'list page');
        self::assertSame(10, $result['per_page'], 'list per_page');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());

        $found = $service->find(1);
        self::assertTrue($found instanceof Subject, 'find returns entity');
        if ($found instanceof Subject) {
            self::assertSame('Math', $found->subjectName(), 'found subject_name');
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

        (new SubjectServiceProvider())->register($container);

        $service = $container->get(SubjectService::class);
        self::assertTrue($service instanceof SubjectServiceInterface, 'SubjectService bound to interface');

        $repository = $container->get(SubjectRepository::class);
        self::assertTrue($repository instanceof SubjectRepositoryInterface, 'SubjectRepository bound to interface');

        $controller = $container->get(SubjectController::class);
        self::assertTrue($controller instanceof SubjectController, 'SubjectController bound');

        self::assertTrue($container->get('subject.validator') instanceof SubjectValidator, 'subject.validator alias');
        self::assertTrue($container->get('subject.repository') instanceof SubjectRepositoryInterface, 'subject.repository alias');
        self::assertTrue($container->get('subject.service') instanceof SubjectServiceInterface, 'subject.service alias');
        self::assertTrue($container->get('subject.controller') instanceof SubjectController, 'subject.controller alias');
    }

    // -------------------------------------------------------------------------
    // Controller
    // -------------------------------------------------------------------------

    private static function testControllerIndexSuccess(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());
        $controller = new SubjectController($service);

        $request = new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null);
        $response = $controller->index($request);

        self::assertSame('success', $response['status'], 'index success status');
        self::assertSame(2, count($response['data']['items']), 'index items count');
        self::assertSame(2, $response['data']['pagination']['total'], 'index total');
    }

    private static function testControllerShowNotFound(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());
        $controller = new SubjectController($service);

        $response = $controller->show(999);

        self::assertSame('error', $response['status'], 'show missing error status');
        self::assertSame('Subject not found.', $response['message'], 'show 404 message');
    }

    private static function testControllerStoreValidationError(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());
        $controller = new SubjectController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'active']);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store validation error status');
        self::assertTrue(isset($response['details']['validation']), 'store validation details present');
    }

    private static function testControllerStoreConflict(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());
        $controller = new SubjectController($service);

        $request = new RequestHelper([], [], [], [], ['subject_name' => 'Math', 'section_id' => 2]);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store conflict error status');
        self::assertSame('Subject already exists.', $response['message'], 'store conflict message');
    }

    private static function testControllerStoreSuccess(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());
        $controller = new SubjectController($service);

        $request = new RequestHelper([], [], [], [], ['subject_name' => 'Physics', 'section_id' => 2, 'status' => 'active']);
        $response = $controller->store($request);

        self::assertSame('success', $response['status'], 'store success status');
        self::assertSame('Physics', $response['data']['subject_name'], 'store success subject_name');
        self::assertSame('active', $response['data']['status'], 'store success status');
    }

    private static function testControllerUpdateNotFound(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());
        $controller = new SubjectController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'inactive']);
        $response = $controller->update(999, $request);

        self::assertSame('error', $response['status'], 'update missing error status');
        self::assertSame('Subject not found.', $response['message'], 'update missing message');
    }

    private static function testControllerDestroyNotFound(): void
    {
        $service = new SubjectService(new FakeSubjectRepository(), new SubjectValidator());
        $controller = new SubjectController($service);

        $response = $controller->destroy(999);

        self::assertSame('error', $response['status'], 'destroy missing error status');
        self::assertSame('Subject not found.', $response['message'], 'destroy missing message');
    }

    // -------------------------------------------------------------------------
    // Router dispatch regression
    // -------------------------------------------------------------------------

    private static function testRouterDispatchRoutes(): void
    {
        $container = new AppContainer();
        $controller = new SubjectController(new SubjectService(new FakeSubjectRepository(), new SubjectValidator()));
        $container->set(SubjectController::class, $controller);
        $router = new Router($container);

        $index = $router->dispatch('GET', '/subjects', new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null));
        self::assertSame('success', $index['status'], 'GET /subjects index status');
        self::assertSame(2, $index['data']['pagination']['total'], 'GET /subjects index total');

        $show = $router->dispatch('GET', '/subjects/1');
        self::assertSame('success', $show['status'], 'GET /subjects/1 show status');
        self::assertSame('Math', $show['data']['subject_name'], 'GET /subjects/1 subject_name');

        $store = $router->dispatch('POST', '/subjects', new RequestHelper([], [], [], [], ['subject_name' => 'Physics', 'section_id' => 2, 'status' => 'active']));
        self::assertSame('success', $store['status'], 'POST /subjects store status');
        self::assertSame('Physics', $store['data']['subject_name'], 'POST /subjects store subject_name');

        $update = $router->dispatch('PUT', '/subjects/1', new RequestHelper([], [], [], [], ['status' => 'inactive']));
        self::assertSame('success', $update['status'], 'PUT /subjects/1 update status');
        self::assertSame('inactive', $update['data']['status'], 'PUT /subjects/1 updated status');

        $destroy = $router->dispatch('DELETE', '/subjects/1');
        self::assertSame('success', $destroy['status'], 'DELETE /subjects/1 destroy status');

        $missing = $router->dispatch('GET', '/subjects/999');
        self::assertSame('error', $missing['status'], 'GET /subjects/999 not found status');
        self::assertSame('Subject not found.', $missing['message'], 'GET /subjects/999 not found message');

        $unknown = $router->dispatch('GET', '/does-not-exist');
        self::assertSame(false, $unknown['success'], 'unknown route error flag');
        self::assertSame(404, $unknown['status'], 'unknown route 404');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function sqlite(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE subjects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(150) NOT NULL,
                code VARCHAR(50) NULL,
                description TEXT NULL,
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

final class FakeSubjectRepository implements SubjectRepositoryInterface
{
    public array $rows = [
        1 => ['id' => 1, 'name' => 'Math', 'code' => 'MATH', 'description' => 'Mathematics', 'status' => 'active', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'name' => 'Science', 'code' => 'SCI', 'description' => null, 'status' => 'inactive', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        $rows = array_values($this->rows);
        if ($search !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => stripos((string) $row['name'], $search) !== false));
        }
        $items = array_map(fn (array $row): Subject => $this->map($row), $rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Subject
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByName(string $name): ?Subject
    {
        foreach ($this->rows as $row) {
            if ($row['name'] === $name) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): Subject
    {
        $nextId = max(array_keys($this->rows) ?: [0]) + 1;
        $this->rows[$nextId] = [
            'id' => $nextId,
            'name' => $attributes['subject_name'] ?? $attributes['name'] ?? null,
            'code' => $attributes['code'] ?? null,
            'description' => $attributes['description'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'created_at' => '2026-01-01',
            'updated_at' => '2026-01-01',
        ];

        return $this->map($this->rows[$nextId]);
    }

    public function update(int $id, array $attributes): ?Subject
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        if (array_key_exists('subject_name', $attributes)) {
            $this->rows[$id]['name'] = $attributes['subject_name'];
        }
        foreach (['code', 'description', 'status'] as $field) {
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

    private function map(array $row): Subject
    {
        return new Subject(
            (int) $row['id'],
            $row['name'],
            $row['code'] ?? null,
            null,
            $row['status'] ?? 'active',
            $row['description'] ?? null,
            $row['created_at'],
            $row['updated_at'],
            null
        );
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
        return $entity instanceof Subject ? ['id' => $entity->id()] : null;
    }
}

SubjectModuleTest::run();
