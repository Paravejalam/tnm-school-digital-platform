<?php

namespace App\Student;


class StudentService implements StudentServiceInterface
{
    public function __construct(
        private StudentRepositoryInterface $students,
        private StudentValidator $validator
    ) {
    }

    public function list(StudentListRequest $request): array
    {
        return $this->students->paginate(
            $request->page(),
            $request->perPage(),
            $request->name(),
            $request->admissionNumber()
        );
    }

    public function find(int $id): ?Student
    {
        return $this->students->findById($id);
    }

    public function create(CreateStudentRequest $request): Student
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->students->findByAdmissionNumber((string) $payload['admission_number']);
        if ($existing instanceof Student) {
            throw new StudentException('Admission number is already registered.');
        }

        return $this->students->create($payload);
    }

    public function update(UpdateStudentRequest $request): ?Student
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['admission_number'])) {
            $existing = $this->students->findByAdmissionNumber((string) $payload['admission_number']);
            if ($existing instanceof Student && $existing->id() !== $request->id()) {
                throw new StudentException('Admission number is already registered.');
            }
        }

        return $this->students->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->students->delete($id);
    }
}