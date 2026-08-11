<?php

namespace App\SystemSetting;

/**
 * SystemSetting data access contract.
 *
 * Settings are seed-managed and updated at runtime; the schema
 * (migration 0019) has no soft-delete column and RBAC only grants
 * system-settings.view and system-settings.update, therefore create
 * and delete operations are intentionally not exposed.
 *
 * Authority: .github/AGENT.md
 */
interface SystemSettingRepositoryInterface
{
    /**
     * @return array{items: list<SystemSetting>, total: int, page: int, per_page: int}
     */
    public function paginate(int $page, int $perPage, ?string $module = null, ?bool $isPublic = null): array;

    public function findById(int $id): ?SystemSetting;

    public function findByKey(string $key): ?SystemSetting;

    public function update(int $id, array $attributes): ?SystemSetting;
}
