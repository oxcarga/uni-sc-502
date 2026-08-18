<?php

declare(strict_types=1);

use App\Controllers\AlertController;
use App\Controllers\AppointmentController;
use App\Controllers\BankDonorController;
use App\Controllers\CenterController;
use App\Controllers\InventoryController;
use App\Controllers\RequestController;
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

    $app->get('/bank/requests', [RequestController::class, 'index'])
        ->add(AuthMiddleware::class);
    $app->post('/bank/requests/{id}/assign', [RequestController::class, 'assign'])
        ->add(AuthMiddleware::class);

    $app->get('/bank/alerts', [AlertController::class, 'index'])
        ->add(AuthMiddleware::class);
    $app->post('/bank/alerts/{id}/resolve', [AlertController::class, 'resolve'])
        ->add(AuthMiddleware::class);

    $app->get('/bank/donors/compatible', [BankDonorController::class, 'compatible'])
        ->add(AuthMiddleware::class);

    $app->get('/bank/center', [CenterController::class, 'mine'])
        ->add(AuthMiddleware::class);
};
