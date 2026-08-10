<?php

declare(strict_types=1);

use App\Controllers\AdminAuditController;
use App\Controllers\AdminPolicyController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/admin/policies', [AdminPolicyController::class, 'index'])
        ->add(AuthMiddleware::class);
    $app->put('/admin/policies', [AdminPolicyController::class, 'update'])
        ->add(AuthMiddleware::class);
    $app->get('/admin/audit-log', [AdminAuditController::class, 'index'])
        ->add(AuthMiddleware::class);
};
