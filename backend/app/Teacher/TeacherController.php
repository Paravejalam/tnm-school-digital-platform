<?php

namespace App\Teacher;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class TeacherController extends BaseController
{
    public function __construct(private ?TeacherServiceInterface $teachers = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->teachers instanceof TeacherServiceInterface) {
            return $this->error('Teacher service unavailable.', 503);
        }

        try {
            $result = $this->teachers->list(new TeacherListRequest($this->query($request)));

            return $this->json([
                'items' => TeacherResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Teacher list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->teachers instanceof TeacherServiceInterface) {
            return $this->error('Teacher service unavailable.', 503);
        }

        $teacher = $this->teachers->find($id);
        if (!$teacher instanceof Teacher) {
            return $this->error('Teacher not found.', 404);
        }

        return $this->json(TeacherResponse::fromTeacher($teacher));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->teachers instanceof TeacherServiceInterface) {
            return $this->error('Teacher service unavailable.', 503);
        }

        try {
            $teacher = $this->teachers->create(new CreateTeacherRequest($this->payload($request)));

            return $this->json(TeacherResponse::fromTeacher($teacher), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (TeacherException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Teacher create request failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->teachers instanceof TeacherServiceInterface) {
            return $this->error('Teacher service unavailable.', 503);
        }

        try {
            $teacher = $this->teachers->update(new UpdateTeacherRequest($id, $this->payload($request)));
            if (!$teacher instanceof Teacher) {
                return $this->error('Teacher not found.', 404);
            }

            return $this->json(TeacherResponse::fromTeacher($teacher));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (TeacherException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Teacher update request failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->teachers instanceof TeacherServiceInterface) {
            return $this->error('Teacher service unavailable.', 503);
        }

        return $this->teachers->delete($id)
            ? $this->json(['message' => 'Teacher deleted successfully.'])
            : $this->error('Teacher not found.', 404);
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