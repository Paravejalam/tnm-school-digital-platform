<?php

namespace App\AttendanceRecord;

interface AttendanceRecordServiceInterface
{
    public function list(AttendanceRecordListRequest $request): array;

    public function find(int $id): ?AttendanceRecord;

    public function create(CreateAttendanceRecordRequest $request): AttendanceRecord;

    public function update(UpdateAttendanceRecordRequest $request): ?AttendanceRecord;

    public function delete(int $id): bool;
}
