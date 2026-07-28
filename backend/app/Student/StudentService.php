<?php

namespace App\Student;

use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\Section\SectionRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\UserRepositoryInterface;
use App\Auth\ValidationException;
use PDO;
use Throwable;

class StudentService implements StudentServiceInterface
{
    public function __construct(
        private StudentRepositoryInterface $students,
        private StudentValidator $validator,
        private ?AcademicSessionRepositoryInterface $sessionRepository = null,
        private ?AcademicClassRepositoryInterface $classRepository = null,
        private ?SectionRepositoryInterface $sectionRepository = null,
        private ?UserRepositoryInterface $userRepository = null,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
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

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->students->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'student', $result->id(), null, $payload);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
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

        $old = $this->students->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->students->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'student', $request->id(), $this->auditLogger->entityToArray($old), $payload);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $old = $this->students->findById($id);

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->students->delete($id);

            if ($result && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('DELETE', 'student', $id, $this->auditLogger->entityToArray($old));
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
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
