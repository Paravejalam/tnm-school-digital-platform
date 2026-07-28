<?php

namespace App\Student;

use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\Section\SectionRepositoryInterface;
use App\Auth\UserRepositoryInterface;
use App\Auth\ValidationException;

class StudentService implements StudentServiceInterface
{
    public function __construct(
        private StudentRepositoryInterface $students,
        private StudentValidator $validator,
        private ?AcademicSessionRepositoryInterface $sessionRepository = null,
        private ?AcademicClassRepositoryInterface $classRepository = null,
        private ?SectionRepositoryInterface $sectionRepository = null,
        private ?UserRepositoryInterface $userRepository = null
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
        $this->validateReferences($payload);

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
        $this->validateReferences($payload);

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

    private function validateReferences(array $payload): void
    {
        if (isset($payload['academic_session_id']) && $this->sessionRepository instanceof AcademicSessionRepositoryInterface) {
            $session = $this->sessionRepository->findById((int) $payload['academic_session_id']);
            if ($session === null) {
                throw new ValidationException(['academic_session_id' => ['Academic session not found.']]);
            }
        }

        if (isset($payload['class_id']) && $this->classRepository instanceof AcademicClassRepositoryInterface) {
            $class = $this->classRepository->findById((int) $payload['class_id']);
            if ($class === null) {
                throw new ValidationException(['class_id' => ['Academic class not found.']]);
            }
        }

        if (isset($payload['section_id']) && $this->sectionRepository instanceof SectionRepositoryInterface) {
            $section = $this->sectionRepository->findById((int) $payload['section_id']);
            if ($section === null) {
                throw new ValidationException(['section_id' => ['Section not found.']]);
            }
        }

        if (isset($payload['user_id']) && $this->userRepository instanceof UserRepositoryInterface) {
            $user = $this->userRepository->findById((int) $payload['user_id']);
            if ($user === null) {
                throw new ValidationException(['user_id' => ['User not found.']]);
            }
        }
    }
}
