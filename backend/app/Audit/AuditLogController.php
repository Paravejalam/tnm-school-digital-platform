<?php

namespace App\Audit;

use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class AuditLogController extends BaseController
{
    public function __construct(private ?AuditLogServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof AuditLogServiceInterface) {
            return $this->error('Audit log service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new AuditLogListRequest($this->query($request)));

            return $this->json([
                'items' => AuditLogResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Audit log list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof AuditLogServiceInterface) {
            return $this->error('Audit log service unavailable.', 503);
        }

        $log = $this->service->find($id);
        if (!$log instanceof AuditLog) {
            return $this->error('Audit log entry not found.', 404);
        }

        return $this->json(AuditLogResponse::fromEntity($log));
    }

    private function query(RequestHelper $request): array
    {
        $requestData = $request->all();

        return is_array($requestData['query'] ?? null) ? $requestData['query'] : [];
    }
}
