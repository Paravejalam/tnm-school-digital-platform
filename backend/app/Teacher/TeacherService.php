<?php

namespace App\Teacher;

use App\Auth\UserRepositoryInterface;
use App\Audit\AuditLoggerInterface;
use App\Auth\ValidationException;
use PDO;
use Throwable;

class TeacherService implements TeacherServiceInterface
{
    public function __construct(
        private TeacherRepositoryInterface $teachers,
        private TeacherValidator $validator,
        private ?UserRepositoryInterface $userRepository = null,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(TeacherListRequest $request): array
    {
        return $this->teachers->paginate(
            $request->page(),
            $request->perPage(),
            $request->name(),
            $request->employeeId()
        );
    }

    public function find(int $id): ?Teacher
    {
        return $this->teachers->findById($id);
    }

    public function create(CreateTeacherRequest $request): Teacher
    {
        $payload = $request->payload();
        $this->validator->validateCreate($payload);
        $this->validateReferences($payload);

        $existing = $this->teachers->findByEmployeeId((string) $payload['employee_id']);
        if ($existing instanceof Teacher) {
            throw new TeacherException('Employee id is already registered.');
        }

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->teachers->create($payload);

            if ($this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('CREATE', 'teacher', $result->id(), null, $payload);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    public function update(UpdateTeacherRequest $request): ?Teacher
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);
        $this->validateReferences($payload);

        if (isset($payload['employee_id'])) {
            $existing = $this->teachers->findByEmployeeId((string) $payload['employee_id']);
            if ($existing instanceof Teacher && $existing->id() !== $request->id()) {
                throw new TeacherException('Employee id is already registered.');
            }
        }

        $old = $this->teachers->findById($request->id());

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->teachers->update($request->id(), $payload);

            if ($result !== null && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'teacher', $request->id(), $this->auditLogger->entityToArray($old), $payload);
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    public function delete(int $id): bool
    {
        $old = $this->teachers->findById($id);

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->teachers->delete($id);

            if ($result && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('DELETE', 'teacher', $id, $this->auditLogger->entityToArray($old));
            }

            if ($this->database instanceof PDO) {
                $this->database->commit();
            }

            return $result;
        } catch (Throwable $e) {
            if ($this->database instanceof PDO && $this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $e;
        }
    }

    private function validateReferences(array $payload): void
    {
        if (isset($payload['user_id']) && $this->userRepository instanceof UserRepositoryInterface) {
            $user = $this->userRepository->findById((int) $payload['user_id']);
            if ($user === null) {
                throw new ValidationException(['user_id' => ['User not found.']]);
            }
        }
    }
}
