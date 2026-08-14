<?php

namespace App\Period;

use App\Timetable\TimetableRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use PDO;
use Throwable;

class PeriodService implements PeriodServiceInterface
{
    public function __construct(
        private PeriodRepositoryInterface $repository,
        private PeriodValidator $validator,
        private ?TimetableRepositoryInterface $timetableRepository = null,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(PeriodListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?Period
    {
        return $this->repository->findById($id);
    }

    public function create(CreatePeriodRequest $request): Period
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);
        $this->validateReferences($payload);

        $existing = $this->repository->findByName((string) $payload['period_name']);
        if ($existing instanceof Period) {
            throw new PeriodException('Period already exists.');
        }

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'period', $result->id(), null, $payload);
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

    public function update(UpdatePeriodRequest $request): ?Period
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);
        $this->validateReferences($payload);

        if (isset($payload['period_name'])) {
            $existing = $this->repository->findByName((string) $payload['period_name']);
            if ($existing instanceof Period && $existing->id() !== $request->id()) {
                throw new PeriodException('Period already exists.');
            }
        }

        $old = $this->repository->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'period', $request->id(), $this->auditLogger->entityToArray($old), $payload);
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
                $this->auditLogger->log('DELETE', 'period', $id, $this->auditLogger->entityToArray($old));
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
        if (isset($payload['timetable_id']) && $this->timetableRepository instanceof TimetableRepositoryInterface) {
            $timetable = $this->timetableRepository->findById((int) $payload['timetable_id']);
            if ($timetable === null) {
                throw new ValidationException(errors: ['timetable_id' => ['Timetable not found.']]);
            }
        }
    }
}
