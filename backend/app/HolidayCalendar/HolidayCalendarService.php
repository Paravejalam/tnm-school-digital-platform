<?php

namespace App\HolidayCalendar;

use App\AcademicSession\AcademicSessionRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use PDO;
use Throwable;

class HolidayCalendarService implements HolidayCalendarServiceInterface
{
    public function __construct(
        private HolidayCalendarRepositoryInterface $repository,
        private HolidayCalendarValidator $validator,
        private ?AcademicSessionRepositoryInterface $sessionRepository = null,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(HolidayCalendarListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?HolidayCalendar
    {
        return $this->repository->findById($id);
    }

    public function create(CreateHolidayCalendarRequest $request): HolidayCalendar
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);
        $this->validateReferences($payload);

        $existing = $this->repository->findByName((string) $payload['holiday_name']);
        if ($existing instanceof HolidayCalendar) {
            throw new HolidayCalendarException('Holiday calendar already exists.');
        }

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'holiday_calendar', $result->id(), null, $payload);
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

    public function update(UpdateHolidayCalendarRequest $request): ?HolidayCalendar
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);
        $this->validateReferences($payload);

        if (isset($payload['holiday_name'])) {
            $existing = $this->repository->findByName((string) $payload['holiday_name']);
            if ($existing instanceof HolidayCalendar && $existing->id() !== $request->id()) {
                throw new HolidayCalendarException('Holiday calendar already exists.');
            }
        }

        $old = $this->repository->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'holiday_calendar', $request->id(), $this->auditLogger->entityToArray($old), $payload);
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
                $this->auditLogger->log('DELETE', 'holiday_calendar', $id, $this->auditLogger->entityToArray($old));
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
                throw new ValidationException(
                    errors: [
                        'academic_session_id' => ['Academic session not found.'],
                    ]
                );
            }
        }
    }
}
