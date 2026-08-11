<?php

namespace App\SystemSetting;

use App\Audit\AuditLoggerInterface;
use PDO;
use Throwable;

class SystemSettingService implements SystemSettingServiceInterface
{
    public function __construct(
        private SystemSettingRepositoryInterface $repository,
        private SystemSettingValidator $validator,
        private ?PDO $database = null,
        private ?AuditLoggerInterface $auditLogger = null
    ) {
    }

    public function list(SystemSettingListRequest $request): array
    {
        return $this->repository->paginate(
            $request->page(),
            $request->perPage(),
            $request->module(),
            $request->isPublic()
        );
    }

    public function find(int $id): ?SystemSetting
    {
        return $this->repository->findById($id);
    }

    public function update(UpdateSystemSettingRequest $request): ?SystemSetting
    {
        $payload = $request->payload();
        $this->validator->validateUpdate($payload);

        $old = $this->repository->findById($request->id());
        if (!$old instanceof SystemSetting) {
            return null;
        }

        $this->validator->validateValueAgainstType($payload);
        $payload = $this->validator->normalizeValue($payload);

        if ($this->database instanceof PDO) {
            $this->database->beginTransaction();
        }

        try {
            $result = $this->repository->update($request->id(), $payload);

            if ($result instanceof SystemSetting && $this->auditLogger instanceof AuditLoggerInterface) {
                $this->auditLogger->log('UPDATE', 'system_setting', $request->id(), $this->auditLogger->entityToArray($old), $payload);
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
}
