<?php

namespace App\Student;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class StudentController extends BaseController
{
    public function __construct(private ?StudentServiceInterface $students = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->students instanceof StudentServiceInterface) {
            return $this->error('Student service unavailable.', 503);
        }

        try {
            $result = $this->students->list(new StudentListRequest($this->query($request)));

            return $this->json([
                'items' => StudentResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Student list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->students instanceof StudentServiceInterface) {
            return $this->error('Student service unavailable.', 503);
        }

        $student = $this->students->find($id);
        if (!$student instanceof Student) {
            return $this->error('Student not found.', 404);
        }

        return $this->json(StudentResponse::fromStudent($student));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->students instanceof StudentServiceInterface) {
            return $this->error('Student service unavailable.', 503);
        }

        try {
            $student = $this->students->create(new CreateStudentRequest($this->payload($request)));

            return $this->json(StudentResponse::fromStudent($student), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (StudentException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Student create request failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->students instanceof StudentServiceInterface) {
            return $this->error('Student service unavailable.', 503);
        }

        try {
            $student = $this->students->update(new UpdateStudentRequest($id, $this->payload($request)));
            if (!$student instanceof Student) {
                return $this->error('Student not found.', 404);
            }

            return $this->json(StudentResponse::fromStudent($student));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (StudentException $exception) {
            return $this->error($exception->getMessage(), 409);
        } catch (Throwable) {
            return $this->error('Student update request failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->students instanceof StudentServiceInterface) {
            return $this->error('Student service unavailable.', 503);
        }

        return $this->students->delete($id)
            ? $this->json(['message' => 'Student deleted successfully.'])
            : $this->error('Student not found.', 404);
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