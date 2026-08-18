<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->post('/users', [UserController::class, 'create']);

    $app->get('/users', [UserController::class, 'index'])
        ->add(AuthMiddleware::class);
    $app->get('/users/{id}', [UserController::class, 'show'])
        ->add(AuthMiddleware::class);
    $app->put('/users/{id}', [UserController::class, 'update'])
        ->add(AuthMiddleware::class);
    $app->delete('/users/{id}', [UserController::class, 'delete'])
        ->add(AuthMiddleware::class);
};
