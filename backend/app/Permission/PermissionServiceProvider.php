<?php

namespace App\Permission;

use App\Support\AppContainer;
use PDO;

class PermissionServiceProvider
{
    public function register(AppContainer $container): void
    {
        $database = $container->get('database');
        $database = $database instanceof PDO ? $database : null;

        $repository = new PermissionRepository($database);
        $service = new PermissionService($repository);
        $controller = new PermissionController($service);

        $container->set(PermissionRepository::class, $repository);
        $container->set(PermissionRepositoryInterface::class, $repository);
        $container->set(PermissionService::class, $service);
        $container->set(PermissionServiceInterface::class, $service);
        $container->set(PermissionController::class, $controller);

        $container->set('permission.repository', $repository);
        $container->set('permission.service', $service);
        $container->set('permission.controller', $controller);
    }
}
