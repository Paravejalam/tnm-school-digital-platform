<?php

declare(strict_types=1);

/**
 * Unit tests for the AcademicSession module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/AcademicSessionModuleTest.php
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\AcademicSession\AcademicSession;
use App\AcademicSession\AcademicSessionController;
use App\AcademicSession\AcademicSessionException;
use App\AcademicSession\AcademicSessionListRequest;
use App\AcademicSession\AcademicSessionRepository;
use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\AcademicSession\AcademicSessionResponse;
use App\AcademicSession\AcademicSessionService;
use App\AcademicSession\AcademicSessionServiceInterface;
use App\AcademicSession\AcademicSessionValidator;
use App\AcademicSession\CreateAcademicSessionRequest;
use App\AcademicSession\UpdateAcademicSessionRequest;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use App\Core\Kernel;
use App\Core\Router;
use App\Http\RequestHelper;

final class AcademicSessionModuleTest
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
        self::testValidatorRejectsMissingRequiredFields();
        self::testValidatorRejectsInvalidStatus();
        self::testValidatorAcceptsValidUpdate();
        self::testValidatorRejectsEmptySessionNameOnUpdate();
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

    private static function testEntityGetters(): void
    {
        $item = new AcademicSession(1, '2026-2027', 'active', '2026-01-01', '2027-03-31', 1, '2026-01-01', '2026-01-02', null);

        self::assertSame(1, $item->id(), 'id');
        self::assertSame('2026-2027', $item->sessionName(), 'session_name');
        self::assertSame('active', $item->status(), 'status');
        self::assertSame('2026-01-01', $item->startDate(), 'start_date');
        self::assertSame('2027-03-31', $item->endDate(), 'end_date');
        self::assertSame(1, $item->isCurrent(), 'is_current');
        self::assertSame('2026-01-01', $item->createdAt(), 'created_at');
        self::assertSame('2026-01-02', $item->updatedAt(), 'updated_at');
        self::assertSame(null, $item->deletedAt(), 'deleted_at default null');
    }

    private static function testResponseExposesExpectedFields(): void
    {
        $item = new AcademicSession(1, '2026-2027', 'active', '2026-01-01', '2027-03-31', 1, null, null, null);
        $data = AcademicSessionResponse::fromEntity($item);

        self::assertSame(1, $data['id'], 'response id');
        self::assertSame('2026-2027', $data['session_name'], 'response session_name');
        self::assertSame('active', $data['status'], 'response status');
        self::assertSame(3, count($data), 'response has exactly 3 fields');
    }

    private static function testResponseCollection(): void
    {
        $items = [
            new AcademicSession(1, '2026-2027', 'active', '2026-01-01', '2027-03-31', 1, null, null, null),
            new AcademicSession(2, '2027-2028', 'inactive', '2027-04-01', '2028-03-31', 0, null, null, null),
        ];

        $collection = AcademicSessionResponse::collection($items);

        self::assertSame(2, count($collection), 'collection length');
        self::assertSame('2026-2027', $collection[0]['session_name'], 'first session_name');
        self::assertSame('inactive', $collection[1]['status'], 'second status');
    }

    private static function testListRequestParsesQuery(): void
    {
        $request = new AcademicSessionListRequest([
            'page' => 2,
            'per_page' => 50,
            'search' => '2026',
        ]);

        self::assertSame(2, $request->page(), 'page parsed');
        self::assertSame(50, $request->perPage(), 'per_page parsed');
        self::assertSame('2026', $request->search(), 'search parsed');

        $default = new AcademicSessionListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->search(), 'default search null');

        $nameFallback = new AcademicSessionListRequest(['name' => '2027-2028']);
        self::assertSame('2027-2028', $nameFallback->search(), 'name fallback to search');

        $bounded = new AcademicSessionListRequest(['page' => 0, 'per_page' => 500]);
        self::assertSame(1, $bounded->page(), 'page clamped to 1');
        self::assertSame(100, $bounded->perPage(), 'per_page capped at 100');
    }

    private static function testCreateRequestPayload(): void
    {
        $payload = [
            'session_name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-03-31',
        ];
        $request = new CreateAcademicSessionRequest($payload);

        self::assertSame($payload, $request->payload(), 'create payload passthrough');
    }

    private static function testUpdateRequestIdAndPayload(): void
    {
        $payload = ['status' => 'archived'];
        $request = new UpdateAcademicSessionRequest(7, $payload);

        self::assertSame(7, $request->id(), 'update id');
        self::assertSame($payload, $request->payload(), 'update payload passthrough');
    }

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new AcademicSessionValidator();

        try {
            $validator->validateCreate([
                'session_name' => '2026-2027',
                'start_date' => '2026-01-01',
                'end_date' => '2027-03-31',
                'status' => 'active',
            ]);
            $validator->validateCreate([
                'session_name' => '2027-2028',
                'start_date' => '2027-04-01',
                'end_date' => '2028-03-31',
                'status' => 'archived',
            ]);
            self::pass('valid create payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid create payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsMissingRequiredFields(): void
    {
        $validator = new AcademicSessionValidator();

        try {
            $validator->validateCreate([]);
            self::fail('empty payload should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['session_name']), 'session_name required');
            self::assertTrue(isset($errors['start_date']), 'start_date required');
            self::assertTrue(isset($errors['end_date']), 'end_date required');
        }

        try {
            $validator->validateCreate(['session_name' => '2026-2027', 'start_date' => '2026-01-01']);
            self::fail('missing end_date should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['end_date']), 'end_date missing error');
        }
    }

    private static function testValidatorRejectsInvalidStatus(): void
    {
        $validator = new AcademicSessionValidator();

        try {
            $validator->validateCreate([
                'session_name' => '2026-2027',
                'start_date' => '2026-01-01',
                'end_date' => '2027-03-31',
                'status' => 'draft',
            ]);
            self::fail('invalid status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new AcademicSessionValidator();

        try {
            $validator->validateUpdate(['status' => 'inactive']);
            $validator->validateUpdate(['status' => 'archived']);
            $validator->validateUpdate(['session_name' => '2026-2027', 'start_date' => '2026-01-01', 'end_date' => '2027-03-31']);
            self::pass('valid update payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid update payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsEmptySessionNameOnUpdate(): void
    {
        $validator = new AcademicSessionValidator();

        try {
            $validator->validateUpdate(['session_name' => '   ']);
            self::fail('empty session_name should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['session_name']), 'session_name error on update');
        }
    }

    private static function testValidatorRejectsInvalidStatusOnUpdate(): void
    {
        $validator = new AcademicSessionValidator();

        try {
            $validator->validateUpdate(['status' => 'deleted']);
            self::fail('invalid status on update should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error on update');
        }
    }

    private static function testExceptionExtendsRuntimeException(): void
    {
        $exception = new AcademicSessionException('Academic session already exists.');
        self::assertSame('Academic session already exists.', $exception->getMessage(), 'exception message');
        self::assertTrue($exception instanceof \RuntimeException, 'extends RuntimeException');
    }

    private static function testRepositoryNoDatabaseBehavior(): void
    {
        $repo = new AcademicSessionRepository(null);

        self::assertTrue($repo instanceof AcademicSessionRepositoryInterface, 'repo implements interface');
        self::assertSame(['items' => [], 'total' => 0, 'page' => 1, 'per_page' => 15], $repo->paginate(1, 15), 'paginate no-db empty');
        self::assertSame(null, $repo->findById(1), 'findById no-db null');
        self::assertSame(null, $repo->findByName('2026-2027'), 'findByName no-db null');
        self::assertSame(false, $repo->delete(1), 'delete no-db false');

        $created = $repo->create([
            'session_name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
            'is_current' => 1,
        ]);

        self::assertTrue($created instanceof AcademicSession, 'create no-db returns entity');
        if ($created instanceof AcademicSession) {
            self::assertSame('2026-2027', $created->sessionName(), 'no-db created session_name');
            self::assertSame('2026-01-01', $created->startDate(), 'no-db created start_date');
            self::assertSame('2027-03-31', $created->endDate(), 'no-db created end_date');
            self::assertSame('active', $created->status(), 'no-db created default status');
            self::assertSame(1, $created->isCurrent(), 'no-db created is_current');
        }

        self::assertSame(null, $repo->update(1, ['status' => 'inactive']), 'update no-db null');
    }

    private static function testRepositoryCreateAndFindById(): void
    {
        $repo = new AcademicSessionRepository(self::sqlite());

        $created = $repo->create([
            'session_name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
            'is_current' => 1,
        ]);

        self::assertTrue($created instanceof AcademicSession, 'create returns entity');
        if ($created instanceof AcademicSession) {
            self::assertTrue((int) $created->id() > 0, 'create assigns id');
            self::assertSame('2026-2027', $created->sessionName(), 'persisted session_name');
            self::assertSame('2026-01-01', $created->startDate(), 'persisted start_date');
            self::assertSame('2027-03-31', $created->endDate(), 'persisted end_date');
            self::assertSame('active', $created->status(), 'persisted status');
            self::assertSame(1, $created->isCurrent(), 'persisted is_current');
        }

        $found = $repo->findById((int) $created->id());
        self::assertTrue($found instanceof AcademicSession, 'findById returns entity');
        if ($found instanceof AcademicSession) {
            self::assertSame('2026-2027', $found->sessionName(), 'findById session_name');
            self::assertSame('active', $found->status(), 'findById status');
            self::assertSame(1, $found->isCurrent(), 'findById is_current');
        }
    }

    private static function testRepositoryFindByNameAndDuplicates(): void
    {
        $repo = new AcademicSessionRepository(self::sqlite());
        $repo->create([
            'session_name' => '2027-2028',
            'start_date' => '2027-04-01',
            'end_date' => '2028-03-31',
            'status' => 'inactive',
            'is_current' => 0,
        ]);

        $found = $repo->findByName('2027-2028');
        self::assertTrue($found instanceof AcademicSession, 'findByName finds existing');
        if ($found instanceof AcademicSession) {
            self::assertSame('2027-2028', $found->sessionName(), 'findByName session_name');
            self::assertSame('inactive', $found->status(), 'findByName status');
        }

        $missing = $repo->findByName('missing session');
        self::assertSame(null, $missing, 'findByName missing null');
    }

    private static function testRepositoryPaginateWithSearch(): void
    {
        $repo = new AcademicSessionRepository(self::sqlite());
        $repo->create(['session_name' => '2026-2027', 'start_date' => '2026-01-01', 'end_date' => '2027-03-31', 'status' => 'active', 'is_current' => 1]);
        $repo->create(['session_name' => '2027-2028', 'start_date' => '2027-04-01', 'end_date' => '2028-03-31', 'status' => 'inactive', 'is_current' => 0]);

        $all = $repo->paginate(1, 15);
        self::assertSame(2, $all['total'], 'paginate total');
        self::assertSame(2, count($all['items']), 'paginate item count');

        $matching = $repo->paginate(1, 15, '2027');
        self::assertSame(2, $matching['total'], 'paginate search total includes both sessions matching 2027');
        self::assertSame('2027-2028', $matching['items'][0]->sessionName(), 'paginate search item');
    }

    private static function testRepositoryUpdatePersists(): void
    {
        $repo = new AcademicSessionRepository(self::sqlite());
        $created = $repo->create([
            'session_name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-03-31',
            'status' => 'active',
            'is_current' => 1,
        ]);

        $updated = $repo->update((int) $created->id(), ['status' => 'archived', 'is_current' => 0]);
        self::assertTrue($updated instanceof AcademicSession, 'update returns entity');
        if ($updated instanceof AcademicSession) {
            self::assertSame('archived', $updated->status(), 'updated status');
            self::assertSame(0, $updated->isCurrent(), 'updated is_current');
        }

        $found = $repo->findById((int) $created->id());
        self::assertTrue($found instanceof AcademicSession, 'findById after update');
        if ($found instanceof AcademicSession) {
            self::assertSame('archived', $found->status(), 'persisted status after update');
        }
    }

    private static function testRepositoryDeleteNotSupportedOnSqlite(): void
    {
        $repo = new AcademicSessionRepository(self::sqlite());
        $created = $repo->create([
            'session_name' => '2028-2029',
            'start_date' => '2028-04-01',
            'end_date' => '2029-03-31',
            'status' => 'active',
            'is_current' => 0,
        ]);

        $result = $repo->delete((int) $created->id());
        self::assertSame(false, $result, 'delete uses NOW() (MySQL-only) so false on SQLite');

        $found = $repo->findById((int) $created->id());
        self::assertTrue($found instanceof AcademicSession, 'row remains when delete unsupported on SQLite');
    }

    private static function testServiceNoDatabaseCreateMapsEntity(): void
    {
        $service = new AcademicSessionService(new AcademicSessionRepository(null), new AcademicSessionValidator());

        $result = $service->create(new CreateAcademicSessionRequest([
            'session_name' => '2026-2027',
            'start_date' => '2026-01-01',
            'end_date' => '2027-03-31',
        ]));

        self::assertTrue($result instanceof AcademicSession, 'no-db create returns entity');
        if ($result instanceof AcademicSession) {
            self::assertSame('2026-2027', $result->sessionName(), 'no-db created session_name');
            self::assertSame('active', $result->status(), 'no-db service default status');
            self::assertSame('2026-01-01', $result->startDate(), 'no-db created start_date');
        }
    }

    private static function testServiceCreatePersistsAndReturnsEntity(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $logger = new FakeAuditLogger();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator(), null, $logger);

        $result = $service->create(new CreateAcademicSessionRequest([
            'session_name' => '2028-2029',
            'start_date' => '2028-04-01',
            'end_date' => '2029-03-31',
            'status' => 'active',
            'is_current' => 1,
        ]));

        self::assertTrue($result instanceof AcademicSession, 'create returns entity');
        if ($result instanceof AcademicSession) {
            self::assertSame('2028-2029', $result->sessionName(), 'created session_name');
            self::assertSame('2028-04-01', $result->startDate(), 'created start_date');
            self::assertSame('2029-03-31', $result->endDate(), 'created end_date');
            self::assertSame('active', $result->status(), 'created status');
        }
        self::assertSame('2028-2029', $repo->rows[3]['session_name'], 'create stored in repository');
        self::assertSame('CREATE', $logger->lastAction, 'create audit logged');
    }

    private static function testServiceCreateRejectsDuplicateName(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator());

        try {
            $service->create(new CreateAcademicSessionRequest([
                'session_name' => '2026-2027',
                'start_date' => '2026-01-01',
                'end_date' => '2027-03-31',
            ]));
            self::fail('duplicate academic session name should be rejected');
        } catch (AcademicSessionException $exception) {
            self::assertSame('Academic session already exists.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceCreateRejectsInvalidPayload(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator());

        try {
            $service->create(new CreateAcademicSessionRequest(['status' => 'active']));
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['session_name']), 'session_name error present');
            self::assertTrue(isset($errors['start_date']), 'start_date error present');
            self::assertTrue(isset($errors['end_date']), 'end_date error present');
        }
    }

    private static function testServiceUpdatePersistsAndReturnsEntity(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $logger = new FakeAuditLogger();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator(), null, $logger);

        $result = $service->update(new UpdateAcademicSessionRequest(1, ['status' => 'archived']));

        self::assertTrue($result instanceof AcademicSession, 'update returns entity');
        if ($result instanceof AcademicSession) {
            self::assertSame('archived', $result->status(), 'updated status');
            self::assertSame('2026-2027', $result->sessionName(), 'session_name retained');
        }
        self::assertSame('archived', $repo->rows[1]['status'], 'update stored in repository');
        self::assertSame('UPDATE', $logger->lastAction, 'update audit logged');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = new AcademicSessionService(new FakeAcademicSessionRepository(), new AcademicSessionValidator());
        self::assertSame(null, $service->update(new UpdateAcademicSessionRequest(999, ['status' => 'inactive'])), 'update missing returns null');
    }

    private static function testServiceUpdateRejectsDuplicateName(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator());

        try {
            $service->update(new UpdateAcademicSessionRequest(1, ['session_name' => '2027-2028']));
            self::fail('duplicate academic session name on update should be rejected');
        } catch (AcademicSessionException $exception) {
            self::assertSame('Academic session already exists.', $exception->getMessage(), 'duplicate update message');
        }
    }

    private static function testServiceUpdateRejectsInvalidPayload(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator());

        try {
            $service->update(new UpdateAcademicSessionRequest(1, ['session_name' => '   ']));
            self::fail('empty session_name update should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['session_name']), 'update session_name error present');
        }
    }

    private static function testServiceDeleteReturnsTrueWhenDeleted(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator());

        self::assertSame(true, $service->delete(1), 'delete returns true when row exists');
    }

    private static function testServiceDeleteReturnsFalseWhenMissing(): void
    {
        $service = new AcademicSessionService(new FakeAcademicSessionRepository(), new AcademicSessionValidator());
        self::assertSame(false, $service->delete(999), 'delete returns false when missing');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator());

        $result = $service->list(new AcademicSessionListRequest(['search' => '2026']));
        self::assertSame(1, $result['total'], 'list total delegated');
        self::assertSame(1, count($result['items']), 'list item count delegated');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator());

        $item = $service->find(1);
        self::assertTrue($item instanceof AcademicSession, 'find delegates to repository');
        if ($item instanceof AcademicSession) {
            self::assertSame('2026-2027', $item->sessionName(), 'found session_name');
        }
    }

    private static function testServiceProviderRegistersBindings(): void
    {
        $kernel = new Kernel(__DIR__ . '/../../backend');
        $kernel->bootstrap();
        $container = $kernel->getContainer();

        $validator = $container->get(AcademicSessionValidator::class);
        $repo = $container->get(AcademicSessionRepositoryInterface::class);
        $service = $container->get(AcademicSessionServiceInterface::class);
        $controller = $container->get(AcademicSessionController::class);

        self::assertTrue($validator instanceof AcademicSessionValidator, 'validator bound');
        self::assertTrue($repo instanceof AcademicSessionRepository, 'repository bound');
        self::assertTrue($service instanceof AcademicSessionService, 'service bound');
        self::assertTrue($controller instanceof AcademicSessionController, 'controller bound');
        self::assertTrue($container->get('academicsession.validator') instanceof AcademicSessionValidator, 'alias validator bound');
        self::assertTrue($container->get('academicsession.repository') instanceof AcademicSessionRepository, 'alias repository bound');
        self::assertTrue($container->get('academicsession.service') instanceof AcademicSessionService, 'alias service bound');
        self::assertTrue($container->get('academicsession.controller') instanceof AcademicSessionController, 'alias controller bound');
    }

    private static function testControllerIndexSuccess(): void
    {
        $repo = new FakeAcademicSessionRepository();
        $service = new AcademicSessionService($repo, new AcademicSessionValidator());
        $controller = new AcademicSessionController($service);

        $result = $controller->index(new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null));
        self::assertSame('success', $result['status'], 'GET /academic-sessions index status');
        self::assertSame(2, $result['data']['pagination']['total'], 'GET /academic-sessions index total');
    }

    private static function testControllerShowNotFound(): void
    {
        $controller = new AcademicSessionController(new AcademicSessionService(new FakeAcademicSessionRepository(), new AcademicSessionValidator()));
        $result = $controller->show(999);
        self::assertSame('error', $result['status'], 'GET /academic-sessions/999 not found status');
        self::assertSame('Academic session not found.', $result['message'], 'GET /academic-sessions/999 not found message');
    }

    private static function testControllerStoreValidationError(): void
    {
        $controller = new AcademicSessionController(new AcademicSessionService(new FakeAcademicSessionRepository(), new AcademicSessionValidator()));
        $result = $controller->store(new RequestHelper([], [], [], [], ['status' => 'active']));
        self::assertSame('error', $result['status'], 'POST validation error status');
        self::assertTrue(isset($result['details']['validation']), 'POST validation details present');
    }

    private static function testControllerStoreConflict(): void
    {
        $controller = new AcademicSessionController(new AcademicSessionService(new FakeAcademicSessionRepository(), new AcademicSessionValidator()));
        $result = $controller->store(new RequestHelper([], [], [], [], ['session_name' => '2026-2027', 'start_date' => '2026-01-01', 'end_date' => '2027-03-31', 'status' => 'active']));
        self::assertSame('error', $result['status'], 'POST conflict error status');
        self::assertSame('Academic session already exists.', $result['message'], 'POST conflict message');
    }

    private static function testControllerStoreSuccess(): void
    {
        $controller = new AcademicSessionController(new AcademicSessionService(new FakeAcademicSessionRepository(), new AcademicSessionValidator()));
        $result = $controller->store(new RequestHelper([], [], [], [], ['session_name' => '2029-2030', 'start_date' => '2029-04-01', 'end_date' => '2030-03-31', 'status' => 'active']));
        self::assertSame('success', $result['status'], 'POST success status');
        self::assertSame('2029-2030', $result['data']['session_name'], 'POST success session_name');
    }

    private static function testControllerUpdateNotFound(): void
    {
        $controller = new AcademicSessionController(new AcademicSessionService(new FakeAcademicSessionRepository(), new AcademicSessionValidator()));
        $result = $controller->update(999, new RequestHelper([], [], [], [], ['status' => 'inactive']));
        self::assertSame('error', $result['status'], 'PUT missing error status');
        self::assertSame('Academic session not found.', $result['message'], 'PUT missing message');
    }

    private static function testControllerDestroyNotFound(): void
    {
        $controller = new AcademicSessionController(new AcademicSessionService(new FakeAcademicSessionRepository(), new AcademicSessionValidator()));
        $result = $controller->destroy(999);
        self::assertSame('error', $result['status'], 'DELETE missing error status');
        self::assertSame('Academic session not found.', $result['message'], 'DELETE missing message');
    }

    private static function testRouterDispatchRoutes(): void
    {
        $kernel = new Kernel(__DIR__ . '/../../backend');
        $kernel->bootstrap();
        $router = $kernel->getContainer()->get('router');

        if ($router instanceof Router) {
            $health = $router->dispatch('GET', '/health');
            self::assertTrue(isset($health['success']) && $health['success'] === true, 'GET /health still works');

            $sessions = $router->dispatch('GET', '/academic-sessions');
            self::assertTrue(($sessions['status'] ?? 0) !== 404, 'GET /academic-sessions resolves');

            $users = $router->dispatch('GET', '/users');
            self::assertTrue(($users['status'] ?? 0) !== 404, 'Regression: GET /users still resolves');

            $students = $router->dispatch('GET', '/students');
            self::assertTrue(($students['status'] ?? 0) !== 404, 'Regression: GET /students still resolves');

            $classes = $router->dispatch('GET', '/classes');
            self::assertTrue(($classes['status'] ?? 0) !== 404, 'Regression: GET /classes still resolves');

            $timetables = $router->dispatch('GET', '/timetables');
            self::assertTrue(($timetables['status'] ?? 0) !== 404, 'Regression: GET /timetables still resolves');
        }
    }

    private static function sqlite(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE academic_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                session_name VARCHAR(100) NOT NULL,
                start_date TEXT NOT NULL,
                end_date TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT "active",
                is_current INTEGER NOT NULL DEFAULT 0,
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

final class FakeAcademicSessionRepository implements AcademicSessionRepositoryInterface
{
    public array $rows = [
        1 => ['id' => 1, 'session_name' => '2026-2027', 'start_date' => '2026-01-01', 'end_date' => '2027-03-31', 'status' => 'active', 'is_current' => 1, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'session_name' => '2027-2028', 'start_date' => '2027-04-01', 'end_date' => '2028-03-31', 'status' => 'inactive', 'is_current' => 0, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        $rows = array_values($this->rows);
        if ($search !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => stripos((string) $row['session_name'], $search) !== false));
        }
        $items = array_map(fn (array $row): AcademicSession => $this->map($row), $rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?AcademicSession
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByName(string $name): ?AcademicSession
    {
        foreach ($this->rows as $row) {
            if ($row['session_name'] === $name) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): AcademicSession
    {
        $nextId = max(array_keys($this->rows) ?: [0]) + 1;
        $this->rows[$nextId] = [
            'id' => $nextId,
            'session_name' => $attributes['session_name'] ?? null,
            'start_date' => $attributes['start_date'] ?? null,
            'end_date' => $attributes['end_date'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'is_current' => $attributes['is_current'] ?? 0,
            'created_at' => '2026-01-01',
            'updated_at' => '2026-01-01',
        ];

        return $this->map($this->rows[$nextId]);
    }

    public function update(int $id, array $attributes): ?AcademicSession
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        foreach (['session_name', 'start_date', 'end_date', 'status', 'is_current'] as $field) {
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

    private function map(array $row): AcademicSession
    {
        return new AcademicSession(
            (int) $row['id'],
            $row['session_name'],
            $row['status'] ?? 'active',
            $row['start_date'] ?? null,
            $row['end_date'] ?? null,
            isset($row['is_current']) ? (int) $row['is_current'] : null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
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
        return $entity instanceof AcademicSession ? ['id' => $entity->id()] : null;
    }
}

AcademicSessionModuleTest::run();
