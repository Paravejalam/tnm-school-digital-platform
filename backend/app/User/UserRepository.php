<?php

namespace App\User;

use PDO;
use Throwable;

class UserRepository implements UserRepositoryInterface
{
    private const COLUMNS = 'id, name, email, password_hash, is_active, last_login_at, created_at, updated_at';

    public function __construct(private ?PDO $database = null)
    {
    }

    public function paginate(int $page, int $perPage, ?string $search = null, ?bool $isActive = null): array
    {
        if (!$this->database instanceof PDO) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }

        try {
            $filters = ['deleted_at IS NULL'];
            $params = [];

            if ($search !== null && $search !== '') {
                $filters[] = '(name LIKE :search OR email LIKE :search)';
                $params['search'] = '%' . $search . '%';
            }

            if ($isActive !== null) {
                $filters[] = 'is_active = :is_active';
                $params['is_active'] = $isActive ? 1 : 0;
            }

            $where = ' WHERE ' . implode(' AND ', $filters);

            $count = $this->database->prepare('SELECT COUNT(*) FROM users' . $where);
            $count->execute($params);
            $total = (int) $count->fetchColumn();

            $statement = $this->database->prepare(
                'SELECT ' . self::COLUMNS . ' FROM users' . $where . ' ORDER BY id ASC LIMIT :limit OFFSET :offset'
            );
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value);
            }
            $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
            $statement->execute();

            $items = array_map(fn (array $row): User => $this->mapEntity($row), $statement->fetchAll());

            return ['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
        } catch (Throwable) {
            return ['items' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage];
        }
    }

    public function findById(int $id): ?User
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare(
                'SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1'
            );
            $statement->execute(['id' => $id]);
            $row = $statement->fetch();

            if (!is_array($row)) {
                return null;
            }

            $user = $this->mapEntity($row);

            return $this->withRoles($user);
        } catch (Throwable) {
            return null;
        }
    }

    public function findByEmail(string $email): ?User
    {
        if (!$this->database instanceof PDO) {
            return null;
        }

        try {
            $statement = $this->database->prepare(
                'SELECT ' . self::COLUMNS . ' FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $row = $statement->fetch();

            return is_array($row) ? $this->mapEntity($row) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function create(array $attributes): User
    {
        if (!$this->database instanceof PDO) {
            return $this->mapEntity($attributes);
        }

        try {
            $statement = $this->database->prepare(
                'INSERT INTO users (name, email, password_hash, is_active) VALUES (:name, :email, :password_hash, :is_active)'
            );
            $statement->execute([
                'name' => $attributes['name'] ?? null,
                'email' => $attributes['email'] ?? null,
                'password_hash' => $attributes['password_hash'] ?? null,
                'is_active' => ($attributes['is_active'] ?? true) ? 1 : 0,
            ]);

            $attributes['id'] = (int) $this->database->lastInsertId();
        } catch (Throwable) {
        }

        return $this->mapEntity($attributes);
    }

    public function update(int $id, array $attributes): ?User
    {
        $current = $this->findById($id);
        if (!$current instanceof User || !$this->database instanceof PDO) {
            return $current;
        }

        try {
            $fields = [];
            $params = ['id' => $id];

            foreach (['name', 'email', 'password_hash', 'is_active'] as $field) {
                if (array_key_exists($field, $attributes)) {
                    $fields[] = $field . ' = :' . $field;
                    $params[$field] = $field === 'is_active'
                        ? (($attributes[$field] ?? false) ? 1 : 0)
                        : $attributes[$field];
                }
            }

            if ($fields === []) {
                return $current;
            }

            $statement = $this->database->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id AND deleted_at IS NULL');
            $statement->execute($params);
        } catch (Throwable) {
        }

        return $this->findById($id);
    }

    public function softDelete(int $id): bool
    {
        if (!$this->database instanceof PDO) {
            return false;
        }

        try {
            $statement = $this->database->prepare('UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = :id AND deleted_at IS NULL');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    public function assignRole(int $userId, int $roleId): void
    {
        if (!$this->database instanceof PDO) {
            return;
        }

        try {
            $statement = $this->database->prepare($this->insertIgnore() . ' INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
            $statement->execute(['user_id' => $userId, 'role_id' => $roleId]);
        } catch (Throwable) {
        }
    }

    public function replaceRoles(int $userId, array $roleIds): void
    {
        if (!$this->database instanceof PDO) {
            return;
        }

        try {
            $statement = $this->database->prepare('DELETE FROM user_roles WHERE user_id = :user_id');
            $statement->execute(['user_id' => $userId]);

            foreach (array_unique(array_filter(array_map('intval', $roleIds))) as $roleId) {
                if ($this->roleExists($roleId)) {
                    $this->assignRole($userId, $roleId);
                }
            }
        } catch (Throwable) {
        }
    }

    public function roleExists(int $roleId): bool
    {
        if (!$this->database instanceof PDO) {
            return false;
        }

        try {
            $statement = $this->database->prepare('SELECT 1 FROM roles WHERE id = :id AND deleted_at IS NULL LIMIT 1');
            $statement->execute(['id' => $roleId]);

            return $statement->fetchColumn() !== false;
        } catch (Throwable) {
            return false;
        }
    }

    public function findRoleIds(int $userId): array
    {
        if (!$this->database instanceof PDO) {
            return [];
        }

        try {
            $statement = $this->database->prepare('SELECT role_id FROM user_roles WHERE user_id = :user_id');
            $statement->execute(['user_id' => $userId]);

            return array_map('intval', array_column($statement->fetchAll(), 'role_id'));
        } catch (Throwable) {
            return [];
        }
    }

    private function insertIgnore(): string
    {
        $driver = $this->database?->getAttribute(PDO::ATTR_DRIVER_NAME);

        return $driver === 'sqlite' ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
    }

    private function withRoles(User $user): User
    {
        $roleIds = $this->findRoleIds($user->id() ?? 0);

        if (!$this->database instanceof PDO || $roleIds === []) {
            return new User(
                $user->id(),
                $user->name(),
                $user->email(),
                $user->passwordHash(),
                $user->isActive(),
                $user->lastLoginAt(),
                $user->createdAt(),
                $user->updatedAt(),
                $roleIds
            );
        }

        $placeholders = implode(',', array_fill(0, count($roleIds), '?'));
        $statement = $this->database->prepare('SELECT id, slug FROM roles WHERE id IN (' . $placeholders . ') AND deleted_at IS NULL');
        $statement->execute($roleIds);
        $slugs = array_map(
            fn (array $row): string => (string) $row['slug'],
            $statement->fetchAll()
        );

        return new User(
            $user->id(),
            $user->name(),
            $user->email(),
            $user->passwordHash(),
            $user->isActive(),
            $user->lastLoginAt(),
            $user->createdAt(),
            $user->updatedAt(),
            $roleIds,
            $slugs
        );
    }

    private function mapEntity(array $row): User
    {
        return new User(
            isset($row['id']) ? (int) $row['id'] : null,
            $row['name'] ?? null,
            $row['email'] ?? null,
            $row['password_hash'] ?? null,
            isset($row['is_active']) ? (bool) $row['is_active'] : null,
            $row['last_login_at'] ?? null,
            $row['created_at'] ?? null,
            $row['updated_at'] ?? null
        );
    }
}
