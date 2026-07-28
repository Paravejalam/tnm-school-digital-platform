<?php

namespace App\AttendanceRecord;

use App\Attendance\AttendanceRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use PDO;
use Throwable;

class AttendanceRecordService implements AttendanceRecordServiceInterface
{
    public function __construct(
        private AttendanceRecordRepositoryInterface $repository,
        private AttendanceRecordValidator $validator,
        private ?AttendanceRepositoryInterface $attendanceRepository = null,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(AttendanceRecordListRequest $request): array
    {
        return $this->repository->paginate($request->page(), $request->perPage(), $request->search());
    }

    public function find(int $id): ?AttendanceRecord
    {
        return $this->repository->findById($id);
    }

    public function create(CreateAttendanceRecordRequest $request): AttendanceRecord
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);
        $this->validateReferences($payload);

        $existing = $this->repository->findByName((string) $payload['record_name']);
        if ($existing instanceof AttendanceRecord) {
            throw new AttendanceRecordException('Attendance record already exists.');
        }

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'attendance_record', $result->id(), null, $payload);
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

    public function update(UpdateAttendanceRecordRequest $request): ?AttendanceRecord
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);
        $this->validateReferences($payload);

        if (isset($payload['record_name'])) {
            $existing = $this->repository->findByName((string) $payload['record_name']);
            if ($existing instanceof AttendanceRecord && $existing->id() !== $request->id()) {
                throw new AttendanceRecordException('Attendance record already exists.');
            }
        }

        $old = $this->repository->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'attendance_record', $request->id(), $this->auditLogger->entityToArray($old), $payload);
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
                $this->auditLogger->log('DELETE', 'attendance_record', $id, $this->auditLogger->entityToArray($old));
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
        if (isset($payload['attendance_id']) && $this->attendanceRepository instanceof AttendanceRepositoryInterface) {
            $attendance = $this->attendanceRepository->findById((int) $payload['attendance_id']);
            if ($attendance === null) {
                throw new ValidationException(['attendance_id' => ['Attendance not found.']]);
            }
        }
    }
}
