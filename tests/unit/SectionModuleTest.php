<?php

declare(strict_types=1);

/**
 * Unit tests for the Section module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/SectionModuleTest.php
 *
 * Covers:
 * - Section entity getters
 * - SectionResponse shape (code exposed as null, status default)
 * - SectionListRequest pagination and search parsing
 * - CreateSectionRequest / UpdateSectionRequest accessors
 * - SectionValidator create/update validation
 * - SectionException type
 * - SectionRepository no-DB behavior
 * - SectionRepository SQLite-backed CRUD + search
 * - SectionService create/update/delete flows (via fake repository)
 * - Reference validation (academic class) — regression for TypeError fix
 * - Duplicate section-name checks
 * - SectionServiceProvider DI bindings and aliases
 * - SectionController request/response handling
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\AcademicClass\AcademicClass;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use App\Http\RequestHelper;
use App\Section\CreateSectionRequest;
use App\Section\Section;
use App\Section\SectionController;
use App\Section\SectionException;
use App\Section\SectionListRequest;
use App\Section\SectionRepository;
use App\Section\SectionRepositoryInterface;
use App\Section\SectionResponse;
use App\Section\SectionService;
use App\Section\SectionServiceInterface;
use App\Section\SectionServiceProvider;
use App\Section\SectionValidator;
use App\Section\UpdateSectionRequest;
use App\Support\AppContainer;

final class SectionModuleTest
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
        self::testValidatorRejectsEmptySectionNameOnUpdate();
        self::testValidatorRejectsInvalidStatusOnUpdate();
        self::testExceptionExtendsRuntimeException();
        self::testRepositoryNoDatabaseBehavior();
        self::testRepositoryCreatePersistsNameAndFindById();
        self::testRepositoryFindByNameAndDuplicates();
        self::testRepositoryPaginateWithSearch();
        self::testRepositoryUpdatePersistsName();
        self::testRepositoryDeleteNotSupportedOnSqlite();
        self::testServiceNoDatabaseCreateMapsEntity();
        self::testServiceCreatePersistsAndReturnsEntity();
        self::testServiceCreateRejectsDuplicateName();
        self::testServiceCreateRejectsMissingClassReference();
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
        $item = new Section(1, 'A', 'S-A', 2, 'active', 30, '2026-01-01', '2026-01-02', null);

        self::assertSame(1, $item->id(), 'id');
        self::assertSame('A', $item->sectionName(), 'section_name');
        self::assertSame('S-A', $item->code(), 'code');
        self::assertSame(2, $item->classId(), 'class_id');
        self::assertSame('active', $item->status(), 'status');
        self::assertSame(30, $item->capacity(), 'capacity');
        self::assertSame('2026-01-01', $item->createdAt(), 'created_at');
        self::assertSame('2026-01-02', $item->updatedAt(), 'updated_at');
        self::assertSame(null, $item->deletedAt(), 'deleted_at default null');
    }

    // -------------------------------------------------------------------------
    // Response DTO
    // -------------------------------------------------------------------------

    private static function testResponseExposesExpectedFields(): void
    {
        $item = new Section(1, 'A', null, 2, 'active', 30, null, null, null);
        $data = SectionResponse::fromEntity($item);

        self::assertSame(1, $data['id'], 'response id');
        self::assertSame('A', $data['section_name'], 'response section_name');
        self::assertSame(null, $data['code'], 'response code is null (not stored)');
        self::assertSame(2, $data['class_id'], 'response class_id');
        self::assertSame('active', $data['status'], 'response status');
        self::assertSame(5, count($data), 'response has exactly 5 fields');
    }

    private static function testResponseCollection(): void
    {
        $items = [
            new Section(1, 'A', null, 2, 'active', 30, null, null, null),
            new Section(2, 'B', null, 2, 'inactive', 25, null, null, null),
        ];

        $collection = SectionResponse::collection($items);

        self::assertSame(2, count($collection), 'collection length');
        self::assertSame('A', $collection[0]['section_name'], 'first section_name');
        self::assertSame('inactive', $collection[1]['status'], 'second status');
    }

    // -------------------------------------------------------------------------
    // ListRequest
    // -------------------------------------------------------------------------

    private static function testListRequestParsesQuery(): void
    {
        $request = new SectionListRequest([
            'page' => 2,
            'per_page' => 50,
            'search' => 'A',
        ]);

        self::assertSame(2, $request->page(), 'page parsed');
        self::assertSame(50, $request->perPage(), 'per_page parsed');
        self::assertSame('A', $request->search(), 'search parsed');

        $default = new SectionListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search null');

        $nameFallback = new SectionListRequest(['name' => 'A']);
        self::assertSame('A', $nameFallback->search(), 'name fallback to search');

        $bounded = new SectionListRequest(['page' => 0, 'per_page' => 500]);
        self::assertSame(1, $bounded->page(), 'page clamped to 1');
        self::assertSame(100, $bounded->perPage(), 'per_page capped at 100');
    }

    // -------------------------------------------------------------------------
    // Create/Update requests
    // -------------------------------------------------------------------------

    private static function testCreateRequestPayload(): void
    {
        $payload = ['section_name' => 'A', 'class_id' => 2];
        $request = new CreateSectionRequest($payload);

        self::assertSame($payload, $request->payload(), 'create payload passthrough');
    }

    private static function testUpdateRequestIdAndPayload(): void
    {
        $payload = ['status' => 'inactive'];
        $request = new UpdateSectionRequest(7, $payload);

        self::assertSame(7, $request->id(), 'update id');
        self::assertSame($payload, $request->payload(), 'update payload passthrough');
    }

    // -------------------------------------------------------------------------
    // Validator
    // -------------------------------------------------------------------------

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new SectionValidator();

        try {
            $validator->validateCreate(['section_name' => 'A', 'class_id' => 2, 'status' => 'active']);
            $validator->validateCreate(['section_name' => 'A', 'class_id' => 2]);
            self::pass('valid create payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid create payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsMissingFields(): void
    {
        $validator = new SectionValidator();

        try {
            $validator->validateCreate([]);
            self::fail('empty payload should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['section_name']), 'section_name error present');
            self::assertTrue(isset($exception->errors()['class_id']), 'class_id error present');
        }

        try {
            $validator->validateCreate(['section_name' => 'A']);
            self::fail('missing class_id should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['class_id']), 'class_id error on partial payload');
        }

        try {
            $validator->validateCreate(['class_id' => 2]);
            self::fail('missing section_name should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['section_name']), 'section_name error on partial payload');
        }
    }

    private static function testValidatorRejectsInvalidStatus(): void
    {
        $validator = new SectionValidator();

        try {
            $validator->validateCreate(['section_name' => 'A', 'class_id' => 2, 'status' => 'archived']);
            self::fail('invalid status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new SectionValidator();

        try {
            $validator->validateUpdate(['section_name' => 'A']);
            $validator->validateUpdate(['status' => 'inactive']);
            $validator->validateUpdate(['section_name' => 'A', 'status' => 'active']);
            $validator->validateUpdate([]);
            self::pass('valid update payloads accepted');
        } catch (ValidationException $exception) {
            self::fail('valid update payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsEmptySectionNameOnUpdate(): void
    {
        $validator = new SectionValidator();

        try {
            $validator->validateUpdate(['section_name' => '   ']);
            self::fail('empty section_name should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['section_name']), 'section_name error present');
        }
    }

    private static function testValidatorRejectsInvalidStatusOnUpdate(): void
    {
        $validator = new SectionValidator();

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
        $exception = new SectionException('Section already exists.');

        self::assertTrue($exception instanceof \RuntimeException, 'SectionException extends RuntimeException');
        self::assertSame('Section already exists.', $exception->getMessage(), 'exception message');
    }

    // -------------------------------------------------------------------------
    // Repository — no-DB
    // -------------------------------------------------------------------------

    private static function testRepositoryNoDatabaseBehavior(): void
    {
        $repository = new SectionRepository(null);

        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repository->paginate(1, 15), 'paginate no-db empty');
        self::assertSame(null, $repository->findById(1), 'findById no-db null');
        self::assertSame(null, $repository->findByName('A'), 'findByName no-db null');
        self::assertSame(false, $repository->delete(1), 'delete no-db false');

        $created = $repository->create(['section_name' => 'A', 'class_id' => 2]);
        self::assertTrue($created instanceof Section, 'create no-db returns entity');
        if ($created instanceof Section) {
            self::assertSame('A', $created->sectionName(), 'no-db created section_name');
            self::assertSame(2, $created->classId(), 'no-db created class_id');
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
        $repository = new SectionRepository(self::sqlite());
        $created = $repository->create([
            'section_name' => 'A',
            'capacity' => 30,
            'class_id' => 2,
            'status' => 'active',
        ]);

        self::assertTrue($created instanceof Section, 'create returns entity');
        self::assertTrue($created->id() !== null, 'create assigns id');
        if ($created instanceof Section) {
            self::assertSame('A', $created->sectionName(), 'persisted section_name');
            self::assertSame(30, $created->capacity(), 'persisted capacity');
            self::assertSame(2, $created->classId(), 'persisted class_id');
            self::assertSame('active', $created->status(), 'persisted status');
            self::assertSame(null, $created->code(), 'code not stored, null');
        }

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Section, 'findById returns entity');
        if ($found instanceof Section) {
            self::assertSame('A', $found->sectionName(), 'findById section_name');
            self::assertSame(2, $found->classId(), 'findById class_id');
            self::assertSame('active', $found->status(), 'findById status default');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function testRepositoryFindByNameAndDuplicates(): void
    {
        $repository = new SectionRepository(self::sqlite());
        $repository->create(['section_name' => 'A', 'class_id' => 2, 'status' => 'active']);
        $repository->create(['section_name' => 'B', 'class_id' => 2, 'status' => 'inactive']);

        $found = $repository->findByName('A');
        self::assertTrue($found instanceof Section, 'findByName finds existing');
        if ($found instanceof Section) {
            self::assertSame(1, $found->id(), 'findByName id');
        }

        self::assertSame(null, $repository->findByName('Z'), 'findByName missing null');
    }

    private static function testRepositoryPaginateWithSearch(): void
    {
        $repository = new SectionRepository(self::sqlite());
        $repository->create(['section_name' => 'A', 'class_id' => 2, 'status' => 'active']);
        $repository->create(['section_name' => 'B', 'class_id' => 2, 'status' => 'active']);
        $repository->create(['section_name' => 'C', 'class_id' => 3, 'status' => 'active']);

        $all = $repository->paginate(1, 15);
        self::assertSame(3, $all['total'], 'paginate total');
        self::assertSame(3, count($all['items']), 'paginate item count');

        $filtered = $repository->paginate(1, 15, 'A');
        self::assertSame(1, $filtered['total'], 'search total');
        self::assertSame(1, count($filtered['items']), 'search item count');

        $paged = $repository->paginate(2, 2);
        self::assertSame(3, $paged['total'], 'page 2 total retains full count');
        self::assertSame(1, count($paged['items']), 'page 2 has 1 item');
    }

    private static function testRepositoryUpdatePersistsName(): void
    {
        $repository = new SectionRepository(self::sqlite());
        $created = $repository->create(['section_name' => 'A', 'class_id' => 2, 'status' => 'active']);

        $updated = $repository->update((int) $created->id(), ['section_name' => 'B']);
        self::assertTrue($updated instanceof Section, 'update returns entity');
        if ($updated instanceof Section) {
            self::assertSame('B', $updated->sectionName(), 'updated section_name persisted');
            self::assertSame(2, $updated->classId(), 'class_id retained on update');
        }

        self::assertSame(null, $repository->update(999, ['status' => 'inactive']), 'update missing id returns null');
    }

    private static function testRepositoryDeleteNotSupportedOnSqlite(): void
    {
        $repository = new SectionRepository(self::sqlite());
        $created = $repository->create(['section_name' => 'A', 'class_id' => 2, 'status' => 'active']);

        $result = $repository->delete((int) $created->id());
        self::assertSame(false, $result, 'delete uses NOW() (MySQL-only) so false on SQLite');

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Section, 'row remains when delete unsupported on SQLite');
    }

    // -------------------------------------------------------------------------
    // Service
    // -------------------------------------------------------------------------

    private static function testServiceNoDatabaseCreateMapsEntity(): void
    {
        $service = new SectionService(new SectionRepository(null), new SectionValidator());

        $result = $service->create(new CreateSectionRequest(['section_name' => 'A', 'class_id' => 2]));

        self::assertTrue($result instanceof Section, 'no-db create returns entity');
        if ($result instanceof Section) {
            self::assertSame('A', $result->sectionName(), 'no-db service section_name');
            self::assertSame('active', $result->status(), 'no-db service default status');
        }
    }

    private static function testServiceCreatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeSectionRepository();
        $logger = new FakeAuditLogger();
        $service = new SectionService($repository, new SectionValidator(), self::fakeClassRepo(true), null, $logger);

        $result = $service->create(new CreateSectionRequest([
            'section_name' => 'C',
            'class_id' => 2,
            'capacity' => 30,
            'status' => 'active',
        ]));

        self::assertTrue($result instanceof Section, 'create returns entity');
        if ($result instanceof Section) {
            self::assertSame('C', $result->sectionName(), 'created section_name');
            self::assertSame(2, $result->classId(), 'created class_id');
            self::assertSame('active', $result->status(), 'created status');
        }
        self::assertSame('C', $repository->rows[3]['section_name'], 'create stored in repository');
        self::assertSame('CREATE', $logger->lastAction, 'create audit logged');
    }

    private static function testServiceCreateRejectsDuplicateName(): void
    {
        $repository = new FakeSectionRepository();
        $service = new SectionService($repository, new SectionValidator(), self::fakeClassRepo(true));

        try {
            $service->create(new CreateSectionRequest([
                'section_name' => 'A',
                'class_id' => 2,
            ]));
            self::fail('duplicate section name should be rejected');
        } catch (SectionException $exception) {
            self::assertSame('Section already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceCreateRejectsMissingClassReference(): void
    {
        $repository = new FakeSectionRepository();
        $service = new SectionService($repository, new SectionValidator(), self::fakeClassRepo(false));

        try {
            $service->create(new CreateSectionRequest([
                'section_name' => 'Z',
                'class_id' => 99,
            ]));
            self::fail('missing academic class reference should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['class_id']), 'class_id error present');
            self::assertSame('Academic class not found.', $errors['class_id'][0], 'reference error message');
        }
    }

    private static function testServiceCreateRejectsInvalidPayload(): void
    {
        $repository = new FakeSectionRepository();
        $service = new SectionService($repository, new SectionValidator(), self::fakeClassRepo(true));

        try {
            $service->create(new CreateSectionRequest(['status' => 'active']));
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['section_name']), 'section_name error present');
            self::assertTrue(isset($errors['class_id']), 'class_id error present');
        }
    }

    private static function testServiceUpdatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeSectionRepository();
        $logger = new FakeAuditLogger();
        $service = new SectionService($repository, new SectionValidator(), self::fakeClassRepo(true), null, $logger);

        $result = $service->update(new UpdateSectionRequest(1, ['status' => 'inactive']));

        self::assertTrue($result instanceof Section, 'update returns entity');
        if ($result instanceof Section) {
            self::assertSame('inactive', $result->status(), 'updated status');
            self::assertSame('A', $result->sectionName(), 'section_name retained');
        }
        self::assertSame('inactive', $repository->rows[1]['status'], 'update stored in repository');
        self::assertSame('UPDATE', $logger->lastAction, 'update audit logged');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));

        $result = $service->update(new UpdateSectionRequest(999, ['status' => 'inactive']));

        self::assertSame(null, $result, 'missing section returns null');
    }

    private static function testServiceUpdateRejectsDuplicateName(): void
    {
        $repository = new FakeSectionRepository();
        $service = new SectionService($repository, new SectionValidator(), self::fakeClassRepo(true));

        try {
            $service->update(new UpdateSectionRequest(1, ['section_name' => 'B']));
            self::fail('renaming to an existing section name should be rejected');
        } catch (SectionException $exception) {
            self::assertSame('Section already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceUpdateRejectsInvalidPayload(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));

        try {
            $service->update(new UpdateSectionRequest(1, ['status' => 'archived']));
            self::fail('invalid status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceDeleteReturnsTrueWhenDeleted(): void
    {
        $repository = new FakeSectionRepository();
        $logger = new FakeAuditLogger();
        $service = new SectionService($repository, new SectionValidator(), self::fakeClassRepo(true), null, $logger);

        $result = $service->delete(1);

        self::assertSame(true, $result, 'delete returns true');
        self::assertSame('DELETE', $logger->lastAction, 'delete audit logged');
    }

    private static function testServiceDeleteReturnsFalseWhenMissing(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));

        $result = $service->delete(999);

        self::assertSame(false, $result, 'missing section delete returns false');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $repository = new FakeSectionRepository();
        $service = new SectionService($repository, new SectionValidator(), self::fakeClassRepo(true));

        $result = $service->list(new SectionListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'list total from repository');
        self::assertSame(1, $result['page'], 'list page');
        self::assertSame(10, $result['per_page'], 'list per_page');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));

        $found = $service->find(1);
        self::assertTrue($found instanceof Section, 'find returns entity');
        if ($found instanceof Section) {
            self::assertSame('A', $found->sectionName(), 'found section_name');
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

        (new SectionServiceProvider())->register($container);

        $service = $container->get(SectionService::class);
        self::assertTrue($service instanceof SectionServiceInterface, 'SectionService bound to interface');

        $repository = $container->get(SectionRepository::class);
        self::assertTrue($repository instanceof SectionRepositoryInterface, 'SectionRepository bound to interface');

        $controller = $container->get(SectionController::class);
        self::assertTrue($controller instanceof SectionController, 'SectionController bound');

        self::assertTrue($container->get('section.validator') instanceof SectionValidator, 'section.validator alias');
        self::assertTrue($container->get('section.repository') instanceof SectionRepositoryInterface, 'section.repository alias');
        self::assertTrue($container->get('section.service') instanceof SectionServiceInterface, 'section.service alias');
        self::assertTrue($container->get('section.controller') instanceof SectionController, 'section.controller alias');
    }

    // -------------------------------------------------------------------------
    // Controller
    // -------------------------------------------------------------------------

    private static function testControllerIndexSuccess(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));
        $controller = new SectionController($service);

        $request = new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null);
        $response = $controller->index($request);

        self::assertSame('success', $response['status'], 'index success status');
        self::assertSame(2, count($response['data']['items']), 'index items count');
        self::assertSame(2, $response['data']['pagination']['total'], 'index total');
    }

    private static function testControllerShowNotFound(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));
        $controller = new SectionController($service);

        $response = $controller->show(999);

        self::assertSame('error', $response['status'], 'show missing error status');
        self::assertSame('Section not found.', $response['message'], 'show 404 message');
    }

    private static function testControllerStoreValidationError(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));
        $controller = new SectionController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'active']);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store validation error status');
        self::assertTrue(isset($response['details']['validation']), 'store validation details present');
    }

    private static function testControllerStoreConflict(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));
        $controller = new SectionController($service);

        $request = new RequestHelper([], [], [], [], ['section_name' => 'A', 'class_id' => 2]);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store conflict error status');
        self::assertSame('Section already exists.', $response['message'], 'store conflict message');
    }

    private static function testControllerStoreSuccess(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));
        $controller = new SectionController($service);

        $request = new RequestHelper([], [], [], [], ['section_name' => 'C', 'class_id' => 2, 'status' => 'active']);
        $response = $controller->store($request);

        self::assertSame('success', $response['status'], 'store success status');
        self::assertSame('C', $response['data']['section_name'], 'store success section_name');
        self::assertSame(2, $response['data']['class_id'], 'store success class_id');
    }

    private static function testControllerUpdateNotFound(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));
        $controller = new SectionController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'inactive']);
        $response = $controller->update(999, $request);

        self::assertSame('error', $response['status'], 'update missing error status');
        self::assertSame('Section not found.', $response['message'], 'update missing message');
    }

    private static function testControllerDestroyNotFound(): void
    {
        $service = new SectionService(new FakeSectionRepository(), new SectionValidator(), self::fakeClassRepo(true));
        $controller = new SectionController($service);

        $response = $controller->destroy(999);

        self::assertSame('error', $response['status'], 'destroy missing error status');
        self::assertSame('Section not found.', $response['message'], 'destroy missing message');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function sqlite(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE sections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                class_id INTEGER NOT NULL,
                name VARCHAR(50) NOT NULL,
                capacity INTEGER NULL,
                status TEXT NOT NULL DEFAULT "active",
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TEXT NULL
            )'
        );

        return $pdo;
    }

    private static function fakeClassRepo(bool $exists): AcademicClassRepositoryInterface
    {
        return new FakeAcademicClassRepository($exists);
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

final class FakeSectionRepository implements SectionRepositoryInterface
{
    public array $rows = [
        1 => ['id' => 1, 'name' => 'A', 'class_id' => 2, 'capacity' => 30, 'status' => 'active', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'name' => 'B', 'class_id' => 2, 'capacity' => 25, 'status' => 'inactive', 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        $rows = array_values($this->rows);
        if ($search !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => stripos((string) $row['name'], $search) !== false));
        }
        $items = array_map(fn (array $row): Section => $this->map($row), $rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Section
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByName(string $name): ?Section
    {
        foreach ($this->rows as $row) {
            if ($row['name'] === $name) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): Section
    {
        $nextId = max(array_keys($this->rows) ?: [0]) + 1;
        $this->rows[$nextId] = [
            'id' => $nextId,
            'name' => $attributes['section_name'] ?? null,
            'class_id' => $attributes['class_id'] ?? null,
            'capacity' => $attributes['capacity'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'created_at' => '2026-01-01',
            'updated_at' => '2026-01-01',
        ];

        return $this->map($this->rows[$nextId]);
    }

    public function update(int $id, array $attributes): ?Section
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        if (array_key_exists('section_name', $attributes)) {
            $this->rows[$id]['name'] = $attributes['section_name'];
        }
        foreach (['capacity', 'class_id', 'status'] as $field) {
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

    private function map(array $row): Section
    {
        return new Section(
            (int) $row['id'],
            $row['name'],
            null,
            isset($row['class_id']) ? (int) $row['class_id'] : null,
            $row['status'] ?? 'active',
            isset($row['capacity']) ? (int) $row['capacity'] : null,
            $row['created_at'],
            $row['updated_at'],
            null
        );
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

final class FakeAuditLogger implements AuditLoggerInterface
{
    public ?string $lastAction = null;

    public function log(string $action, string $entityType, ?int $entityId, ?array $oldValues = null, ?array $newValues = null): void
    {
        $this->lastAction = $action;
    }

    public function entityToArray(?object $entity): ?array
    {
        return $entity instanceof Section ? ['id' => $entity->id()] : null;
    }
}

SectionModuleTest::run();
