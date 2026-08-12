<?php

namespace App\Role;

use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class RoleController extends BaseController
{
    public function __construct(private ?RoleServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof RoleServiceInterface) {
            return $this->error('Role service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new RoleListRequest($this->query($request)));

            return $this->json([
                'items' => RoleResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('Role list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof RoleServiceInterface) {
            return $this->error('Role service unavailable.', 503);
        }

        $role = $this->service->find($id);
        if (!$role instanceof Role) {
            return $this->error('Role not found.', 404);
        }

        return $this->json(RoleResponse::fromEntity($role));
    }

    private function query(RequestHelper $request): array
    {
        $requestData = $request->all();

        return is_array($requestData['query'] ?? null) ? $requestData['query'] : [];
    }
}
