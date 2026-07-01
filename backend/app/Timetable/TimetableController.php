<?php

namespace App\Timetable;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class TimetableController extends BaseController
{
    public function __construct(private ?TimetableServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof TimetableServiceInterface) {
            return $this->error('Timetable service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new TimetableListRequest($this->query($request)));

            return $this->json([
                'items' => TimetableResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Timetable list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof TimetableServiceInterface) {
            return $this->error('Timetable service unavailable.', 503);
        }

        $item = $this->service->find($id);
        if (!$item instanceof Timetable) {
            return $this->error('Timetable not found.', 404);
        }

        return $this->json(TimetableResponse::fromEntity($item));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->service instanceof TimetableServiceInterface) {
            return $this->error('Timetable service unavailable.', 503);
        }

        try {
            $item = $this->service->create(new CreateTimetableRequest($this->payload($request)));

            return $this->json(TimetableResponse::fromEntity($item), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (TimetableException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Timetable create request failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->service instanceof TimetableServiceInterface) {
            return $this->error('Timetable service unavailable.', 503);
        }

        try {
            $item = $this->service->update(new UpdateTimetableRequest($id, $this->payload($request)));
            if (!$item instanceof Timetable) {
                return $this->error('Timetable not found.', 404);
            }

            return $this->json(TimetableResponse::fromEntity($item));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (TimetableException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Timetable update request failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->service instanceof TimetableServiceInterface) {
            return $this->error('Timetable service unavailable.', 503);
        }

        return $this->service->delete($id)
            ? $this->json(['message' => 'Timetable deleted successfully.'])
            : $this->error('Timetable not found.', 404);
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
