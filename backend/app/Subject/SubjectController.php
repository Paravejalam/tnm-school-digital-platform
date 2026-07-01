<?php

namespace App\Subject;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class SubjectController extends BaseController
{
    public function __construct(private ?SubjectServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof SubjectServiceInterface) {
            return $this->error('Subject service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new SubjectListRequest($this->query($request)));

            return $this->json([
                'items' => SubjectResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Subject list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof SubjectServiceInterface) {
            return $this->error('Subject service unavailable.', 503);
        }

        $item = $this->service->find($id);
        if (!$item instanceof Subject) {
            return $this->error('Subject not found.', 404);
        }

        return $this->json(SubjectResponse::fromEntity($item));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->service instanceof SubjectServiceInterface) {
            return $this->error('Subject service unavailable.', 503);
        }

        try {
            $item = $this->service->create(new CreateSubjectRequest($this->payload($request)));

            return $this->json(SubjectResponse::fromEntity($item), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (SubjectException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Subject create request failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->service instanceof SubjectServiceInterface) {
            return $this->error('Subject service unavailable.', 503);
        }

        try {
            $item = $this->service->update(new UpdateSubjectRequest($id, $this->payload($request)));
            if (!$item instanceof Subject) {
                return $this->error('Subject not found.', 404);
            }

            return $this->json(SubjectResponse::fromEntity($item));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (SubjectException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Subject update request failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->service instanceof SubjectServiceInterface) {
            return $this->error('Subject service unavailable.', 503);
        }

        return $this->service->delete($id)
            ? $this->json(['message' => 'Subject deleted successfully.'])
            : $this->error('Subject not found.', 404);
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
