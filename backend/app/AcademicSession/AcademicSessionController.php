<?php

namespace App\AcademicSession;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class AcademicSessionController extends BaseController
{
    public function __construct(private ?AcademicSessionServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof AcademicSessionServiceInterface) {
            return $this->error('Academic session service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new AcademicSessionListRequest($this->query($request)));

            return $this->json([
                'items' => AcademicSessionResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Academic session list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof AcademicSessionServiceInterface) {
            return $this->error('Academic session service unavailable.', 503);
        }

        $item = $this->service->find($id);
        if (!$item instanceof AcademicSession) {
            return $this->error('Academic session not found.', 404);
        }

        return $this->json(AcademicSessionResponse::fromEntity($item));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->service instanceof AcademicSessionServiceInterface) {
            return $this->error('Academic session service unavailable.', 503);
        }

        try {
            $item = $this->service->create(new CreateAcademicSessionRequest($this->payload($request)));

            return $this->json(AcademicSessionResponse::fromEntity($item), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AcademicSessionException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Academic session create request failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->service instanceof AcademicSessionServiceInterface) {
            return $this->error('Academic session service unavailable.', 503);
        }

        try {
            $item = $this->service->update(new UpdateAcademicSessionRequest($id, $this->payload($request)));
            if (!$item instanceof AcademicSession) {
                return $this->error('Academic session not found.', 404);
            }

            return $this->json(AcademicSessionResponse::fromEntity($item));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (AcademicSessionException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Academic session update request failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->service instanceof AcademicSessionServiceInterface) {
            return $this->error('Academic session service unavailable.', 503);
        }

        return $this->service->delete($id)
            ? $this->json(['message' => 'Academic session deleted successfully.'])
            : $this->error('Academic session not found.', 404);
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
