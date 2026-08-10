<?php

declare(strict_types=1);

use App\Controllers\NotificationController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/notifications', [NotificationController::class, 'index'])
        ->add(AuthMiddleware::class);
    $app->post('/notifications/{id}/read', [NotificationController::class, 'markRead'])
        ->add(AuthMiddleware::class);
};
