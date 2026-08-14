<?php

namespace App\User;

use App\Auth\ValidationException;
use App\Controllers\BaseController;
use App\Http\RequestHelper;
use Throwable;

class UserController extends BaseController
{
    public function __construct(private ?UserServiceInterface $service = null)
    {
        parent::__construct();
    }

    public function index(RequestHelper $request): array
    {
        if (!$this->service instanceof UserServiceInterface) {
            return $this->error('User service unavailable.', 503);
        }

        try {
            $result = $this->service->list(new UserListRequest($this->query($request)));

            return $this->json([
                'items' => UserResponse::collection($result['items'] ?? []),
                'pagination' => [
                    'total' => $result['total'] ?? 0,
                    'page' => $result['page'] ?? 1,
                    'per_page' => $result['per_page'] ?? 15,
                ],
            ]);
        } catch (Throwable) {
            return $this->error('User list request failed.', 500);
        }
    }

    public function show(int $id): array
    {
        if (!$this->service instanceof UserServiceInterface) {
            return $this->error('User service unavailable.', 503);
        }

        $user = $this->service->find($id);
        if (!$user instanceof User) {
            return $this->error('User not found.', 404);
        }

        return $this->json(UserResponse::fromEntity($user));
    }

    public function store(RequestHelper $request): array
    {
        if (!$this->service instanceof UserServiceInterface) {
            return $this->error('User service unavailable.', 503);
        }

        try {
            $user = $this->service->create(new CreateUserRequest($this->payload($request)));
            if (!$user instanceof User) {
                return $this->error('User creation failed.', 500);
            }

            return $this->json(UserResponse::fromEntity($user), 201);
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (Throwable) {
            return $this->error('User creation failed.', 500);
        }
    }

    public function update(int $id, RequestHelper $request): array
    {
        if (!$this->service instanceof UserServiceInterface) {
            return $this->error('User service unavailable.', 503);
        }

        try {
            $user = $this->service->update(new UpdateUserRequest($id, $this->payload($request)));
            if (!$user instanceof User) {
                return $this->error('User not found.', 404);
            }

            return $this->json(UserResponse::fromEntity($user));
        } catch (ValidationException $exception) {
            return $this->error($exception->getMessage(), 422, ['validation' => $exception->errors()]);
        } catch (Throwable) {
            return $this->error('User update failed.', 500);
        }
    }

    public function destroy(int $id): array
    {
        if (!$this->service instanceof UserServiceInterface) {
            return $this->error('User service unavailable.', 503);
        }

        try {
            if (!$this->service->delete($id)) {
                return $this->error('User not found.', 404);
            }

            return $this->json(['id' => $id, 'deleted' => true]);
        } catch (Throwable) {
            return $this->error('User deletion failed.', 500);
        }
    }

    private function payload(RequestHelper $request): array
    {
        $json = $request->json();
        if (is_array($json)) {
            return $json;
        }

        $requestData = $request->all();

        return is_array($requestData['body'] ?? null) ? $requestData['body'] : [];
    }

    private function query(RequestHelper $request): array
    {
        $requestData = $request->all();

        return is_array($requestData['query'] ?? null) ? $requestData['query'] : [];
    }
}
