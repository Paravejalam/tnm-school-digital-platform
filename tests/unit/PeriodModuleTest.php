<?php

declare(strict_types=1);

/**
 * Tests for the Period module.
 * Run: C:\xampp\php\php.exe tests/unit/PeriodModuleTest.php
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

use App\Period\Period;
use App\Period\PeriodController;
use App\Period\PeriodException;
use App\Period\PeriodListRequest;
use App\Period\PeriodRepository;
use App\Period\PeriodRepositoryInterface;
use App\Period\PeriodResponse;
use App\Period\PeriodService;
use App\Period\PeriodServiceInterface;
use App\Period\PeriodValidator;
use App\Period\CreatePeriodRequest;
use App\Period\UpdatePeriodRequest;
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

echo "=== Period Entity Tests ===" . PHP_EOL;

$period = new Period(
    1, 'Period 1', 10, 'active', 'Monday', '08:00', '08:45', 1,
    '2026-01-01', '2026-06-01', null
);

assert_test('Entity: id()', $period->id() === 1);
assert_test('Entity: periodName()', $period->periodName() === 'Period 1');
assert_test('Entity: timetableId()', $period->timetableId() === 10);
assert_test('Entity: status()', $period->status() === 'active');
assert_test('Entity: dayOfWeek()', $period->dayOfWeek() === 'Monday');
assert_test('Entity: startTime()', $period->startTime() === '08:00');
assert_test('Entity: endTime()', $period->endTime() === '08:45');
assert_test('Entity: periodOrder()', $period->periodOrder() === 1);
assert_test('Entity: createdAt()', $period->createdAt() === '2026-01-01');
assert_test('Entity: updatedAt()', $period->updatedAt() === '2026-06-01');
assert_test('Entity: deletedAt()', $period->deletedAt() === null);

// Minimal entity
$minimal = new Period();
assert_test('Entity: minimal id is null', $minimal->id() === null);
assert_test('Entity: minimal periodName is null', $minimal->periodName() === null);
assert_test('Entity: minimal timetableId is null', $minimal->timetableId() === null);
assert_test('Entity: minimal status is null', $minimal->status() === null);
assert_test('Entity: minimal dayOfWeek is null', $minimal->dayOfWeek() === null);
assert_test('Entity: minimal startTime is null', $minimal->startTime() === null);
assert_test('Entity: minimal endTime is null', $minimal->endTime() === null);
assert_test('Entity: minimal periodOrder is null', $minimal->periodOrder() === null);

// Inactive entity
$inactive = new Period(2, 'Break', 10, 'inactive');
assert_test('Entity: inactive status', $inactive->status() === 'inactive');

echo PHP_EOL . "=== PeriodResponse Tests ===" . PHP_EOL;

$response = PeriodResponse::fromEntity($period);
assert_test('Response: has id', $response['id'] === 1);
assert_test('Response: has period_name', $response['period_name'] === 'Period 1');
assert_test('Response: has timetable_id', $response['timetable_id'] === 10);
assert_test('Response: has status', $response['status'] === 'active');

// Fields NOT exposed
assert_test('Response: no day_of_week', !array_key_exists('day_of_week', $response));
assert_test('Response: no start_time', !array_key_exists('start_time', $response));
assert_test('Response: no end_time', !array_key_exists('end_time', $response));
assert_test('Response: no period_order', !array_key_exists('period_order', $response));
assert_test('Response: no created_at', !array_key_exists('created_at', $response));
assert_test('Response: no deleted_at', !array_key_exists('deleted_at', $response));

$period2 = new Period(2, 'Period 2', 10, 'active');
$collection = PeriodResponse::collection([$period, $period2]);
assert_test('Collection: returns array of 2', count($collection) === 2);
assert_test('Collection: first id correct', $collection[0]['id'] === 1);
assert_test('Collection: second id correct', $collection[1]['id'] === 2);
assert_test('Collection: second period_name', $collection[1]['period_name'] === 'Period 2');

echo PHP_EOL . "=== PeriodListRequest Tests ===" . PHP_EOL;

$listReq = new PeriodListRequest(['page' => '3', 'per_page' => '25', 'search' => 'Period']);
assert_test('ListRequest: page()', $listReq->page() === 3);
assert_test('ListRequest: perPage()', $listReq->perPage() === 25);
assert_test('ListRequest: search()', $listReq->search() === 'Period');

$defaultReq = new PeriodListRequest([]);
assert_test('ListRequest: default page', $defaultReq->page() === 1);
assert_test('ListRequest: default perPage', $defaultReq->perPage() === 15);
assert_test('ListRequest: null search', $defaultReq->search() === null);

$cappedReq = new PeriodListRequest(['per_page' => '500']);
assert_test('ListRequest: perPage capped at 100', $cappedReq->perPage() === 100);

$negativeReq = new PeriodListRequest(['page' => '-1', 'per_page' => '0']);
assert_test('ListRequest: negative page clamps to 1', $negativeReq->page() === 1);
assert_test('ListRequest: zero perPage clamps to 1', $negativeReq->perPage() === 1);

// search via 'name' alias
$nameReq = new PeriodListRequest(['name' => 'Break']);
assert_test('ListRequest: name param used as search', $nameReq->search() === 'Break');

// search priority
$bothReq = new PeriodListRequest(['search' => 'Period', 'name' => 'Break']);
assert_test('ListRequest: search param takes priority', $bothReq->search() === 'Period');

// Whitespace returns null
$wsReq = new PeriodListRequest(['search' => '   ']);
assert_test('ListRequest: whitespace search is null', $wsReq->search() === null);

echo PHP_EOL . "=== CreatePeriodRequest Tests ===" . PHP_EOL;

$createReq = new CreatePeriodRequest(['period_name' => 'Period 1', 'timetable_id' => 10]);
assert_test('CreateRequest: payload() period_name', $createReq->payload()['period_name'] === 'Period 1');
assert_test('CreateRequest: payload() timetable_id', $createReq->payload()['timetable_id'] === 10);

$emptyCreateReq = new CreatePeriodRequest([]);
assert_test('CreateRequest: empty payload', $emptyCreateReq->payload() === []);

echo PHP_EOL . "=== UpdatePeriodRequest Tests ===" . PHP_EOL;

$updateReq = new UpdatePeriodRequest(42, ['period_name' => 'Updated']);
assert_test('UpdateRequest: id()', $updateReq->id() === 42);
assert_test('UpdateRequest: payload()', $updateReq->payload()['period_name'] === 'Updated');

$emptyUpdateReq = new UpdatePeriodRequest(10, []);
assert_test('UpdateRequest: empty payload', $emptyUpdateReq->payload() === []);
assert_test('UpdateRequest: id preserved', $emptyUpdateReq->id() === 10);

echo PHP_EOL . "=== PeriodValidator Tests ===" . PHP_EOL;

$validator = new PeriodValidator();

// Valid create
try {
    $validator->validateCreate(['period_name' => 'Period 1', 'timetable_id' => '10']);
    assert_test('Validator: valid create passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: valid create passes', false);
}

// Valid create with status
try {
    $validator->validateCreate(['period_name' => 'Period 1', 'timetable_id' => '10', 'status' => 'active']);
    assert_test('Validator: create with active status passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: create with active status passes', false);
}

try {
    $validator->validateCreate(['period_name' => 'Period 1', 'timetable_id' => '10', 'status' => 'inactive']);
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
    assert_test('Validator: missing period_name error', isset($errors['period_name']));
    assert_test('Validator: missing timetable_id error', isset($errors['timetable_id']));
}

// Empty period_name
try {
    $validator->validateCreate(['period_name' => '   ', 'timetable_id' => '10']);
    assert_test('Validator: empty period_name throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: empty period_name throws', isset($e->errors()['period_name']));
}

// Invalid status in create
try {
    $validator->validateCreate(['period_name' => 'Period 1', 'timetable_id' => '10', 'status' => 'archived']);
    assert_test('Validator: invalid status in create throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: invalid status in create throws', isset($e->errors()['status']));
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

// Empty period_name in update
try {
    $validator->validateUpdate(['period_name' => '  ']);
    assert_test('Validator: empty period_name in update throws', false);
} catch (ValidationException $e) {
    assert_test('Validator: empty period_name in update throws', isset($e->errors()['period_name']));
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
    $validator->validateUpdate(['period_name' => '', 'status' => 'unknown']);
    assert_test('Validator: multiple update errors throws', false);
} catch (ValidationException $e) {
    $errors = $e->errors();
    assert_test('Validator: multiple errors has period_name', isset($errors['period_name']));
    assert_test('Validator: multiple errors has status', isset($errors['status']));
}

// Valid period_name in update
try {
    $validator->validateUpdate(['period_name' => 'Lunch Break']);
    assert_test('Validator: valid period_name in update passes', true);
} catch (ValidationException $e) {
    assert_test('Validator: valid period_name in update passes', false);
}

echo PHP_EOL . "=== PeriodException Tests ===" . PHP_EOL;

$exception = new PeriodException('Period already exists.');
assert_test('Exception: message', $exception->getMessage() === 'Period already exists.');
assert_test('Exception: extends RuntimeException', $exception instanceof \RuntimeException);

$exception2 = new PeriodException('Conflict', 409);
assert_test('Exception: custom code', $exception2->getCode() === 409);

echo PHP_EOL . "=== PeriodRepository (no DB) Tests ===" . PHP_EOL;

$repo = new PeriodRepository(null);
assert_test('Repo: implements interface', $repo instanceof PeriodRepositoryInterface);

$paginated = $repo->paginate(1, 15);
assert_test('Repo: paginate returns empty items', $paginated['items'] === []);
assert_test('Repo: paginate returns total 0', $paginated['total'] === 0);
assert_test('Repo: paginate preserves page', $paginated['page'] === 1);
assert_test('Repo: paginate preserves per_page', $paginated['per_page'] === 15);

$paginated2 = $repo->paginate(3, 25, 'Period');
assert_test('Repo: paginate with search preserves page', $paginated2['page'] === 3);
assert_test('Repo: paginate with search preserves per_page', $paginated2['per_page'] === 25);

assert_test('Repo: findById returns null', $repo->findById(1) === null);
assert_test('Repo: findByName returns null', $repo->findByName('Period 1') === null);
assert_test('Repo: delete returns false', $repo->delete(1) === false);

// create without DB returns entity from attributes
$created = $repo->create([
    'period_name' => 'Period 1',
    'timetable_id' => 10,
    'day_of_week' => 'Monday',
    'start_time' => '08:00',
    'end_time' => '08:45',
    'period_order' => 1,
]);
assert_test('Repo: create without DB returns Period', $created instanceof Period);
assert_test('Repo: created has period_name', $created->periodName() === 'Period 1');
assert_test('Repo: created has timetable_id', $created->timetableId() === 10);
assert_test('Repo: created has day_of_week', $created->dayOfWeek() === 'Monday');
assert_test('Repo: created has start_time', $created->startTime() === '08:00');
assert_test('Repo: created has end_time', $created->endTime() === '08:45');
assert_test('Repo: created has period_order', $created->periodOrder() === 1);

// create without DB defaults status to active
$createdDefault = $repo->create(['period_name' => 'Break']);
assert_test('Repo: created default status is active', $createdDefault->status() === 'active');

// update without DB returns null (findById returns null)
$updated = $repo->update(1, ['period_name' => 'Updated']);
assert_test('Repo: update without DB returns null', $updated === null);

echo PHP_EOL . "=== PeriodService (no DB) Tests ===" . PHP_EOL;

$service = new PeriodService($repo, $validator);
assert_test('Service: implements interface', $service instanceof PeriodServiceInterface);

// list
$listResult = $service->list(new PeriodListRequest([]));
assert_test('Service: list returns paginated result', $listResult['items'] === []);
assert_test('Service: list total is 0', $listResult['total'] === 0);
assert_test('Service: list page is 1', $listResult['page'] === 1);
assert_test('Service: list per_page is 15', $listResult['per_page'] === 15);

// list with search
$searchResult = $service->list(new PeriodListRequest(['search' => 'Period', 'page' => '2', 'per_page' => '10']));
assert_test('Service: search list page is 2', $searchResult['page'] === 2);
assert_test('Service: search list per_page is 10', $searchResult['per_page'] === 10);

// find
assert_test('Service: find returns null (no DB)', $service->find(999) === null);

// create with valid data
$createdPeriod = $service->create(new CreatePeriodRequest([
    'period_name' => 'Period 1',
    'timetable_id' => '10',
]));
assert_test('Service: create returns Period', $createdPeriod instanceof Period);
assert_test('Service: created has period_name', $createdPeriod->periodName() === 'Period 1');

// create with invalid data
try {
    $service->create(new CreatePeriodRequest([]));
    assert_test('Service: invalid create throws ValidationException', false);
} catch (ValidationException $e) {
    assert_test('Service: invalid create throws ValidationException', true);
}

// create with invalid status
try {
    $service->create(new CreatePeriodRequest([
        'period_name' => 'Period 2',
        'timetable_id' => '10',
        'status' => 'bogus',
    ]));
    assert_test('Service: create with invalid status throws', false);
} catch (ValidationException $e) {
    assert_test('Service: create with invalid status throws', isset($e->errors()['status']));
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

$ctrl = $container->get(PeriodController::class);
assert_test('DI: PeriodController resolved', $ctrl instanceof PeriodController);
$svc = $container->get(PeriodServiceInterface::class);
assert_test('DI: PeriodServiceInterface resolved', $svc instanceof PeriodService);
$ctrlAlias = $container->get('period.controller');
assert_test('DI: period.controller alias resolved', $ctrlAlias instanceof PeriodController);
$svcAlias = $container->get('period.service');
assert_test('DI: period.service alias resolved', $svcAlias instanceof PeriodService);
$repoAlias = $container->get('period.repository');
assert_test('DI: period.repository alias resolved', $repoAlias instanceof PeriodRepository);
$validatorAlias = $container->get('period.validator');
assert_test('DI: period.validator alias resolved', $validatorAlias instanceof PeriodValidator);

$repoInterface = $container->get(PeriodRepositoryInterface::class);
assert_test('DI: PeriodRepositoryInterface resolved', $repoInterface instanceof PeriodRepository);

$router = $container->get('router');
if ($router instanceof Router) {
    $health = $router->dispatch('GET', '/health');
    assert_test('Route: GET /health still works', isset($health['success']) && $health['success'] === true);

    $periodsRoute = $router->dispatch('GET', '/periods');
    assert_test('Route: GET /periods resolves', ($periodsRoute['status'] ?? 0) !== 404);

    // Regression checks
    $usersRoute = $router->dispatch('GET', '/users');
    assert_test('Regression: GET /users still resolves', ($usersRoute['status'] ?? 0) !== 404);

    $studentsRoute = $router->dispatch('GET', '/students');
    assert_test('Regression: GET /students still resolves', ($studentsRoute['status'] ?? 0) !== 404);

    $teachersRoute = $router->dispatch('GET', '/teachers');
    assert_test('Regression: GET /teachers still resolves', ($teachersRoute['status'] ?? 0) !== 404);

    $timetablesRoute = $router->dispatch('GET', '/timetables');
    assert_test('Regression: GET /timetables still resolves', ($timetablesRoute['status'] ?? 0) !== 404);

    $auditRoute = $router->dispatch('GET', '/audit-logs');
    assert_test('Regression: GET /audit-logs still resolves', ($auditRoute['status'] ?? 0) !== 404);

    $settingsRoute = $router->dispatch('GET', '/settings');
    assert_test('Regression: GET /settings still resolves', ($settingsRoute['status'] ?? 0) !== 404);
}

echo PHP_EOL . "=== Summary ===" . PHP_EOL;
echo "Passed: $pass | Failed: $fail" . PHP_EOL;
exit($fail > 0 ? 1 : 0);
