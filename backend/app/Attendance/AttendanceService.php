<?php

namespace App\Attendance;

class AttendanceService implements AttendanceServiceInterface
{
    public function __construct(
        private AttendanceRepositoryInterface $repository,
        private AttendanceValidator $validator
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
}
