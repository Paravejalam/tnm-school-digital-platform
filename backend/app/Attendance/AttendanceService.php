<?php

namespace App\Attendance;

use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\Section\SectionRepositoryInterface;
use App\Student\StudentRepositoryInterface;
use App\Auth\ValidationException;

class AttendanceService implements AttendanceServiceInterface
{
    public function __construct(
        private AttendanceRepositoryInterface $repository,
        private AttendanceValidator $validator,
        private ?AcademicSessionRepositoryInterface $sessionRepository = null,
        private ?AcademicClassRepositoryInterface $classRepository = null,
        private ?SectionRepositoryInterface $sectionRepository = null,
        private ?StudentRepositoryInterface $studentRepository = null
    ) {
    }

    public function list(AttendanceListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?Attendance
    {
        return $this->repository->findById($id);
    }

    public function create(CreateAttendanceRequest $request): Attendance
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);
        $this->validateReferences($payload);

        $existing = $this->repository->findByName((string) $payload['attendance_date']);
        if ($existing instanceof Attendance) {
            throw new AttendanceException('Attendance already exists.');
        }

        return $this->repository->create($payload);
    }

    public function update(UpdateAttendanceRequest $request): ?Attendance
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);
        $this->validateReferences($payload);

        if (isset($payload['attendance_date'])) {
            $existing = $this->repository->findByName((string) $payload['attendance_date']);
            if ($existing instanceof Attendance && $existing->id() !== $request->id()) {
                throw new AttendanceException('Attendance already exists.');
            }
        }

        return $this->repository->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
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

        if (isset($payload['student_id']) && $this->studentRepository instanceof StudentRepositoryInterface) {
            $student = $this->studentRepository->findById((int) $payload['student_id']);
            if ($student === null) {
                throw new ValidationException(['student_id' => ['Student not found.']]);
            }
        }
    }
}
