<?php

namespace App\User;

interface UserServiceInterface
{
    /**
     * @return array{items: list<User>, total: int, page: int, per_page: int}
     */
    public function list(UserListRequest $request): array;

    public function find(int $id): ?User;

    public function create(CreateUserRequest $request): ?User;

    public function update(UpdateUserRequest $request): ?User;

    public function delete(int $id): bool;
}
