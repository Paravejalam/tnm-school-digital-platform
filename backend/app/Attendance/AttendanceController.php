<?php

namespace App\Attendance;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class AttendanceController extends BaseController
{
    public function __construct(private ?AttendanceServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof AttendanceServiceInterface) {
            return $this->error('Attendance service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new AttendanceListRequest($this->query($request)));

            return $this->json([
                'items' => AttendanceResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Attendance list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof AttendanceServiceInterface) {
            return $this->error('Attendance service unavailable.', 503);
        }

        $item = $this->service->find($id);
        if (!$item instanceof Attendance) {
            return $this->error('Attendance not found.', 404);
        }

        return $this->json(AttendanceResponse::fromEntity($item));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->service instanceof AttendanceServiceInterface) {
            return $this->error('Attendance service unavailable.', 503);
        }

        try {
            $item = $this->service->create(new CreateAttendanceRequest($this->payload($request)));

            return $this->json(AttendanceResponse::fromEntity($item), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AttendanceException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Attendance create request failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->service instanceof AttendanceServiceInterface) {
            return $this->error('Attendance service unavailable.', 503);
        }

        try {
            $item = $this->service->update(new UpdateAttendanceRequest($id, $this->payload($request)));
            if (!$item instanceof Attendance) {
                return $this->error('Attendance not found.', 404);
            }

            return $this->json(AttendanceResponse::fromEntity($item));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AttendanceException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Attendance update request failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->service instanceof AttendanceServiceInterface) {
            return $this->error('Attendance service unavailable.', 503);
        }

        return $this->service->delete($id)
            ? $this->json(['message' => 'Attendance deleted successfully.'])
            : $this->error('Attendance not found.', 404);
    }

    private function payload(RequestHelper $request): array
    {
        $json = $request->json();
        if (is_array($json)) {
            return $json;
        }

        $requestData = $request->all();

        return is_array($requestData['body'] ?? null) ? $requestData['body'] : [];
    }

    private function query(RequestHelper $request): array
    {
        $requestData = $request->all();

        return is_array($requestData['query'] ?? null) ? $requestData['query'] : [];
    }
}
