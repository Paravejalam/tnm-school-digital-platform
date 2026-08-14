<?php

declare(strict_types=1);

/**
 * Tests for the HolidayCalendar module.
 * Run: C:\xampp\php\php.exe tests/unit/HolidayCalendarModuleTest.php
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

use App\AcademicSession\AcademicSession;
use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\HolidayCalendar\HolidayCalendar;
use App\HolidayCalendar\HolidayCalendarController;
use App\HolidayCalendar\HolidayCalendarException;
use App\HolidayCalendar\HolidayCalendarListRequest;
use App\HolidayCalendar\HolidayCalendarRepository;
use App\HolidayCalendar\HolidayCalendarRepositoryInterface;
use App\HolidayCalendar\HolidayCalendarResponse;
use App\HolidayCalendar\HolidayCalendarService;
use App\HolidayCalendar\HolidayCalendarServiceInterface;
use App\HolidayCalendar\HolidayCalendarValidator;
use App\HolidayCalendar\CreateHolidayCalendarRequest;
use App\HolidayCalendar\UpdateHolidayCalendarRequest;
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

echo "=== HolidayCalendar Entity Tests ===" . PHP_EOL;

$holiday = new HolidayCalendar(
    1, 'Republic Day', 5, 'active', '2026-01-26', 'national', 1,
    'National holiday celebrating the republic.', '2026-01-01', '2026-01-15', null
);

assert_test('Entity: id()', $holiday->id() === 1);
assert_test('Entity: holidayName()', $holiday->holidayName() === 'Republic Day');
assert_test('Entity: academicSessionId()', $holiday->academicSessionId() === 5);
assert_test('Entity: status()', $holiday->status() === 'active');
assert_test('Entity: holidayDate()', $holiday->holidayDate() === '2026-01-26');
assert_test('Entity: holidayType()', $holiday->holidayType() === 'national');
assert_test('Entity: isRecurring()', $holiday->isRecurring() === 1);
assert_test('Entity: description()', $holiday->description() === 'National holiday celebrating the republic.');
assert_test('Entity: createdAt()', $holiday->createdAt() === '2026-01-01');
assert_test('Entity: updatedAt()', $holiday->updatedAt() === '2026-01-15');
assert_test('Entity: deletedAt()', $holiday->deletedAt() === null);

// Minimal entity
$minimal = new HolidayCalendar();
assert_test('Entity: minimal id is null', $minimal->id() === null);
assert_test('Entity: minimal holidayName is null', $minimal->holidayName() === null);
assert_test('Entity: minimal academicSessionId is null', $minimal->academicSessionId() === null);
assert_test('Entity: minimal status is null', $minimal->status() === null);
assert_test('Entity: minimal holidayDate is null', $minimal->holidayDate() === null);
assert_test('Entity: minimal holidayType is null', $minimal->holidayType() === null);
assert_test('Entity: minimal isRecurring is null', $minimal->isRecurring() === null);
assert_test('Entity: minimal description is null', $minimal->description() === null);

// Inactive entity
$inactive = new HolidayCalendar(2, 'Old Holiday', 5, 'inactive');
assert_test('Entity: inactive status', $inactive->status() === 'inactive');

// Non-recurring entity
$nonRecurring = new HolidayCalendar(3, 'Special Day', 5, 'active', '2026-03-15', 'school', 0);
assert_test('Entity: isRecurring 0', $nonRecurring->isRecurring() === 0);

// Entity with deletedAt
$deleted = new HolidayCalendar(4, 'Removed', 5, 'inactive', null, null, null, null, null, null, '2026-06-01');
assert_test('Entity: deletedAt set', $deleted->deletedAt() === '2026-06-01');

echo PHP_EOL . "=== HolidayCalendarResponse Tests ===" . PHP_EOL;

$response = HolidayCalendarResponse::fromEntity($holiday);
assert_test('Response: has id', $response['id'] === 1);
assert_test('Response: has holiday_name', $response['holiday_name'] === 'Republic Day');
assert_test('Response: has academic_session_id', $response['academic_session_id'] === 5);
assert_test('Response: has status', $response['status'] === 'active');

// Fields NOT exposed in response
assert_test('Response: no holiday_date', !array_key_exists('holiday_date', $response));
assert_test('Response: no holiday_type', !array_key_exists('holiday_type', $response));
assert_test('Response: no is_recurring', !array_key_exists('is_recurring', $response));
assert_test('Response: no description', !array_key_exists('description', $response));
assert_test('Response: no created_at', !array_key_exists('created_at', $response));
assert_test('Response: no updated_at', !array_key_exists('updated_at', $response));
assert_test('Response: no deleted_at', !array_key_exists('deleted_at', $response));

$holiday2 = new HolidayCalendar(2, 'Independence Day', 5, 'active');
$collection = HolidayCalendarResponse::collection([$holiday, $holiday2]);
assert_test('Collection: returns array of 2', count($collection) === 2);
assert_test('Collection: first id correct', $collection[0]['id'] === 1);
assert_test('Collection: second id correct', $collection[1]['id'] === 2);
assert_test('Collection: second holiday_name', $collection[1]['holiday_name'] === 'Independence Day');
assert_test('Collection: field count per item', count($collection[0]) === 4);

echo PHP_EOL . "=== HolidayCalendarListRequest Tests ===" . PHP_EOL;

$listReq = new HolidayCalendarListRequest(['page' => '3', 'per_page' => '25', 'search' => 'Republic']);
assert_test('ListRequest: page()', $listReq->page() === 3);
assert_test('ListRequest: perPage()', $listReq->perPage() === 25);
assert_test('ListRequest: search()', $listReq->search() === 'Republic');

$defaultReq = new HolidayCalendarListRequest([]);
assert_test('ListRequest: default page', $defaultReq->page() === 1);
assert_test('ListRequest: default perPage', $defaultReq->perPage() === 15);
assert_test('ListRequest: null search', $defaultReq->search() === null);

$cappedReq = new HolidayCalendarListRequest(['per_page' => '500']);
assert_test('ListRequest: perPage capped at 100', $cappedReq->perPage() === 100);

$negativeReq = new HolidayCalendarListRequest(['page' => '-1', 'per_page' => '0']);
assert_test('ListRequest: negative page clamps to 1', $negativeReq->page() === 1);
assert_test('ListRequest: zero perPage clamps to 1', $negativeReq->perPage() === 1);

// search via 'name' alias
$nameReq = new HolidayCalendarListRequest(['name' => 'Diwali']);
assert_test('ListRequest: name param used as search', $nameReq->search() === 'Diwali');

// search priority
$bothReq = new HolidayCalendarListRequest(['search' => 'Republic', 'name' => 'Diwali']);
assert_test('ListRequest: search param takes priority', $bothReq->search() === 'Republic');

// Whitespace returns null
$wsReq = new HolidayCalendarListRequest(['search' => '   ']);
assert_test('ListRequest: whitespace search is null', $wsReq->search() === null);

echo PHP_EOL . "=== CreateHolidayCalendarRequest Tests ===" . PHP_EOL;

$createReq = new CreateHolidayCalendarRequest([
    'holiday_name' => 'Republic Day',
    'academic_session_id' => 5,
    'holiday_date' => '2026-01-26',
]);
assert_test('CreateRequest: payload() holiday_name', $createReq->payload()['holiday_name'] === 'Republic Day');
assert_test('CreateRequest: payload() academic_session_id', $createReq->payload()['academic_session_id'] === 5);
assert_test('CreateRequest: payload() holiday_date', $createReq->payload()['holiday_date'] === '2026-01-26');

$emptyCreateReq = new CreateHolidayCalendarRequest([]);
assert_test('CreateRequest: empty payload', $emptyCreateReq->payload() === []);

echo PHP_EOL . "=== UpdateHolidayCalendarRequest Tests ===" . PHP_EOL;

$updateReq = new UpdateHolidayCalendarRequest(42, ['holiday_name' => 'Updated Holiday']);
assert_test('UpdateRequest: id()', $updateReq->id() === 42);
assert_test('UpdateRequest: payload()', $updateReq->payload()['holiday_name'] === 'Updated Holiday');

$emptyUpdateReq = new UpdateHolidayCalendarRequest(10, []);
assert_test('UpdateRequest: empty payload', $emptyUpdateReq->payload() === []);
assert_test('UpdateRequest: id preserved', $emptyUpdateReq->id() === 10);

echo PHP_EOL . "=== HolidayCalendarValidator Tests ===" . PHP_EOL;

$validator = new HolidayCalendarValidator();

// Valid create
try {
    $validator->validateCreate(['holiday_name' => 'Republic Day', 'academic_session_id' => '5']);
    assert_test('Validator: valid create passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: valid create passes', false);
}

// Valid create with status
try {
    $validator->validateCreate(['holiday_name' => 'Republic Day', 'academic_session_id' => '5', 'status' => 'active']);
    assert_test('Validator: create with active status passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: create with active status passes', false);
}

try {
    $validator->validateCreate(['holiday_name' => 'Republic Day', 'academic_session_id' => '5', 'status' => 'inactive']);
    assert_test('Validator: create with inactive status passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: create with inactive status passes', false);
}

// Missing required fields
try {
    $validator->validateCreate([]);
    assert_test('Validator: missing required throws', false);
} catch (ValidationException $e) {
    $errors = $e->errors();
    assert_test('Validator: missing holiday_name error', isset($errors['holiday_name']));
    assert_test('Validator: missing academic_session_id error', isset($errors['academic_session_id']));
}

// Empty holiday_name
try {
    $validator->validateCreate(['holiday_name' => '   ', 'academic_session_id' => '5']);
    assert_test('Validator: empty holiday_name throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: empty holiday_name throws', isset($e->errors()['holiday_name']));
}

// Invalid status in create
try {
    $validator->validateCreate(['holiday_name' => 'Republic Day', 'academic_session_id' => '5', 'status' => 'cancelled']);
    assert_test('Validator: invalid status in create throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: invalid status in create throws', isset($e->errors()['status']));
}

// Missing academic_session_id only
try {
    $validator->validateCreate(['holiday_name' => 'Republic Day']);
    assert_test('Validator: missing academic_session_id only throws', false);
} catch (ValidationException $e) {
    $errors = $e->errors();
    assert_test('Validator: missing academic_session_id only throws', isset($errors['academic_session_id']));
    assert_test('Validator: holiday_name not in errors when present', !isset($errors['holiday_name']));
}

// === Update validation ===

// Empty update passes
try {
    $validator->validateUpdate([]);
    assert_test('Validator: empty update passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: empty update passes', false);
}

// Partial update with valid status
try {
    $validator->validateUpdate(['status' => 'inactive']);
    assert_test('Validator: partial update with valid status passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: partial update with valid status passes', false);
}

// Empty holiday_name in update
try {
    $validator->validateUpdate(['holiday_name' => '  ']);
    assert_test('Validator: empty holiday_name in update throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: empty holiday_name in update throws', isset($e->errors()['holiday_name']));
}

// Invalid status in update
try {
    $validator->validateUpdate(['status' => 'deleted']);
    assert_test('Validator: invalid status in update throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: invalid status in update throws', isset($e->errors()['status']));
}

// Multiple errors in update
try {
    $validator->validateUpdate(['holiday_name' => '', 'status' => 'unknown']);
    assert_test('Validator: multiple update errors throws', false);
} catch (ValidationException $e) {
    $errors = $e->errors();
    assert_test('Validator: multiple errors has holiday_name', isset($errors['holiday_name']));
    assert_test('Validator: multiple errors has status', isset($errors['status']));
}

// Valid holiday_name in update
try {
    $validator->validateUpdate(['holiday_name' => 'Gandhi Jayanti']);
    assert_test('Validator: valid holiday_name in update passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: valid holiday_name in update passes', false);
}

echo PHP_EOL . "=== HolidayCalendarException Tests ===" . PHP_EOL;

$exception = new HolidayCalendarException('Holiday calendar already exists.');
assert_test('Exception: message', $exception->getMessage() === 'Holiday calendar already exists.');
assert_test('Exception: extends RuntimeException', $exception instanceof \RuntimeException);

$exception2 = new HolidayCalendarException('Conflict', 409);
assert_test('Exception: custom code', $exception2->getCode() === 409);

echo PHP_EOL . "=== HolidayCalendarRepository (no DB) Tests ===" . PHP_EOL;

$repo = new HolidayCalendarRepository(null);
assert_test('Repo: implements interface', $repo instanceof HolidayCalendarRepositoryInterface);

$paginated = $repo->paginate(1, 15);
assert_test('Repo: paginate returns empty items', $paginated['items'] === []);
assert_test('Repo: paginate returns total 0', $paginated['total'] === 0);
assert_test('Repo: paginate preserves page', $paginated['page'] === 1);
assert_test('Repo: paginate preserves per_page', $paginated['per_page'] === 15);

$paginated2 = $repo->paginate(3, 25, 'Republic');
assert_test('Repo: paginate with search preserves page', $paginated2['page'] === 3);
assert_test('Repo: paginate with search preserves per_page', $paginated2['per_page'] === 25);

assert_test('Repo: findById returns null', $repo->findById(1) === null);
assert_test('Repo: findByName returns null', $repo->findByName('Republic Day') === null);
assert_test('Repo: delete returns false', $repo->delete(1) === false);

// create without DB returns entity from attributes
$created = $repo->create([
    'holiday_name' => 'Republic Day',
    'academic_session_id' => 5,
    'holiday_date' => '2026-01-26',
    'holiday_type' => 'national',
    'is_recurring' => 1,
    'description' => 'National holiday.',
]);
assert_test('Repo: create without DB returns HolidayCalendar', $created instanceof HolidayCalendar);
assert_test('Repo: created has holiday_name', $created->holidayName() === 'Republic Day');
assert_test('Repo: created has academic_session_id', $created->academicSessionId() === 5);
assert_test('Repo: created has holiday_date', $created->holidayDate() === '2026-01-26');
assert_test('Repo: created has holiday_type', $created->holidayType() === 'national');
assert_test('Repo: created has is_recurring', $created->isRecurring() === 1);
assert_test('Repo: created has description', $created->description() === 'National holiday.');

// create without DB defaults status to active
$createdDefault = $repo->create(['holiday_name' => 'Diwali']);
assert_test('Repo: created default status is active', $createdDefault->status() === 'active');

// update without DB returns null (findById returns null)
$updated = $repo->update(1, ['holiday_name' => 'Updated']);
assert_test('Repo: update without DB returns null', $updated === null);

echo PHP_EOL . "=== HolidayCalendarService (no DB) Tests ===" . PHP_EOL;

$service = new HolidayCalendarService($repo, $validator);
assert_test('Service: implements interface', $service instanceof HolidayCalendarServiceInterface);

// list
$listResult = $service->list(new HolidayCalendarListRequest([]));
assert_test('Service: list returns paginated result', $listResult['items'] === []);
assert_test('Service: list total is 0', $listResult['total'] === 0);
assert_test('Service: list page is 1', $listResult['page'] === 1);
assert_test('Service: list per_page is 15', $listResult['per_page'] === 15);

// list with search
$searchResult = $service->list(new HolidayCalendarListRequest(['search' => 'Republic', 'page' => '2', 'per_page' => '10']));
assert_test('Service: search list page is 2', $searchResult['page'] === 2);
assert_test('Service: search list per_page is 10', $searchResult['per_page'] === 10);

// find
assert_test('Service: find returns null (no DB)', $service->find(999) === null);

// create with valid data
$createdHoliday = $service->create(new CreateHolidayCalendarRequest([
    'holiday_name' => 'Republic Day',
    'academic_session_id' => '5',
]));
assert_test('Service: create returns HolidayCalendar', $createdHoliday instanceof HolidayCalendar);
assert_test('Service: created has holiday_name', $createdHoliday->holidayName() === 'Republic Day');

// create with invalid data
try {
    $service->create(new CreateHolidayCalendarRequest([]));
    assert_test('Service: invalid create throws ValidationException', false);
} catch (ValidationException $e) {
    assert_test('Service: invalid create throws ValidationException', true);
}

// create with invalid status
try {
    $service->create(new CreateHolidayCalendarRequest([
        'holiday_name' => 'Holi',
        'academic_session_id' => '5',
        'status' => 'bogus',
    ]));
    assert_test('Service: create with invalid status throws', false);
} catch (ValidationException $e) {
    assert_test('Service: create with invalid status throws', isset($e->errors()['status']));
}

$missingSessionRepo = new class implements AcademicSessionRepositoryInterface {
    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
    }

    public function findById(int $id): ?AcademicSession
    {
        return null;
    }

    public function findByName(string $name): ?AcademicSession
    {
        return null;
    }

    public function create(array $attributes): AcademicSession
    {
        return new AcademicSession(1, $attributes['session_name'] ?? 'Session', 'active');
    }

    public function update(int $id, array $attributes): ?AcademicSession
    {
        return null;
    }

    public function delete(int $id): bool
    {
        return false;
    }
};

$serviceWithMissingSession = new HolidayCalendarService($repo, $validator, $missingSessionRepo);
try {
    $serviceWithMissingSession->create(new CreateHolidayCalendarRequest([
        'holiday_name' => 'Summer Break',
        'academic_session_id' => '99',
    ]));
    assert_test('Service: missing academic session reference throws ValidationException', false);
} catch (ValidationException $e) {
    $errors = $e->errors();
    assert_test('Service: missing academic_session_id error present', isset($errors['academic_session_id']));
    assert_test('Service: missing academic session message matches', ($errors['academic_session_id'][0] ?? null) === 'Academic session not found.');
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

$ctrl = $container->get(HolidayCalendarController::class);
assert_test('DI: HolidayCalendarController resolved', $ctrl instanceof HolidayCalendarController);
$svc = $container->get(HolidayCalendarServiceInterface::class);
assert_test('DI: HolidayCalendarServiceInterface resolved', $svc instanceof HolidayCalendarService);
$ctrlAlias = $container->get('holidaycalendar.controller');
assert_test('DI: holidaycalendar.controller alias resolved', $ctrlAlias instanceof HolidayCalendarController);
$svcAlias = $container->get('holidaycalendar.service');
assert_test('DI: holidaycalendar.service alias resolved', $svcAlias instanceof HolidayCalendarService);
$repoAlias = $container->get('holidaycalendar.repository');
assert_test('DI: holidaycalendar.repository alias resolved', $repoAlias instanceof HolidayCalendarRepository);
$validatorAlias = $container->get('holidaycalendar.validator');
assert_test('DI: holidaycalendar.validator alias resolved', $validatorAlias instanceof HolidayCalendarValidator);

$repoInterface = $container->get(HolidayCalendarRepositoryInterface::class);
assert_test('DI: HolidayCalendarRepositoryInterface resolved', $repoInterface instanceof HolidayCalendarRepository);

$router = $container->get('router');
if ($router instanceof Router) {
    $health = $router->dispatch('GET', '/health');
    assert_test('Route: GET /health still works', isset($health['success']) && $health['success'] === true);

    $holidaysRoute = $router->dispatch('GET', '/holiday-calendars');
    assert_test('Route: GET /holiday-calendars resolves', ($holidaysRoute['status'] ?? 0) !== 404);

    // Regression checks
    $usersRoute = $router->dispatch('GET', '/users');
    assert_test('Regression: GET /users still resolves', ($usersRoute['status'] ?? 0) !== 404);

    $studentsRoute = $router->dispatch('GET', '/students');
    assert_test('Regression: GET /students still resolves', ($studentsRoute['status'] ?? 0) !== 404);

    $teachersRoute = $router->dispatch('GET', '/teachers');
    assert_test('Regression: GET /teachers still resolves', ($teachersRoute['status'] ?? 0) !== 404);

    $timetablesRoute = $router->dispatch('GET', '/timetables');
    assert_test('Regression: GET /timetables still resolves', ($timetablesRoute['status'] ?? 0) !== 404);

    $periodsRoute = $router->dispatch('GET', '/periods');
    assert_test('Regression: GET /periods still resolves', ($periodsRoute['status'] ?? 0) !== 404);

    $auditRoute = $router->dispatch('GET', '/audit-logs');
    assert_test('Regression: GET /audit-logs still resolves', ($auditRoute['status'] ?? 0) !== 404);

    $settingsRoute = $router->dispatch('GET', '/settings');
    assert_test('Regression: GET /settings still resolves', ($settingsRoute['status'] ?? 0) !== 404);
}

echo PHP_EOL . "=== Summary ===" . PHP_EOL;
echo "Passed: $pass | Failed: $fail" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
