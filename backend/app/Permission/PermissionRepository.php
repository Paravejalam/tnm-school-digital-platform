<?php

namespace App\Permission;

use PDO;
use Throwable;

class PermissionRepository implements PermissionRepositoryInterface
{
    private const COLUMNS = 'id, name, slug, module, description, created_at, updated_at';

    public function __construct(private ?PDO $database = null)
    {
    }

    public function paginate(int $page, int $perPage, ?string $module = null, ?string $search = null): array
    {
        if (!$this->database instanceof PDO) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        try {
            $filters = [];
            $params = [];

            if ($module !== null && $module !== '') {
                $filters[] = 'module = :module';
                $params['module'] = $module;
            }

            if ($search !== null && $search !== '') {
                $filters[] = '(name LIKE :search OR slug LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }

            $where = $filters !== [] ? ' WHERE ' . implode(' AND ', $filters) : '';

            $count = $this->database->prepare('SELECT COUNT(*) FROM permissions' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare(
                'SELECT ' . self::COLUMNS . ' FROM permissions' . $where . ' ORDER BY module ASC, slug ASC LIMIT :limit OFFSET :offset'
            );
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): Permission => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?Permission
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT ' . self::COLUMNS . ' FROM permissions WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findBySlug(string $slug): ?Permission
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT ' . self::COLUMNS . ' FROM permissions WHERE slug = :slug LIMIT 1');
            $statement->execute(['slug' => $slug]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function mapEntity(array $row): Permission
    {
        return new Permission(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['slug'] ?? null,
            $row['module'] ?? null,
            $row['description'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
