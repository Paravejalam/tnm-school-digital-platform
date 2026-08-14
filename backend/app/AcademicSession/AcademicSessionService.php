<?php

namespace App\AcademicSession;

use App\Audit\AuditLoggerInterface;
use PDO;
use Throwable;

class AcademicSessionService implements AcademicSessionServiceInterface
{
    public function __construct(
        private AcademicSessionRepositoryInterface $repository,
        private AcademicSessionValidator $validator,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(AcademicSessionListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?AcademicSession
    {
        return $this->repository->findById($id);
    }

    public function create(CreateAcademicSessionRequest $request): AcademicSession
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        $existing = $this->repository->findByName((string) $payload['session_name']);
        if ($existing instanceof AcademicSession) {
            throw new AcademicSessionException('Academic session already exists.');
        }

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'academic_session', $result->id(), null, $payload);
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

    public function update(UpdateAcademicSessionRequest $request): ?AcademicSession
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        if (isset($payload['session_name'])) {
            $existing = $this->repository->findByName((string) $payload['session_name']);
            if ($existing instanceof AcademicSession && $existing->id() !== $request->id()) {
                throw new AcademicSessionException('Academic session already exists.');
            }
        }

        $old = $this->repository->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'academic_session', $request->id(), $this->auditLogger->entityToArray($old), $payload);
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
                $this->auditLogger->log('DELETE', 'academic_session', $id, $this->auditLogger->entityToArray($old));
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
