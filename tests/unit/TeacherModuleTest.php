<?php

declare(strict_types=1);

/**
 * Tests for the Teacher module.
 * Run: C:\xampp\php\php.exe tests/unit/TeacherModuleTest.php
 */

$backendDir = __DIR__ . '/../../backend';
require_once $backendDir . '/app/helpers/functions.php';

spl_autoload_register(function (string $class) use ($backendDir): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $backendDir . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Teacher\Teacher;
use App\Teacher\TeacherController;
use App\Teacher\TeacherException;
use App\Teacher\TeacherListRequest;
use App\Teacher\TeacherRepository;
use App\Teacher\TeacherRepositoryInterface;
use App\Teacher\TeacherResponse;
use App\Teacher\TeacherService;
use App\Teacher\TeacherServiceInterface;
use App\Teacher\TeacherValidator;
use App\Teacher\CreateTeacherRequest;
use App\Teacher\UpdateTeacherRequest;
use App\Auth\ValidationException;
use App\Core\Kernel;
use App\Core\Router;

$pass = 0;
$fail = 0;

function assert_test(string $name, bool $condition): void
{
    global $pass, $fail;
    if ($condition) { echo "[PASS] $name" . PHP_EOL; $pass++; }
    else { echo "[FAIL] $name" . PHP_EOL; $fail++; }
}

echo "=== Teacher Entity Tests ===" . PHP_EOL;

$teacher = new Teacher(
    1, 'EMP-001', 'Anand', 'Sharma', 'anand@example.com', '9876543210',
    'Mathematics', 'Senior Teacher', 'active', 5, 'male',
    '2020-06-15', '2026-01-01', '2026-01-02', null
);

assert_test('Entity: id()', $teacher->id() === 1);
assert_test('Entity: employeeId()', $teacher->employeeId() === 'EMP-001');
assert_test('Entity: firstName()', $teacher->firstName() === 'Anand');
assert_test('Entity: lastName()', $teacher->lastName() === 'Sharma');
assert_test('Entity: email()', $teacher->email() === 'anand@example.com');
assert_test('Entity: phone()', $teacher->phone() === '9876543210');
assert_test('Entity: department()', $teacher->department() === 'Mathematics');
assert_test('Entity: designation()', $teacher->designation() === 'Senior Teacher');
assert_test('Entity: status()', $teacher->status() === 'active');
assert_test('Entity: userId()', $teacher->userId() === 5);
assert_test('Entity: gender()', $teacher->gender() === 'male');
assert_test('Entity: dateJoined()', $teacher->dateJoined() === '2020-06-15');
assert_test('Entity: createdAt()', $teacher->createdAt() === '2026-01-01');
assert_test('Entity: updatedAt()', $teacher->updatedAt() === '2026-01-02');
assert_test('Entity: deletedAt()', $teacher->deletedAt() === null);

// Minimal entity
$minimal = new Teacher();
assert_test('Entity: minimal id is null', $minimal->id() === null);
assert_test('Entity: minimal employeeId is null', $minimal->employeeId() === null);
assert_test('Entity: minimal firstName is null', $minimal->firstName() === null);
assert_test('Entity: minimal status is null', $minimal->status() === null);
assert_test('Entity: minimal department is null', $minimal->department() === null);

echo PHP_EOL . "=== TeacherResponse Tests ===" . PHP_EOL;

$response = TeacherResponse::fromTeacher($teacher);
assert_test('Response: has id', $response['id'] === 1);
assert_test('Response: has employee_id', $response['employee_id'] === 'EMP-001');
assert_test('Response: has first_name', $response['first_name'] === 'Anand');
assert_test('Response: has last_name', $response['last_name'] === 'Sharma');
assert_test('Response: has email', $response['email'] === 'anand@example.com');
assert_test('Response: has phone', $response['phone'] === '9876543210');
assert_test('Response: has department', $response['department'] === 'Mathematics');
assert_test('Response: has designation', $response['designation'] === 'Senior Teacher');
assert_test('Response: has status', $response['status'] === 'active');

// Ensure sensitive/internal fields not exposed
assert_test('Response: no user_id', !array_key_exists('user_id', $response));
assert_test('Response: no deleted_at', !array_key_exists('deleted_at', $response));
assert_test('Response: no gender', !array_key_exists('gender', $response));
assert_test('Response: no date_joined', !array_key_exists('date_joined', $response));
assert_test('Response: no password', !array_key_exists('password', $response));

$teacher2 = new Teacher(2, 'EMP-002', 'Priya', 'Singh');
$collection = TeacherResponse::collection([$teacher, $teacher2]);
assert_test('Collection: returns array of 2', count($collection) === 2);
assert_test('Collection: first has correct id', $collection[0]['id'] === 1);
assert_test('Collection: second has correct id', $collection[1]['id'] === 2);

echo PHP_EOL . "=== TeacherListRequest Tests ===" . PHP_EOL;

$listReq = new TeacherListRequest(['page' => '3', 'per_page' => '25', 'name' => 'Sharma', 'employee_id' => 'EMP-001']);
assert_test('ListRequest: page()', $listReq->page() === 3);
assert_test('ListRequest: perPage()', $listReq->perPage() === 25);
assert_test('ListRequest: name()', $listReq->name() === 'Sharma');
assert_test('ListRequest: employeeId()', $listReq->employeeId() === 'EMP-001');

$defaultReq = new TeacherListRequest([]);
assert_test('ListRequest: default page', $defaultReq->page() === 1);
assert_test('ListRequest: default perPage', $defaultReq->perPage() === 15);
assert_test('ListRequest: null name', $defaultReq->name() === null);
assert_test('ListRequest: null employeeId', $defaultReq->employeeId() === null);

$cappedReq = new TeacherListRequest(['per_page' => '500']);
assert_test('ListRequest: perPage capped at 100', $cappedReq->perPage() === 100);

$negativeReq = new TeacherListRequest(['page' => '-1', 'per_page' => '0']);
assert_test('ListRequest: negative page clamps to 1', $negativeReq->page() === 1);
assert_test('ListRequest: zero perPage clamps to 1', $negativeReq->perPage() === 1);

// Whitespace-only name returns null
$emptyNameReq = new TeacherListRequest(['name' => '   ', 'employee_id' => '  ']);
assert_test('ListRequest: whitespace name is null', $emptyNameReq->name() === null);
assert_test('ListRequest: whitespace employeeId is null', $emptyNameReq->employeeId() === null);

echo PHP_EOL . "=== CreateTeacherRequest Tests ===" . PHP_EOL;

$createReq = new CreateTeacherRequest(['employee_id' => 'EMP-003', 'first_name' => 'Test', 'department' => 'Science']);
assert_test('CreateRequest: payload()', $createReq->payload()['employee_id'] === 'EMP-003');
assert_test('CreateRequest: has first_name', $createReq->payload()['first_name'] === 'Test');
assert_test('CreateRequest: has department', $createReq->payload()['department'] === 'Science');

$emptyCreateReq = new CreateTeacherRequest([]);
assert_test('CreateRequest: empty payload is array', is_array($emptyCreateReq->payload()));
assert_test('CreateRequest: empty payload is empty', $emptyCreateReq->payload() === []);

echo PHP_EOL . "=== UpdateTeacherRequest Tests ===" . PHP_EOL;

$updateReq = new UpdateTeacherRequest(42, ['first_name' => 'Updated', 'department' => 'Physics']);
assert_test('UpdateRequest: id()', $updateReq->id() === 42);
assert_test('UpdateRequest: payload() first_name', $updateReq->payload()['first_name'] === 'Updated');
assert_test('UpdateRequest: payload() department', $updateReq->payload()['department'] === 'Physics');

$emptyUpdateReq = new UpdateTeacherRequest(10, []);
assert_test('UpdateRequest: empty payload', $emptyUpdateReq->payload() === []);
assert_test('UpdateRequest: id preserved with empty payload', $emptyUpdateReq->id() === 10);

echo PHP_EOL . "=== TeacherValidator Tests ===" . PHP_EOL;

$validator = new TeacherValidator();

// Valid create — minimum required fields
try {
    $validator->validateCreate([
        'employee_id' => 'EMP-100',
        'first_name' => 'Test',
        'last_name' => 'Teacher',
        'department' => 'Science',
    ]);
    assert_test('Validator: valid create passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: valid create passes', false);
}

// Missing all required fields
try {
    $validator->validateCreate([]);
    assert_test('Validator: missing required throws', false);
} catch (ValidationException $e) {
    $errors = $e->errors();
    assert_test('Validator: missing employee_id error', isset($errors['employee_id']));
    assert_test('Validator: missing first_name error', isset($errors['first_name']));
    assert_test('Validator: missing last_name error', isset($errors['last_name']));
    assert_test('Validator: missing department error', isset($errors['department']));
}

// Invalid email in create
try {
    $validator->validateCreate([
        'employee_id' => 'EMP-100',
        'first_name' => 'Test',
        'last_name' => 'Teacher',
        'department' => 'Science',
        'email' => 'not-an-email',
    ]);
    assert_test('Validator: invalid email in create throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: invalid email in create throws', isset($e->errors()['email']));
}

// Valid create with valid email
try {
    $validator->validateCreate([
        'employee_id' => 'EMP-100',
        'first_name' => 'Test',
        'last_name' => 'Teacher',
        'department' => 'Science',
        'email' => 'valid@example.com',
    ]);
    assert_test('Validator: valid email in create passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: valid email in create passes', false);
}

// Invalid status in create
try {
    $validator->validateCreate([
        'employee_id' => 'EMP-100',
        'first_name' => 'Test',
        'last_name' => 'Teacher',
        'department' => 'Science',
        'status' => 'suspended',
    ]);
    assert_test('Validator: invalid status in create throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: invalid status in create throws', isset($e->errors()['status']));
}

// Valid statuses in create
try {
    $validator->validateCreate([
        'employee_id' => 'EMP-100',
        'first_name' => 'Test',
        'last_name' => 'Teacher',
        'department' => 'Science',
        'status' => 'active',
    ]);
    assert_test('Validator: status active passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: status active passes', false);
}

try {
    $validator->validateCreate([
        'employee_id' => 'EMP-100',
        'first_name' => 'Test',
        'last_name' => 'Teacher',
        'department' => 'Science',
        'status' => 'inactive',
    ]);
    assert_test('Validator: status inactive passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: status inactive passes', false);
}

// === Update validation ===

// Valid update with partial data
try {
    $validator->validateUpdate(['first_name' => 'Updated']);
    assert_test('Validator: partial update passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: partial update passes', false);
}

// Empty payload update passes
try {
    $validator->validateUpdate([]);
    assert_test('Validator: empty update passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: empty update passes', false);
}

// Invalid email in update
try {
    $validator->validateUpdate(['email' => 'bad-email']);
    assert_test('Validator: invalid email in update throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: invalid email in update throws', isset($e->errors()['email']));
}

// Valid email in update
try {
    $validator->validateUpdate(['email' => 'good@example.com']);
    assert_test('Validator: valid email in update passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: valid email in update passes', false);
}

// Empty employee_id in update
try {
    $validator->validateUpdate(['employee_id' => '  ']);
    assert_test('Validator: empty employee_id in update throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: empty employee_id in update throws', isset($e->errors()['employee_id']));
}

// Invalid status in update
try {
    $validator->validateUpdate(['status' => 'banned']);
    assert_test('Validator: invalid status in update throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: invalid status in update throws', isset($e->errors()['status']));
}

// Valid status in update
try {
    $validator->validateUpdate(['status' => 'inactive']);
    assert_test('Validator: valid status in update passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: valid status in update passes', false);
}

// Multiple errors in update
try {
    $validator->validateUpdate(['email' => 'bad', 'employee_id' => '', 'status' => 'unknown']);
    assert_test('Validator: multiple update errors throws', false);
} catch (ValidationException $e) {
    $errors = $e->errors();
    assert_test('Validator: multiple errors has email', isset($errors['email']));
    assert_test('Validator: multiple errors has employee_id', isset($errors['employee_id']));
    assert_test('Validator: multiple errors has status', isset($errors['status']));
}

echo PHP_EOL . "=== TeacherException Tests ===" . PHP_EOL;

$exception = new TeacherException('Employee id is already registered.');
assert_test('Exception: message', $exception->getMessage() === 'Employee id is already registered.');
assert_test('Exception: extends RuntimeException', $exception instanceof \RuntimeException);

$exception2 = new TeacherException('Custom error', 409);
assert_test('Exception: custom code', $exception2->getCode() === 409);

echo PHP_EOL . "=== TeacherRepository (no DB) Tests ===" . PHP_EOL;

$repo = new TeacherRepository(null);
assert_test('Repo: implements interface', $repo instanceof TeacherRepositoryInterface);

$paginated = $repo->paginate(1, 15);
assert_test('Repo: paginate returns empty items', $paginated['items'] === []);
assert_test('Repo: paginate returns total 0', $paginated['total'] === 0);
assert_test('Repo: paginate preserves page', $paginated['page'] === 1);
assert_test('Repo: paginate preserves per_page', $paginated['per_page'] === 15);

$paginated2 = $repo->paginate(3, 25, 'Sharma', 'EMP-001');
assert_test('Repo: paginate with filters preserves page', $paginated2['page'] === 3);
assert_test('Repo: paginate with filters preserves per_page', $paginated2['per_page'] === 25);

assert_test('Repo: findById returns null', $repo->findById(1) === null);
assert_test('Repo: findByEmployeeId returns null', $repo->findByEmployeeId('EMP-001') === null);
assert_test('Repo: delete returns false', $repo->delete(1) === false);

// create without DB returns Teacher from attributes
$created = $repo->create([
    'employee_id' => 'EMP-010',
    'first_name' => 'New',
    'last_name' => 'Teacher',
    'department' => 'English',
    'designation' => 'Lecturer',
]);
assert_test('Repo: create without DB returns Teacher', $created instanceof Teacher);
assert_test('Repo: created teacher has first_name', $created->firstName() === 'New');
assert_test('Repo: created teacher has employee_id', $created->employeeId() === 'EMP-010');
assert_test('Repo: created teacher has department', $created->department() === 'English');
assert_test('Repo: created teacher has designation', $created->designation() === 'Lecturer');

// create without DB defaults status to active
$createdDefault = $repo->create([
    'employee_id' => 'EMP-011',
    'first_name' => 'Default',
    'last_name' => 'Status',
    'department' => 'Hindi',
]);
assert_test('Repo: created teacher default status is active', $createdDefault->status() === 'active');

echo PHP_EOL . "=== TeacherService (no DB) Tests ===" . PHP_EOL;

$service = new TeacherService($repo, $validator);
assert_test('Service: implements interface', $service instanceof TeacherServiceInterface);

// list
$listResult = $service->list(new TeacherListRequest([]));
assert_test('Service: list returns paginated result', $listResult['items'] === []);
assert_test('Service: list total is 0', $listResult['total'] === 0);
assert_test('Service: list page is 1', $listResult['page'] === 1);
assert_test('Service: list per_page is 15', $listResult['per_page'] === 15);

// list with filters
$filteredResult = $service->list(new TeacherListRequest(['name' => 'Test', 'employee_id' => 'EMP-001', 'page' => '2', 'per_page' => '10']));
assert_test('Service: filtered list page is 2', $filteredResult['page'] === 2);
assert_test('Service: filtered list per_page is 10', $filteredResult['per_page'] === 10);

// find
assert_test('Service: find returns null (no DB)', $service->find(999) === null);

// create with valid data
$createdTeacher = $service->create(new CreateTeacherRequest([
    'employee_id' => 'EMP-SVC-001',
    'first_name' => 'Service',
    'last_name' => 'Test',
    'department' => 'Physics',
]));
assert_test('Service: create returns Teacher', $createdTeacher instanceof Teacher);
assert_test('Service: created teacher has first_name', $createdTeacher->firstName() === 'Service');
assert_test('Service: created teacher has department', $createdTeacher->department() === 'Physics');

// create with invalid data
try {
    $service->create(new CreateTeacherRequest([]));
    assert_test('Service: invalid create throws ValidationException', false);
} catch (ValidationException $e) {
    assert_test('Service: invalid create throws ValidationException', true);
}

// create with invalid email
try {
    $service->create(new CreateTeacherRequest([
        'employee_id' => 'EMP-SVC-002',
        'first_name' => 'Bad',
        'last_name' => 'Email',
        'department' => 'Math',
        'email' => 'not-valid',
    ]));
    assert_test('Service: create with invalid email throws', false);
} catch (ValidationException $e) {
    assert_test('Service: create with invalid email throws', isset($e->errors()['email']));
}

// delete returns false (no DB)
assert_test('Service: delete returns false (no DB)', $service->delete(1) === false);

echo PHP_EOL . "=== Boot + DI + Route Tests ===" . PHP_EOL;

try {
    $kernel = new Kernel($backendDir);
    $kernel->bootstrap();
    $container = $kernel->getContainer();
    echo "[PASS] Kernel bootstrap succeeded" . PHP_EOL;
    $pass++;
} catch (\Throwable $e) {
    echo "[FAIL] Kernel bootstrap: " . $e->getMessage() . PHP_EOL;
    $fail++;
    echo PHP_EOL . "Passed: $pass | Failed: $fail" . PHP_EOL;
    exit(1);
}

$ctrl = $container->get(TeacherController::class);
assert_test('DI: TeacherController resolved', $ctrl instanceof TeacherController);
$svc = $container->get(TeacherServiceInterface::class);
assert_test('DI: TeacherServiceInterface resolved', $svc instanceof TeacherService);
$ctrlAlias = $container->get('teacher.controller');
assert_test('DI: teacher.controller alias resolved', $ctrlAlias instanceof TeacherController);
$svcAlias = $container->get('teacher.service');
assert_test('DI: teacher.service alias resolved', $svcAlias instanceof TeacherService);
$repoAlias = $container->get('teacher.repository');
assert_test('DI: teacher.repository alias resolved', $repoAlias instanceof TeacherRepository);
$validatorAlias = $container->get('teacher.validator');
assert_test('DI: teacher.validator alias resolved', $validatorAlias instanceof TeacherValidator);

// Interface bindings
$repoInterface = $container->get(TeacherRepositoryInterface::class);
assert_test('DI: TeacherRepositoryInterface resolved', $repoInterface instanceof TeacherRepository);

$router = $container->get('router');
if ($router instanceof Router) {
    $health = $router->dispatch('GET', '/health');
    assert_test('Route: GET /health still works', isset($health['success']) && $health['success'] === true);

    $teachersRoute = $router->dispatch('GET', '/teachers');
    assert_test('Route: GET /teachers resolves', ($teachersRoute['status'] ?? 0) !== 404);

    // Regression checks
    $usersRoute = $router->dispatch('GET', '/users');
    assert_test('Regression: GET /users still resolves', ($usersRoute['status'] ?? 0) !== 404);

    $studentsRoute = $router->dispatch('GET', '/students');
    assert_test('Regression: GET /students still resolves', ($studentsRoute['status'] ?? 0) !== 404);

    $auditRoute = $router->dispatch('GET', '/audit-logs');
    assert_test('Regression: GET /audit-logs still resolves', ($auditRoute['status'] ?? 0) !== 404);

    $settingsRoute = $router->dispatch('GET', '/system-settings');
    assert_test('Regression: GET /system-settings still resolves', ($settingsRoute['status'] ?? 0) !== 404);
}

echo PHP_EOL . "=== Summary ===" . PHP_EOL;
echo "Passed: $pass | Failed: $fail" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
