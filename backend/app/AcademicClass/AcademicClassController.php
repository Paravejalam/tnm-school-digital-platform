<?php

namespace App\AcademicClass;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class AcademicClassController extends BaseController
{
    public function __construct(private ?AcademicClassServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof AcademicClassServiceInterface) {
            return $this->error('Class service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new AcademicClassListRequest($this->query($request)));

            return $this->json([
                'items' => AcademicClassResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Class list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof AcademicClassServiceInterface) {
            return $this->error('Class service unavailable.', 503);
        }

        $item = $this->service->find($id);
        if (!$item instanceof AcademicClass) {
            return $this->error('Class not found.', 404);
        }

        return $this->json(AcademicClassResponse::fromEntity($item));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->service instanceof AcademicClassServiceInterface) {
            return $this->error('Class service unavailable.', 503);
        }

        try {
            $item = $this->service->create(new CreateAcademicClassRequest($this->payload($request)));

            return $this->json(AcademicClassResponse::fromEntity($item), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AcademicClassException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Class create request failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->service instanceof AcademicClassServiceInterface) {
            return $this->error('Class service unavailable.', 503);
        }

        try {
            $item = $this->service->update(new UpdateAcademicClassRequest($id, $this->payload($request)));
            if (!$item instanceof AcademicClass) {
                return $this->error('Class not found.', 404);
            }

            return $this->json(AcademicClassResponse::fromEntity($item));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AcademicClassException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Class update request failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->service instanceof AcademicClassServiceInterface) {
            return $this->error('Class service unavailable.', 503);
        }

        return $this->service->delete($id)
            ? $this->json(['message' => 'Class deleted successfully.'])
            : $this->error('Class not found.', 404);
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
