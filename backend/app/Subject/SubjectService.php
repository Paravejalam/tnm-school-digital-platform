<?php

namespace App\Subject;

use App\Audit\AuditLoggerInterface;
use PDO;
use Throwable;

class SubjectService implements SubjectServiceInterface
{
    public function __construct(
        private SubjectRepositoryInterface $repository,
        private SubjectValidator $validator,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(SubjectListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?Subject
    {
        return $this->repository->findById($id);
    }

    public function create(CreateSubjectRequest $request): Subject
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->repository->findByName((string) $payload['subject_name']);
        if ($existing instanceof Subject) {
            throw new SubjectException('Subject already exists.');
        }

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'subject', $result->id(), null, $payload);
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

    public function update(UpdateSubjectRequest $request): ?Subject
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['subject_name'])) {
            $existing = $this->repository->findByName((string) $payload['subject_name']);
            if ($existing instanceof Subject && $existing->id() !== $request->id()) {
                throw new SubjectException('Subject already exists.');
            }
        }

        $old = $this->repository->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'subject', $request->id(), $this->auditLogger->entityToArray($old), $payload);
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
                $this->auditLogger->log('DELETE', 'subject', $id, $this->auditLogger->entityToArray($old));
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
}
