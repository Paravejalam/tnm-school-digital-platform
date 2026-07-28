<?php

namespace App\AttendanceRecord;

use App\Attendance\AttendanceRepositoryInterface;
use App\Auth\ValidationException;

class AttendanceRecordService implements AttendanceRecordServiceInterface
{
    public function __construct(
        private AttendanceRecordRepositoryInterface $repository,
        private AttendanceRecordValidator $validator,
        private ?AttendanceRepositoryInterface $attendanceRepository = null
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

        return $this->repository->create($payload);
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

        return $this->repository->update($request->id(), $payload);
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete($id);
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
