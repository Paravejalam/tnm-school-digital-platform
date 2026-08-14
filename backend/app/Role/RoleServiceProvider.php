<?php

namespace App\Role;

use App\Support\AppContainer;
use PDO;

class RoleServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $repository = new RoleRepository($database);
        $service = new RoleService($repository);
        $controller = new RoleController($service);

        $container->set(RoleRepository::class, $repository);
        $container->set(RoleRepositoryInterface::class, $repository);
        $container->set(RoleService::class, $service);
        $container->set(RoleServiceInterface::class, $service);
        $container->set(RoleController::class, $controller);

        $container->set('role.repository', $repository);
        $container->set('role.service', $service);
        $container->set('role.controller', $controller);
    }
}
