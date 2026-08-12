<?php

namespace App\Permission;

use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class PermissionController extends BaseController
{
    public function __construct(private ?PermissionServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof PermissionServiceInterface) {
            return $this->error('Permission service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new PermissionListRequest($this->query($request)));

            return $this->json([
                'items' => PermissionResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Permission list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof PermissionServiceInterface) {
            return $this->error('Permission service unavailable.', 503);
        }

        $permission = $this->service->find($id);
        if (!$permission instanceof Permission) {
            return $this->error('Permission not found.', 404);
        }

        return $this->json(PermissionResponse::fromEntity($permission));
    }

    private function query(RequestHelper $request): array
    {
        $requestData = $request->all();

        return is_array($requestData['query'] ?? null) ? $requestData['query'] : [];
    }
}
