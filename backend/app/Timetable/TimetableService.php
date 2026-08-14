<?php

namespace App\Timetable;

use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\AcademicClass\AcademicClassRepositoryInterface;
use App\Section\SectionRepositoryInterface;
use App\Subject\SubjectRepositoryInterface;
use App\Teacher\TeacherRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use PDO;
use Throwable;

class TimetableService implements TimetableServiceInterface
{
    public function __construct(
        private TimetableRepositoryInterface $repository,
        private TimetableValidator $validator,
        private ?AcademicSessionRepositoryInterface $sessionRepository = null,
        private ?AcademicClassRepositoryInterface $classRepository = null,
        private ?SectionRepositoryInterface $sectionRepository = null,
        private ?SubjectRepositoryInterface $subjectRepository = null,
        private ?TeacherRepositoryInterface $teacherRepository = null,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(TimetableListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?Timetable
    {
        return $this->repository->findById($id);
    }

    public function create(CreateTimetableRequest $request): Timetable
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);
        $this->validateReferences($payload);

        $existing = $this->repository->findByName((string) $payload['timetable_name']);
        if ($existing instanceof Timetable) {
            throw new TimetableException('Timetable already exists.');
        }

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'timetable', $result->id(), null, $payload);
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

    public function update(UpdateTimetableRequest $request): ?Timetable
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);
        $this->validateReferences($payload);

        if (isset($payload['timetable_name'])) {
            $existing = $this->repository->findByName((string) $payload['timetable_name']);
            if ($existing instanceof Timetable && $existing->id() !== $request->id()) {
                throw new TimetableException('Timetable already exists.');
            }
        }

        $old = $this->repository->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'timetable', $request->id(), $this->auditLogger->entityToArray($old), $payload);
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
        $old = $this->repository->findById($id);

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->delete($id);

            if ($result && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('DELETE', 'timetable', $id, $this->auditLogger->entityToArray($old));
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
                throw new ValidationException(errors: ['academic_session_id' => ['Academic session not found.']]);
            }
        }

        if (isset($payload['class_id']) && $this->classRepository instanceof AcademicClassRepositoryInterface) {
            $class = $this->classRepository->findById((int) $payload['class_id']);
            if ($class === null) {
                throw new ValidationException(errors: ['class_id' => ['Academic class not found.']]);
            }
        }

        if (isset($payload['section_id']) && $this->sectionRepository instanceof SectionRepositoryInterface) {
            $section = $this->sectionRepository->findById((int) $payload['section_id']);
            if ($section === null) {
                throw new ValidationException(errors: ['section_id' => ['Section not found.']]);
            }
        }

        if (isset($payload['subject_id']) && $this->subjectRepository instanceof SubjectRepositoryInterface) {
            $subject = $this->subjectRepository->findById((int) $payload['subject_id']);
            if ($subject === null) {
                throw new ValidationException(errors: ['subject_id' => ['Subject not found.']]);
            }
        }

        if (isset($payload['teacher_id']) && $this->teacherRepository instanceof TeacherRepositoryInterface) {
            $teacher = $this->teacherRepository->findById((int) $payload['teacher_id']);
            if ($teacher === null) {
                throw new ValidationException(errors: ['teacher_id' => ['Teacher not found.']]);
            }
        }
    }
}
