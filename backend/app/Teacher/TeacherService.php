<?php

namespace App\Teacher;

class TeacherService implements TeacherServiceInterface
{
    public function __construct(
        private TeacherRepositoryInterface $teachers,
        private TeacherValidator $validator
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
}