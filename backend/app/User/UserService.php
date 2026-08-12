<?php

namespace App\User;

use App\Auth\PasswordHasher;
use App\Auth\ValidationException;
use App\Audit\AuditLoggerInterface;
use PDO;
use Throwable;

class UserService implements UserServiceInterface
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private UserValidator $validator,
        private ?PasswordHasher $passwordHasher = null,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(UserListRequest $request): array
    {
        return $this->repository->paginate(
            $request->page(),
            $request->perPage(),
            $request->search(),
            $request->isActive()
        );
    }

    public function find(int $id): ?User
    {
        return $this->repository->findById($id);
    }

    public function create(CreateUserRequest $request): ?User
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);

        if ($this->repository->findByEmail($payload['email'] ?? '') instanceof User) {
            throw new ValidationException('Email already in use.', ['email' => ['Email is already registered.']]);
        }

        $roleIds = $request->roleIds();
        if ($roleIds !== []) {
            $this->validator->validateRoles($this->repository, $roleIds);
        }

        $hash = $this->passwordHasher instanceof PasswordHasher
            ? $this->passwordHasher->hash((string) $payload['password'])
            : password_hash((string) $payload['password'], PASSWORD_DEFAULT);

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $user = $this->repository->create([
                'name' => $payload['name'] ?? null,
                'email' => $payload['email'] ?? null,
                'password_hash' => $hash,
                'is_active' => $payload['is_active'] ?? true,
            ]);

            if ($user->id() !== null) {
                foreach ($roleIds as $roleId) {
                    $this->repository->assignRole($user->id(), $roleId);
                }
            }

            if ($this->auditLogger instanceof AuditLoggerInterface && $user->id() !== null) {
                $this->auditLogger->log('CREATE', 'user', $user->id(), null, [
                    'name' => $payload['name'] ?? null,
                    'email' => $payload['email'] ?? null,
                ]);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $user->id() !== null ? $this->repository->findById($user->id()) : null;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    public function update(UpdateUserRequest $request): ?User
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        $current = $this->repository->findById($request->id());
        if (!$current instanceof User) {
            return null;
        }

        $email = $payload['email'] ?? $current->email();
        $existing = $this->repository->findByEmail((string) $email);
        if ($existing instanceof User && $existing->id() !== $request->id()) {
            throw new ValidationException('Email already in use.', ['email' => ['Email is already registered.']]);
        }

        $roleIds = $request->roleIds();

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $attributes = [
                'name' => $payload['name'] ?? $current->name(),
                'email' => $email,
            ];

            if (array_key_exists('is_active', $payload)) {
                $attributes['is_active'] = (bool) $payload['is_active'];
            }

            if (array_key_exists('password', $payload) && $payload['password'] !== '') {
                $attributes['password_hash'] = $this->passwordHasher instanceof PasswordHasher
                    ? $this->passwordHasher->hash((string) $payload['password'])
                    : password_hash((string) $payload['password'], PASSWORD_DEFAULT);
            }

            $user = $this->repository->update($request->id(), $attributes);

            if ($roleIds !== [] && $user instanceof User) {
                $this->repository->replaceRoles($request->id(), $roleIds);
            }

            if ($user instanceof User && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'user', $request->id(), [
                    'name' => $current->name(),
                    'email' => $current->email(),
                ], [
                    'name' => $payload['name'] ?? $current->name(),
                    'email' => $email,
                ]);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $user instanceof User ? $this->repository->findById($request->id()) : null;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $current = $this->repository->findById($id);
        if (!$current instanceof User) {
            return false;
        }

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $deleted = $this->repository->softDelete($id);

            if ($deleted && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('DELETE', 'user', $id, [
                    'name' => $current->name(),
                    'email' => $current->email(),
                ], null);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $deleted;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }
}
