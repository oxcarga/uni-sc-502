<?php

declare(strict_types=1);

use App\Controllers\AppointmentController;
use App\Controllers\InventoryController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/bank/appointments', [AppointmentController::class, 'indexBank'])
        ->add(AuthMiddleware::class);
    $app->post('/bank/appointments/{id}/complete', [AppointmentController::class, 'complete'])
        ->add(AuthMiddleware::class);

    $app->get('/bank/inventory/movements', [InventoryController::class, 'movements'])
        ->add(AuthMiddleware::class);
    $app->get('/bank/inventory', [InventoryController::class, 'index'])
        ->add(AuthMiddleware::class);
    $app->post('/bank/inventory/receipts', [InventoryController::class, 'receipt'])
        ->add(AuthMiddleware::class);
    $app->post('/bank/inventory/adjustments', [InventoryController::class, 'adjustment'])
        ->add(AuthMiddleware::class);
};
