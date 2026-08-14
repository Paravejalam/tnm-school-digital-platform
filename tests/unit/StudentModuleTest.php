<?php

declare(strict_types=1);

/**
 * Unit tests for the Student module.
 *
 * Runs with plain PHP — no external framework required.
 * Usage: php tests/unit/StudentModuleTest.php
 *
 * Covers:
 * - Student entity getters
 * - StudentResponse shape
 * - StudentListRequest pagination and search parsing
 * - CreateStudentRequest / UpdateStudentRequest accessors
 * - StudentValidator create/update validation
 * - StudentRepository no-DB behavior
 * - StudentRepository SQLite-backed CRUD + search
 * - StudentService create/update/delete flows
 * - Reference validation for academic_session/class/section/user
 * - StudentServiceProvider DI bindings and aliases
 * - StudentController request/response handling
 * - Router dispatch regression for /students and completed modules
 *
 * Authority: .github/AGENT.md
 */

require __DIR__ . '/../../backend/config/bootstrap.php';

use App\AcademicClass\AcademicClass;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\AcademicSession\AcademicSession;
use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\User;
use App\Auth\UserRepositoryInterface;
use App\Auth\ValidationException;
use App\Core\Router;
use App\Http\RequestHelper;
use App\Section\Section;
use App\Section\SectionRepositoryInterface;
use App\Student\CreateStudentRequest;
use App\Student\Student;
use App\Student\StudentController;
use App\Student\StudentException;
use App\Student\StudentListRequest;
use App\Student\StudentRepository;
use App\Student\StudentRepositoryInterface;
use App\Student\StudentResponse;
use App\Student\StudentService;
use App\Student\StudentServiceInterface;
use App\Student\StudentServiceProvider;
use App\Student\StudentValidator;
use App\Student\UpdateStudentRequest;
use App\Support\AppContainer;

final class StudentModuleTest
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
        self::testValidatorRejectsMissingRequiredFields();
        self::testValidatorRejectsInvalidEmail();
        self::testValidatorAcceptsValidUpdate();
        self::testValidatorRejectsEmptyAdmissionNumberOnUpdate();
        self::testValidatorRejectsInvalidEmailOnUpdate();
        self::testExceptionExtendsRuntimeException();
        self::testRepositoryNoDatabaseBehavior();
        self::testRepositoryCreateAndFindById();
        self::testRepositoryFindByAdmissionNumber();
        self::testRepositoryPaginateWithFilters();
        self::testRepositoryUpdatePerservesFieldsOnPartialUpdate();
        self::testRepositoryDeleteNotSupportedOnSqlite();
        self::testServiceNoDatabaseCreateMapsEntity();
        self::testServiceCreatePersistsAndReturnsEntity();
        self::testServiceCreateRejectsDuplicateAdmissionNumber();
        self::testServiceCreateRejectsInvalidEmail();
        self::testServiceCreateRejectsMissingAcademicSessionReference();
        self::testServiceCreateRejectsMissingClassReference();
        self::testServiceCreateRejectsMissingSectionReference();
        self::testServiceCreateRejectsMissingUserReference();
        self::testServiceCreateRejectsInvalidStatus();
        self::testServiceUpdatePersistsAndReturnsEntity();
        self::testServiceUpdateNotFoundReturnsNull();
        self::testServiceUpdateRejectsDuplicateAdmissionNumber();
        self::testServiceUpdateRejectsInvalidEmail();
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
        $item = new Student(
            1,
            'ADM-2026-001',
            'Aisha',
            'Khan',
            'aisha@example.com',
            '9876543210',
            'Grade 1',
            'A',
            'active',
            5,
            '12',
            '2015-06-12',
            'female',
            2,
            10,
            22,
            '2026-01-01',
            '2026-01-02',
            null
        );

        self::assertSame(1, $item->id(), 'id');
        self::assertSame('ADM-2026-001', $item->admissionNumber(), 'admission_number');
        self::assertSame('Aisha', $item->firstName(), 'first_name');
        self::assertSame('Khan', $item->lastName(), 'last_name');
        self::assertSame('aisha@example.com', $item->email(), 'email');
        self::assertSame('9876543210', $item->phone(), 'phone');
        self::assertSame('Grade 1', $item->className(), 'class_name');
        self::assertSame('A', $item->section(), 'section');
        self::assertSame('active', $item->status(), 'status');
        self::assertSame(5, $item->userId(), 'user_id');
        self::assertSame('12', $item->rollNumber(), 'roll_number');
        self::assertSame('2015-06-12', $item->dateOfBirth(), 'date_of_birth');
        self::assertSame('female', $item->gender(), 'gender');
        self::assertSame(2, $item->academicSessionId(), 'academic_session_id');
        self::assertSame(10, $item->classId(), 'class_id');
        self::assertSame(22, $item->sectionId(), 'section_id');
        self::assertSame('2026-01-01', $item->createdAt(), 'created_at');
        self::assertSame('2026-01-02', $item->updatedAt(), 'updated_at');
        self::assertSame(null, $item->deletedAt(), 'deleted_at default null');
    }

    // -------------------------------------------------------------------------
    // Response DTO
    // -------------------------------------------------------------------------

    private static function testResponseExposesExpectedFields(): void
    {
        $item = new Student(1, 'ADM-2026-001', 'Aisha', 'Khan', 'aisha@example.com', '9876543210', 'Grade 1', 'A', 'active', null, null, null, null, null, null, null, null, null, null);
        $data = StudentResponse::fromStudent($item);

        self::assertSame(1, $data['id'], 'response id');
        self::assertSame('ADM-2026-001', $data['admission_number'], 'response admission_number');
        self::assertSame('Aisha', $data['first_name'], 'response first_name');
        self::assertSame('Khan', $data['last_name'], 'response last_name');
        self::assertSame('aisha@example.com', $data['email'], 'response email');
        self::assertSame('9876543210', $data['phone'], 'response phone');
        self::assertSame('Grade 1', $data['class_name'], 'response class_name');
        self::assertSame('A', $data['section'], 'response section');
        self::assertSame('active', $data['status'], 'response status');
        self::assertSame(9, count($data), 'response has exactly 9 fields');
    }

    private static function testResponseCollection(): void
    {
        $items = [
            new Student(1, 'ADM-2026-001', 'Aisha', 'Khan', 'aisha@example.com', '9876543210', 'Grade 1', 'A', 'active', null, null, null, null, null, null, null, null, null, null),
            new Student(2, 'ADM-2026-002', 'Zaid', 'Ali', 'zaid@example.com', '9876543211', 'Grade 1', 'A', 'inactive', null, null, null, null, null, null, null, null, null, null),
        ];

        $collection = StudentResponse::collection($items);

        self::assertSame(2, count($collection), 'collection length');
        self::assertSame('ADM-2026-001', $collection[0]['admission_number'], 'first admission_number');
        self::assertSame('inactive', $collection[1]['status'], 'second status');
    }

    // -------------------------------------------------------------------------
    // ListRequest
    // -------------------------------------------------------------------------

    private static function testListRequestParsesQuery(): void
    {
        $request = new StudentListRequest([
            'page' => 2,
            'per_page' => 50,
            'name' => 'Aisha',
            'admission_number' => 'ADM-2026-001',
        ]);

        self::assertSame(2, $request->page(), 'page parsed');
        self::assertSame(50, $request->perPage(), 'per_page parsed');
        self::assertSame('Aisha', $request->name(), 'name parsed');
        self::assertSame('ADM-2026-001', $request->admissionNumber(), 'admission_number parsed');

        $default = new StudentListRequest([]);
        self::assertSame(1, $default->page(), 'default page');
        self::assertSame(15, $default->perPage(), 'default per_page');
        self::assertSame(null, $default->name(), 'default name null');
        self::assertSame(null, $default->admissionNumber(), 'default admission_number null');

        $bounded = new StudentListRequest(['page' => 0, 'per_page' => 500]);
        self::assertSame(1, $bounded->page(), 'page clamped to 1');
        self::assertSame(100, $bounded->perPage(), 'per_page capped at 100');
    }

    // -------------------------------------------------------------------------
    // Create/Update requests
    // -------------------------------------------------------------------------

    private static function testCreateRequestPayload(): void
    {
        $payload = ['admission_number' => 'ADM-2026-001', 'first_name' => 'Aisha', 'last_name' => 'Khan'];
        $request = new CreateStudentRequest($payload);

        self::assertSame($payload, $request->payload(), 'create payload passthrough');
    }

    private static function testUpdateRequestIdAndPayload(): void
    {
        $payload = ['status' => 'inactive'];
        $request = new UpdateStudentRequest(7, $payload);

        self::assertSame(7, $request->id(), 'update id');
        self::assertSame($payload, $request->payload(), 'update payload passthrough');
    }

    // -------------------------------------------------------------------------
    // Validator
    // -------------------------------------------------------------------------

    private static function testValidatorAcceptsValidCreate(): void
    {
        $validator = new StudentValidator();

        try {
            $validator->validateCreate([
                'admission_number' => 'ADM-2026-001',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'email' => 'aisha@example.com',
            ]);
            self::pass('valid create payload accepted');
        } catch (ValidationException $exception) {
            self::fail('valid create payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorAcceptsAllSchemaStatuses(): void
    {
        $validator = new StudentValidator();

        try {
            foreach (['active', 'inactive', 'graduated', 'transferred', 'withdrawn'] as $status) {
                $validator->validateCreate([
                    'admission_number' => 'ADM-2026-001',
                    'first_name' => 'Aisha',
                    'last_name' => 'Khan',
                    'class_name' => 'Grade 1',
                    'status' => $status,
                ]);
            }
            self::pass('all schema statuses accepted');
        } catch (ValidationException $exception) {
            self::fail('schema status rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsMissingRequiredFields(): void
    {
        $validator = new StudentValidator();

        try {
            $validator->validateCreate(['status' => 'active']);
            self::fail('missing required fields should be rejected');
        } catch (ValidationException $exception) {
            $errors = $exception->errors();
            self::assertTrue(isset($errors['admission_number']), 'admission_number error present');
            self::assertTrue(isset($errors['first_name']), 'first_name error present');
            self::assertTrue(isset($errors['last_name']), 'last_name error present');
            self::assertTrue(isset($errors['class_name']), 'class_name error present');
        }
    }

    private static function testValidatorRejectsInvalidEmail(): void
    {
        $validator = new StudentValidator();

        try {
            $validator->validateCreate([
                'admission_number' => 'ADM-2026-001',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'email' => 'not-an-email',
            ]);
            self::fail('invalid email should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['email']), 'email error present');
        }
    }

    private static function testValidatorAcceptsValidUpdate(): void
    {
        $validator = new StudentValidator();

        try {
            $validator->validateUpdate(['admission_number' => 'ADM-2026-002']);
            $validator->validateUpdate(['email' => 'new@example.com']);
            $validator->validateUpdate(['status' => 'transferred']);
            self::pass('valid update payloads accepted');
        } catch (ValidationException $exception) {
            self::fail('valid update payload rejected: ' . json_encode($exception->errors()));
        }
    }

    private static function testValidatorRejectsEmptyAdmissionNumberOnUpdate(): void
    {
        $validator = new StudentValidator();

        try {
            $validator->validateUpdate(['admission_number' => '   ']);
            self::fail('empty admission_number should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['admission_number']), 'admission_number error present');
        }
    }

    private static function testValidatorRejectsInvalidEmailOnUpdate(): void
    {
        $validator = new StudentValidator();

        try {
            $validator->validateUpdate(['email' => 'not-valid']);
            self::fail('invalid email update should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['email']), 'email error present');
        }
    }

    // -------------------------------------------------------------------------
    // Exception
    // -------------------------------------------------------------------------

    private static function testExceptionExtendsRuntimeException(): void
    {
        self::assertTrue(new StudentException('boom') instanceof \RuntimeException, 'StudentException extends RuntimeException');
        self::assertSame('boom', (new StudentException('boom'))->getMessage(), 'exception message');
    }

    // -------------------------------------------------------------------------
    // Repository — no DB
    // -------------------------------------------------------------------------

    private static function testRepositoryNoDatabaseBehavior(): void
    {
        $repository = new StudentRepository(null);

        self::assertSame(0, $repository->paginate(1, 15)['total'], 'paginate no-db empty');
        self::assertSame([], $repository->paginate(1, 15)['items'], 'paginate no-db items');
        self::assertSame(null, $repository->findById(1), 'findById no-db null');
        self::assertSame(null, $repository->findByAdmissionNumber('ADM-2026-001'), 'findByAdmissionNumber no-db null');
        self::assertSame(false, $repository->delete(1), 'delete no-db false');

        $created = $repository->create([
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Aisha',
            'last_name' => 'Khan',
            'class_name' => 'Grade 1',
            'status' => 'active',
        ]);
        self::assertTrue($created instanceof Student, 'create no-db returns entity');
        if ($created instanceof Student) {
            self::assertSame(null, $created->id(), 'no-db created has no id');
            self::assertSame('ADM-2026-001', $created->admissionNumber(), 'no-db created admission_number');
            self::assertSame('active', $created->status(), 'no-db created default status');
        }

        self::assertSame(null, $repository->update(1, ['status' => 'inactive']), 'update no-db null');
    }

    // -------------------------------------------------------------------------
    // Repository — SQLite CRUD
    // -------------------------------------------------------------------------

    private static function testRepositoryCreateAndFindById(): void
    {
        $repository = new StudentRepository(self::sqlite());
        $created = $repository->create([
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Aisha',
            'last_name' => 'Khan',
            'email' => 'aisha@example.com',
            'phone' => '9876543210',
            'class_name' => 'Grade 1',
            'section' => 'A',
            'status' => 'active',
            'user_id' => 5,
            'roll_number' => '12',
            'date_of_birth' => '2015-06-12',
            'gender' => 'female',
            'academic_session_id' => 2,
            'class_id' => 10,
            'section_id' => 22,
        ]);

        self::assertTrue($created instanceof Student, 'create returns entity');
        if ($created instanceof Student) {
            self::assertTrue($created->id() > 0, 'create assigns id');
        }

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Student, 'findById returns entity');
        if ($found instanceof Student) {
            self::assertSame('ADM-2026-001', $found->admissionNumber(), 'findById admission_number');
            self::assertSame('Aisha', $found->firstName(), 'findById first_name');
            self::assertSame('Grade 1', $found->className(), 'findById class_name');
            self::assertSame('active', $found->status(), 'findById status');
        }

        self::assertSame(null, $repository->findById(999), 'missing id returns null');
    }

    private static function testRepositoryFindByAdmissionNumber(): void
    {
        $repository = new StudentRepository(self::sqlite());
        $repository->create([
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Aisha',
            'last_name' => 'Khan',
            'class_name' => 'Grade 1',
            'status' => 'active',
        ]);

        $found = $repository->findByAdmissionNumber('ADM-2026-001');
        self::assertTrue($found instanceof Student, 'findByAdmissionNumber finds existing');
        if ($found instanceof Student) {
            self::assertSame(1, $found->id(), 'findByAdmissionNumber id');
        }

        self::assertSame(null, $repository->findByAdmissionNumber('ADM-2026-999'), 'findByAdmissionNumber missing null');
    }

    private static function testRepositoryPaginateWithFilters(): void
    {
        $repository = new StudentRepository(self::sqlite());
        $repository->create(['admission_number' => 'ADM-2026-001', 'first_name' => 'Aisha', 'last_name' => 'Khan', 'class_name' => 'Grade 1', 'status' => 'active']);
        $repository->create(['admission_number' => 'ADM-2026-002', 'first_name' => 'Zaid', 'last_name' => 'Ali', 'class_name' => 'Grade 1', 'status' => 'inactive']);
        $repository->create(['admission_number' => 'ADM-2026-003', 'first_name' => 'Sara', 'last_name' => 'Naeem', 'class_name' => 'Grade 2', 'status' => 'active']);

        $all = $repository->paginate(1, 15);
        self::assertSame(3, $all['total'], 'paginate total');
        self::assertSame(3, count($all['items']), 'paginate item count');

        $filtered = $repository->paginate(1, 15, 'Zaid');
        self::assertSame(1, $filtered['total'], 'name filter total');
        self::assertSame(1, count($filtered['items']), 'name filter item count');

        $byNumber = $repository->paginate(1, 15, null, 'ADM-2026-002');
        self::assertSame(1, $byNumber['total'], 'admission_number filter total');
        self::assertSame('ADM-2026-002', $byNumber['items'][0]->admissionNumber(), 'admission_number filter value');
    }

    private static function testRepositoryUpdatePerservesFieldsOnPartialUpdate(): void
    {
        $repository = new StudentRepository(self::sqlite());
        $created = $repository->create([
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Aisha',
            'last_name' => 'Khan',
            'email' => 'aisha@example.com',
            'class_name' => 'Grade 1',
            'section' => 'A',
            'status' => 'active',
            'user_id' => 5,
            'roll_number' => '12',
            'date_of_birth' => '2015-06-12',
            'gender' => 'female',
            'academic_session_id' => 2,
            'class_id' => 10,
            'section_id' => 22,
        ]);

        $updated = $repository->update((int) $created->id(), ['status' => 'inactive']);
        self::assertTrue($updated instanceof Student, 'partial update returns entity');
        if ($updated instanceof Student) {
            self::assertSame('inactive', $updated->status(), 'status updated');
            self::assertSame('Aisha', $updated->firstName(), 'first_name preserved');
            self::assertSame('ADM-2026-001', $updated->admissionNumber(), 'admission_number preserved');
            self::assertSame('Grade 1', $updated->className(), 'class_name preserved');
            self::assertSame('A', $updated->section(), 'section preserved');
        }
    }

    private static function testRepositoryDeleteNotSupportedOnSqlite(): void
    {
        $repository = new StudentRepository(self::sqlite());
        $created = $repository->create([
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Aisha',
            'last_name' => 'Khan',
            'class_name' => 'Grade 1',
            'status' => 'active',
        ]);

        $result = $repository->delete((int) $created->id());
        self::assertSame(false, $result, 'delete uses NOW() (MySQL-only) so false on SQLite');

        $found = $repository->findById((int) $created->id());
        self::assertTrue($found instanceof Student, 'row remains when delete unsupported on SQLite');
    }

    // -------------------------------------------------------------------------
    // Service
    // -------------------------------------------------------------------------

    private static function testServiceNoDatabaseCreateMapsEntity(): void
    {
        $service = new StudentService(new StudentRepository(null), new StudentValidator());

        $result = $service->create(new CreateStudentRequest([
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Aisha',
            'last_name' => 'Khan',
            'class_name' => 'Grade 1',
            'status' => 'active',
        ]));

        self::assertTrue($result instanceof Student, 'no-db create returns entity');
        if ($result instanceof Student) {
            self::assertSame('ADM-2026-001', $result->admissionNumber(), 'no-db service admission_number');
            self::assertSame('active', $result->status(), 'no-db service default status');
        }
    }

    private static function testServiceCreatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeStudentRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true, true);

        $result = $service->create(new CreateStudentRequest([
            'admission_number' => 'ADM-2026-003',
            'first_name' => 'Sara',
            'last_name' => 'Naeem',
            'class_name' => 'Grade 1',
            'status' => 'active',
            'academic_session_id' => 2,
            'class_id' => 10,
            'section_id' => 22,
            'user_id' => 5,
        ]));

        self::assertTrue($result instanceof Student, 'create returns entity');
        if ($result instanceof Student) {
            self::assertSame('ADM-2026-003', $result->admissionNumber(), 'created admission_number');
            self::assertSame('active', $result->status(), 'created status');
        }
        self::assertSame('ADM-2026-003', $repository->rows[3]['admission_number'], 'create stored in repository');
        self::assertSame('CREATE', $logger->lastAction, 'create audit logged');
    }

    private static function testServiceCreateRejectsDuplicateAdmissionNumber(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        try {
            $service->create(new CreateStudentRequest([
                'admission_number' => 'ADM-2026-001',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'status' => 'active',
            ]));
            self::fail('duplicate admission_number should be rejected');
        } catch (StudentException $exception) {
            self::assertSame('Admission number is already registered.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceCreateRejectsInvalidEmail(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        try {
            $service->create(new CreateStudentRequest([
                'admission_number' => 'ADM-2026-099',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'email' => 'nope',
            ]));
            self::fail('invalid email should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['email']), 'invalid email error present');
        }
    }

    private static function testServiceCreateRejectsMissingAcademicSessionReference(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, false, true, true, true, true);

        try {
            $service->create(new CreateStudentRequest([
                'admission_number' => 'ADM-2026-099',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'academic_session_id' => 999,
            ]));
            self::fail('missing academic_session reference should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['academic_session_id']), 'academic_session_id error present');
        }
    }

    private static function testServiceCreateRejectsMissingClassReference(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, false, true, true, true);

        try {
            $service->create(new CreateStudentRequest([
                'admission_number' => 'ADM-2026-099',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'class_id' => 999,
            ]));
            self::fail('missing class reference should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['class_id']), 'class_id error present');
        }
    }

    private static function testServiceCreateRejectsMissingSectionReference(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, false, true, true);

        try {
            $service->create(new CreateStudentRequest([
                'admission_number' => 'ADM-2026-099',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'section_id' => 999,
            ]));
            self::fail('missing section reference should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['section_id']), 'section_id error present');
        }
    }

    private static function testServiceCreateRejectsMissingUserReference(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, false, true);

        try {
            $service->create(new CreateStudentRequest([
                'admission_number' => 'ADM-2026-099',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'user_id' => 999,
            ]));
            self::fail('missing user reference should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['user_id']), 'user_id error present');
        }
    }

    private static function testServiceCreateRejectsInvalidStatus(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        try {
            $service->create(new CreateStudentRequest([
                'admission_number' => 'ADM-2026-099',
                'first_name' => 'Aisha',
                'last_name' => 'Khan',
                'class_name' => 'Grade 1',
                'status' => 'unknown',
            ]));
            self::fail('non-schema status should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['status']), 'status error present');
        }
    }

    private static function testServiceUpdatePersistsAndReturnsEntity(): void
    {
        $repository = new FakeStudentRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true, true);

        $result = $service->update(new UpdateStudentRequest(1, ['status' => 'inactive']));

        self::assertTrue($result instanceof Student, 'update returns entity');
        if ($result instanceof Student) {
            self::assertSame('inactive', $result->status(), 'updated status');
            self::assertSame('ADM-2026-001', $result->admissionNumber(), 'admission_number retained');
        }
        self::assertSame('inactive', $repository->rows[1]['status'], 'update stored in repository');
        self::assertSame('UPDATE', $logger->lastAction, 'update audit logged');
    }

    private static function testServiceUpdateNotFoundReturnsNull(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        $result = $service->update(new UpdateStudentRequest(999, ['status' => 'inactive']));

        self::assertSame(null, $result, 'missing student returns null');
    }

    private static function testServiceUpdateRejectsDuplicateAdmissionNumber(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        try {
            $service->update(new UpdateStudentRequest(1, ['admission_number' => 'ADM-2026-002']));
            self::fail('duplicate admission_number should be rejected');
        } catch (StudentException $exception) {
            self::assertSame('Admission number is already registered.', $exception->getMessage(), 'duplicate message');
        }
    }

    private static function testServiceUpdateRejectsInvalidEmail(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        try {
            $service->update(new UpdateStudentRequest(1, ['email' => 'bad-email']));
            self::fail('invalid email update should be rejected');
        } catch (ValidationException $exception) {
            self::assertTrue(isset($exception->errors()['email']), 'email error present');
        }
    }

    private static function testServiceDeleteReturnsTrueWhenDeleted(): void
    {
        $repository = new FakeStudentRepository();
        $logger = new FakeAuditLogger();
        $service = self::serviceWithReferences($repository, $logger, true, true, true, true, true);

        $result = $service->delete(1);

        self::assertSame(true, $result, 'delete returns true');
        self::assertSame('DELETE', $logger->lastAction, 'delete audit logged');
    }

    private static function testServiceDeleteReturnsFalseWhenMissing(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        $result = $service->delete(999);

        self::assertSame(false, $result, 'missing student delete returns false');
    }

    private static function testServiceListDelegatesToRepository(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        $result = $service->list(new StudentListRequest(['page' => 1, 'per_page' => 10]));

        self::assertSame(2, $result['total'], 'list total from repository');
        self::assertSame(1, $result['page'], 'list page');
        self::assertSame(10, $result['per_page'], 'list per_page');
    }

    private static function testServiceFindDelegatesToRepository(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);

        $found = $service->find(1);
        self::assertTrue($found instanceof Student, 'find returns entity');
        if ($found instanceof Student) {
            self::assertSame('ADM-2026-001', $found->admissionNumber(), 'found admission_number');
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

        (new StudentServiceProvider())->register($container);

        $service = $container->get(StudentService::class);
        self::assertTrue($service instanceof StudentServiceInterface, 'StudentService bound to interface');

        $repository = $container->get(StudentRepository::class);
        self::assertTrue($repository instanceof StudentRepositoryInterface, 'StudentRepository bound to interface');

        $controller = $container->get(StudentController::class);
        self::assertTrue($controller instanceof StudentController, 'StudentController bound');

        self::assertTrue($container->get('student.validator') instanceof StudentValidator, 'student.validator alias');
        self::assertTrue($container->get('student.repository') instanceof StudentRepositoryInterface, 'student.repository alias');
        self::assertTrue($container->get('student.service') instanceof StudentServiceInterface, 'student.service alias');
        self::assertTrue($container->get('student.controller') instanceof StudentController, 'student.controller alias');
    }

    // -------------------------------------------------------------------------
    // Controller
    // -------------------------------------------------------------------------

    private static function testControllerIndexSuccess(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);
        $controller = new StudentController($service);

        $request = new RequestHelper([], ['page' => 1, 'per_page' => 15], [], [], null);
        $response = $controller->index($request);

        self::assertSame('success', $response['status'], 'index success status');
        self::assertSame(2, count($response['data']['items']), 'index items count');
        self::assertSame(2, $response['data']['pagination']['total'], 'index total');
    }

    private static function testControllerShowNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);
        $controller = new StudentController($service);

        $response = $controller->show(999);

        self::assertSame('error', $response['status'], 'show missing error status');
        self::assertSame('Student not found.', $response['message'], 'show 404 message');
    }

    private static function testControllerStoreValidationError(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);
        $controller = new StudentController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'active']);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store validation error status');
        self::assertTrue(isset($response['details']['validation']), 'store validation details present');
    }

    private static function testControllerStoreConflict(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);
        $controller = new StudentController($service);

        $request = new RequestHelper([], [], [], [], [
            'admission_number' => 'ADM-2026-001',
            'first_name' => 'Aisha',
            'last_name' => 'Khan',
            'class_name' => 'Grade 1',
        ]);
        $response = $controller->store($request);

        self::assertSame('error', $response['status'], 'store conflict error status');
        self::assertSame('Admission number is already registered.', $response['message'], 'store conflict message');
    }

    private static function testControllerStoreSuccess(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);
        $controller = new StudentController($service);

        $request = new RequestHelper([], [], [], [], [
            'admission_number' => 'ADM-2026-003',
            'first_name' => 'Sara',
            'last_name' => 'Naeem',
            'class_name' => 'Grade 1',
            'status' => 'active',
        ]);
        $response = $controller->store($request);

        self::assertSame('success', $response['status'], 'store success status');
        self::assertSame('ADM-2026-003', $response['data']['admission_number'], 'store success admission_number');
        self::assertSame('active', $response['data']['status'], 'store success status value');
    }

    private static function testControllerUpdateNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);
        $controller = new StudentController($service);

        $request = new RequestHelper([], [], [], [], ['status' => 'inactive']);
        $response = $controller->update(999, $request);

        self::assertSame('error', $response['status'], 'update missing error status');
        self::assertSame('Student not found.', $response['message'], 'update missing message');
    }

    private static function testControllerDestroyNotFound(): void
    {
        $service = self::serviceWithReferences(new FakeStudentRepository(), null, true, true, true, true, true);
        $controller = new StudentController($service);

        $response = $controller->destroy(999);

        self::assertSame('error', $response['status'], 'destroy missing error status');
        self::assertSame('Student not found.', $response['message'], 'destroy missing message');
    }

    // -------------------------------------------------------------------------
    // Router dispatch regression
    // -------------------------------------------------------------------------

    private static function testRouterDispatchRoutes(): void
    {
        $kernel = new \App\Core\Kernel(__DIR__ . '/../../backend');
        $kernel->bootstrap();
        $router = $kernel->getContainer()->get('router');

        if ($router instanceof Router) {
            $health = $router->dispatch('GET', '/health');
            self::assertTrue(isset($health['success']) && $health['success'] === true, 'GET /health still works');

            $students = $router->dispatch('GET', '/students');
            self::assertTrue(($students['status'] ?? 0) !== 404, 'GET /students resolves');

            $teachers = $router->dispatch('GET', '/teachers');
            self::assertTrue(($teachers['status'] ?? 0) !== 404, 'Regression: GET /teachers still resolves');

            $academicSessions = $router->dispatch('GET', '/academic-sessions');
            self::assertTrue(($academicSessions['status'] ?? 0) !== 404, 'Regression: GET /academic-sessions still resolves');

            $classes = $router->dispatch('GET', '/classes');
            self::assertTrue(($classes['status'] ?? 0) !== 404, 'Regression: GET /classes still resolves');

            $sections = $router->dispatch('GET', '/sections');
            self::assertTrue(($sections['status'] ?? 0) !== 404, 'Regression: GET /sections still resolves');

            $subjects = $router->dispatch('GET', '/subjects');
            self::assertTrue(($subjects['status'] ?? 0) !== 404, 'Regression: GET /subjects still resolves');

            $unknown = $router->dispatch('GET', '/does-not-exist');
            self::assertSame(false, $unknown['success'], 'unknown route error flag');
            self::assertSame(404, $unknown['status'], 'unknown route 404');
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function serviceWithReferences(
        StudentRepositoryInterface $repository,
        ?FakeAuditLogger $logger,
        bool $sessionExists,
        bool $classExists,
        bool $sectionExists,
        bool $userExists,
        bool $validStatus
    ): StudentService {
        return new StudentService(
            $repository,
            new StudentValidator(),
            new FakeAcademicSessionRepository($sessionExists),
            new FakeAcademicClassRepository($classExists),
            new FakeSectionRepository($sectionExists),
            new FakeUserRepository($userExists),
            null,
            $logger
        );
    }

    private static function sqlite(): \PDO
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                admission_number TEXT NOT NULL,
                roll_number TEXT NULL,
                first_name TEXT NOT NULL,
                last_name TEXT NOT NULL,
                email TEXT NULL,
                phone TEXT NULL,
                date_of_birth TEXT NULL,
                gender TEXT NULL,
                academic_session_id INTEGER NULL,
                class_id INTEGER NULL,
                section_id INTEGER NULL,
                class_name TEXT NULL,
                section TEXT NULL,
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

final class FakeStudentRepository implements StudentRepositoryInterface
{
    public array $rows = [
        1 => ['id' => 1, 'admission_number' => 'ADM-2026-001', 'first_name' => 'Aisha', 'last_name' => 'Khan', 'email' => 'aisha@example.com', 'phone' => '9876543210', 'class_name' => 'Grade 1', 'section' => 'A', 'status' => 'active', 'user_id' => 5, 'roll_number' => '12', 'date_of_birth' => '2015-06-12', 'gender' => 'female', 'academic_session_id' => 2, 'class_id' => 10, 'section_id' => 22, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
        2 => ['id' => 2, 'admission_number' => 'ADM-2026-002', 'first_name' => 'Zaid', 'last_name' => 'Ali', 'email' => 'zaid@example.com', 'phone' => '9876543211', 'class_name' => 'Grade 1', 'section' => 'A', 'status' => 'inactive', 'user_id' => 6, 'roll_number' => '13', 'date_of_birth' => '2015-09-12', 'gender' => 'male', 'academic_session_id' => 2, 'class_id' => 10, 'section_id' => 22, 'created_at' => '2026-01-01', 'updated_at' => '2026-01-01'],
    ];

    public function paginate(int $page, int $perPage, ?string $name = null, ?string $admissionNumber = null): array
    {
        $rows = array_values($this->rows);
        if ($name !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => stripos((string) $row['first_name'], $name) !== false || stripos((string) $row['last_name'], $name) !== false));
        }
        if ($admissionNumber !== null) {
            $rows = array_values(array_filter($rows, fn (array $row): bool => (string) $row['admission_number'] === $admissionNumber));
        }
        $items = array_map(fn (array $row): Student => $this->map($row), $rows);

        return ['items' => $items, 'total' => count($items), 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?Student
    {
        return isset($this->rows[$id]) ? $this->map($this->rows[$id]) : null;
    }

    public function findByAdmissionNumber(string $admissionNumber): ?Student
    {
        foreach ($this->rows as $row) {
            if ($row['admission_number'] === $admissionNumber) {
                return $this->map($row);
            }
        }

        return null;
    }

    public function create(array $attributes): Student
    {
        $nextId = max(array_keys($this->rows) ?: [0]) + 1;
        $this->rows[$nextId] = [
            'id' => $nextId,
            'admission_number' => $attributes['admission_number'] ?? null,
            'first_name' => $attributes['first_name'] ?? null,
            'last_name' => $attributes['last_name'] ?? null,
            'email' => $attributes['email'] ?? null,
            'phone' => $attributes['phone'] ?? null,
            'class_name' => $attributes['class_name'] ?? null,
            'section' => $attributes['section'] ?? null,
            'status' => $attributes['status'] ?? 'active',
            'user_id' => $attributes['user_id'] ?? null,
            'roll_number' => $attributes['roll_number'] ?? null,
            'date_of_birth' => $attributes['date_of_birth'] ?? null,
            'gender' => $attributes['gender'] ?? null,
            'academic_session_id' => $attributes['academic_session_id'] ?? null,
            'class_id' => $attributes['class_id'] ?? null,
            'section_id' => $attributes['section_id'] ?? null,
            'created_at' => '2026-01-01',
            'updated_at' => '2026-01-01',
        ];

        return $this->map($this->rows[$nextId]);
    }

    public function update(int $id, array $attributes): ?Student
    {
        if (!isset($this->rows[$id])) {
            return null;
        }

        foreach (['user_id', 'admission_number', 'roll_number', 'first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'gender', 'academic_session_id', 'class_id', 'section_id', 'class_name', 'section', 'status'] as $field) {
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

    private function map(array $row): Student
    {
        return new Student(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['admission_number'] ?? null,
            $row['first_name'] ?? null,
            $row['last_name'] ?? null,
            $row['email'] ?? null,
            $row['phone'] ?? null,
            $row['class_name'] ?? null,
            $row['section'] ?? null,
            $row['status'] ?? 'active',
            isset($row['user_id']) ? (int) $row['user_id'] : null,
            $row['roll_number'] ?? null,
            $row['date_of_birth'] ?? null,
            $row['gender'] ?? null,
            isset($row['academic_session_id']) ? (int) $row['academic_session_id'] : null,
            isset($row['class_id']) ? (int) $row['class_id'] : null,
            isset($row['section_id']) ? (int) $row['section_id'] : null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
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
        return $this->exists ? new AcademicSession($id, '2026-2027', 'active', '2026-04-01', '2027-03-31', 1, '2026-01-01', '2026-01-02', null) : null;
    }

    public function findByName(string $name): ?AcademicSession
    {
        return $this->exists ? new AcademicSession(1, $name, 'active', '2026-04-01', '2027-03-31', 1, '2026-01-01', '2026-01-02', null) : null;
    }

    public function create(array $attributes): AcademicSession
    {
        return new AcademicSession(1, $attributes['session_name'] ?? '2026-2027', 'active', '2026-04-01', '2027-03-31', 1, '2026-01-01', '2026-01-02', null);
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

    public function findById(int $id): ?\App\AcademicClass\AcademicClass
    {
        return $this->exists ? new \App\AcademicClass\AcademicClass($id, 'Grade 1', 'G1', 2, 'active', 1, '2026-01-01', '2026-01-02', null) : null;
    }

    public function findByName(string $name): ?\App\AcademicClass\AcademicClass
    {
        return $this->exists ? new \App\AcademicClass\AcademicClass(1, $name, 'G1', 2, 'active', 1, '2026-01-01', '2026-01-02', null) : null;
    }

    public function create(array $attributes): \App\AcademicClass\AcademicClass
    {
        return new \App\AcademicClass\AcademicClass(1, $attributes['class_name'] ?? 'Grade 1', 'G1', 2, 'active', 1, '2026-01-01', '2026-01-02', null);
    }

    public function update(int $id, array $attributes): ?\App\AcademicClass\AcademicClass
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
        return $this->exists ? new Section($id, 'A', 'A', 1, 'active', 30, '2026-01-01', '2026-01-02', null) : null;
    }

    public function findByName(string $name): ?Section
    {
        return $this->exists ? new Section(1, $name, 'A', 1, 'active', 30, '2026-01-01', '2026-01-02', null) : null;
    }

    public function create(array $attributes): Section
    {
        return new Section(1, $attributes['name'] ?? 'A', 'A', 1, 'active', 30, '2026-01-01', '2026-01-02', null);
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

final class FakeUserRepository implements UserRepositoryInterface
{
    public function __construct(private bool $exists)
    {
    }

    public function findByEmail(string $email): ?User
    {
        return $this->exists ? new User(1, 'John Doe', 'john@example.com', 'hashed') : null;
    }

    public function findById(int $id): ?User
    {
        return $this->exists ? new User($id, 'John Doe', 'john@example.com', 'hashed') : null;
    }

    public function create(array $attributes): User
    {
        return new User(1, 'John Doe', $attributes['email'] ?? 'john@example.com', 'hashed');
    }

    public function assignRole(int $userId, int $roleId): void
    {
    }

    public function findUserRole(int $userId): ?string
    {
        return $userId > 0 ? 'admin' : null;
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
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
        return $entity instanceof Student ? ['id' => $entity->id(), 'admission_number' => $entity->admissionNumber()] : null;
    }
}

StudentModuleTest::run();
