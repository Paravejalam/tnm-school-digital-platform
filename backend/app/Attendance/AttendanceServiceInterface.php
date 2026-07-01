<?php

namespace App\Attendance;

interface AttendanceServiceInterface
{
    public function list(AttendanceListRequest $request): array;

    public function find(int $id): ?Attendance;

    public function create(CreateAttendanceRequest $request): Attendance;

    public function update(UpdateAttendanceRequest $request): ?Attendance;

    public function delete(int $id): bool;
}
