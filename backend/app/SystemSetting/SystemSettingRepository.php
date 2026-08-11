<?php

namespace App\SystemSetting;

use PDO;
use Throwable;

class SystemSettingRepository implements SystemSettingRepositoryInterface
{
    private const COLUMNS = 'id, `key`, value, type, module, description, is_public, created_at, updated_at';

    public function __construct(private ?PDO $database = null)
    {
    }

    public function paginate(int $page, int $perPage, ?string $module = null, ?bool $isPublic = null): array
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

            if ($isPublic !== null) {
                $filters[] = 'is_public = :is_public';
                $params['is_public'] = $isPublic ? 1 : 0;
            }

            $where = $filters !== [] ? ' WHERE ' . implode(' AND ', $filters) : '';

            $count = $this->database->prepare('SELECT COUNT(*) FROM system_settings' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare('SELECT ' . self::COLUMNS . ' FROM system_settings' . $where . ' ORDER BY id ASC LIMIT :limit OFFSET :offset');
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): SystemSetting => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?SystemSetting
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT ' . self::COLUMNS . ' FROM system_settings WHERE id = :id LIMIT 1');
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function findByKey(string $key): ?SystemSetting
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare('SELECT ' . self::COLUMNS . ' FROM system_settings WHERE `key` = :key LIMIT 1');
            $statement->execute(['key' => $key]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function update(int $id, array $attributes): ?SystemSetting
    {
        $current = $this->findById($id);
        if (!$this->database instanceof PDO) {
            return $current;
        }

        try {
            $statement = $this->database->prepare(
                'UPDATE system_settings SET `value` = :value, type = :type, module = :module, description = :description, is_public = :is_public WHERE id = :id'
            );
            $statement->execute($this->attributes($current, $attributes));
        } catch (Throwable) {
        }

        return $this->findById($id);
    }

    private function attributes(?SystemSetting $current, array $attributes): array
    {
        $base = [
            'value' => null,
            'type' => 'string',
            'module' => null,
            'description' => null,
            'is_public' => 0,
        ];

        if ($current instanceof SystemSetting) {
            $base['value'] = $current->value();
            $base['type'] = $current->type() ?? 'string';
            $base['module'] = $current->module();
            $base['description'] = $current->description();
            $base['is_public'] = $current->isPublic() ?? 0;
        }

        foreach (['value', 'type', 'module', 'description', 'is_public'] as $field) {
            if (array_key_exists($field, $attributes)) {
                $base[$field] = $attributes[$field];
            }
        }

        return [
            'value' => $base['value'] !== null ? (string) $base['value'] : null,
            'type' => (string) $base['type'],
            'module' => $base['module'] !== null ? (string) $base['module'] : null,
            'description' => $base['description'] !== null ? (string) $base['description'] : null,
            'is_public' => $base['is_public'] ? 1 : 0,
            'id' => $current instanceof SystemSetting ? $current->id() : 0,
        ];
    }

    private function mapEntity(array $row): SystemSetting
    {
        return new SystemSetting(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['key'] ?? null,
            $row['value'] ?? null,
            $row['type'] ?? 'string',
            $row['module'] ?? null,
            $row['description'] ?? null,
            isset($row['is_public']) ? (int) $row['is_public'] : 0,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
