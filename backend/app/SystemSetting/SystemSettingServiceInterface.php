<?php

namespace App\SystemSetting;

interface SystemSettingServiceInterface
{
    public function list(SystemSettingListRequest $request): array;

    public function find(int $id): ?SystemSetting;

    public function update(UpdateSystemSettingRequest $request): ?SystemSetting;
}
