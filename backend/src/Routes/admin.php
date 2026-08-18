<?php

declare(strict_types=1);

use App\Controllers\AdminAuditController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminPolicyController;
use App\Controllers\AdminUserController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->add(AuthMiddleware::class);

    $app->get('/admin/users', [AdminUserController::class, 'index'])
        ->add(AuthMiddleware::class);
    $app->post('/admin/users', [AdminUserController::class, 'create'])
        ->add(AuthMiddleware::class);
    $app->patch('/admin/users/{id}', [AdminUserController::class, 'patch'])
        ->add(AuthMiddleware::class);

    $app->get('/admin/policies', [AdminPolicyController::class, 'index'])
        ->add(AuthMiddleware::class);

    $app->put('/admin/policies', [AdminPolicyController::class, 'update'])
        ->add(AuthMiddleware::class);

    $app->get('/admin/audit-log', [AdminAuditController::class, 'index'])
        ->add(AuthMiddleware::class);
};