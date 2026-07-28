<?php

namespace App\Teacher;

use App\Auth\UserRepositoryInterface;
use App\Auth\ValidationException;

class TeacherService implements TeacherServiceInterface
{
    public function __construct(
        private TeacherRepositoryInterface $teachers,
        private TeacherValidator $validator,
        private ?UserRepositoryInterface $userRepository = null
    ) {
    }

    public function list(TeacherListRequest $request): array
    {
        return $this->teachers->paginate(
            $request->page(),
            $request->perPage(),
            $request->name(),
            $request->employeeId()
        );
    }

    public function find(int $id): ?Teacher
    {
        return $this->teachers->findById($id);
    }

    public function create(CreateTeacherRequest $request): Teacher
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);
        $this->validateReferences($payload);

        $existing = $this->teachers->findByEmployeeId((string) $payload['employee_id']);
        if ($existing instanceof Teacher) {
            throw new TeacherException('Employee id is already registered.');
        }

        return $this->teachers->create($payload);
    }

    public function update(UpdateTeacherRequest $request): ?Teacher
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);
        $this->validateReferences($payload);

        if (isset($payload['employee_id'])) {
            $existing = $this->teachers->findByEmployeeId((string) $payload['employee_id']);
            if ($existing instanceof Teacher && $existing->id() !== $request->id()) {
                throw new TeacherException('Employee id is already registered.');
            }
        }

        return $this->teachers->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->teachers->delete($id);
    }

    private function validateReferences(array $payload): void
    {
        if (isset($payload['user_id']) && $this->userRepository instanceof UserRepositoryInterface) {
            $user = $this->userRepository->findById((int) $payload['user_id']);
            if ($user === null) {
                throw new ValidationException(['user_id' => ['User not found.']]);
            }
        }
    }
}
