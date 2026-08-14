<?php

namespace App\Role;

use PDO;
use Throwable;

class RoleRepository implements RoleRepositoryInterface
{
    private const COLUMNS = 'id, name, slug, description, is_system, created_at, updated_at';

    public function __construct(private ?PDO $database = null)
    {
    }

    public function paginate(int $page, int $perPage, ?string $search = null): array
    {
        if (!$this->database instanceof PDO) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        try {
            $where = ' WHERE deleted_at IS NULL';
            $params = [];

            if ($search !== null && $search !== '') {
                $where .= ' AND (name LIKE :search OR slug LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }

            $count = $this->database->prepare('SELECT COUNT(*) FROM roles' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare(
                'SELECT ' . self::COLUMNS . ' FROM roles' . $where . ' ORDER BY id ASC LIMIT :limit OFFSET :offset'
            );
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): Role => $this->withPermissions($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?Role
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare(
                'SELECT ' . self::COLUMNS . ' FROM roles WHERE id = :id AND deleted_at IS NULL LIMIT 1'
            );
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->withPermissions($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findPermissionSlugs(int $roleId): array
    {
        if (!$this->database instanceof PDO) {
            return [];
        }

        try {
            $statement = $this->database->prepare(
                'SELECT p.slug FROM role_permissions rp
                 INNER JOIN permissions p ON rp.permission_id = p.id
                 WHERE rp.role_id = :role_id
                 ORDER BY p.slug ASC'
            );
            $statement->execute(['role_id' => $roleId]);

            return array_map(fn (array $row): string => (string) $row['slug'], $statement->fetchAll());
        } catch (Throwable) {
            return [];
        }
    }

    private function withPermissions(array $row): Role
    {
        return new Role(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['slug'] ?? null,
            $row['description'] ?? null,
            isset($row['is_system']) ? (bool) $row['is_system'] : null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null,
            isset($row['id']) ? $this->findPermissionSlugs((int) $row['id']) : []
        );
    }
}
