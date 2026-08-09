<?php

declare(strict_types=1);

use App\Controllers\UserController;
use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->get('/users', [UserController::class, 'index']);
    $app->get('/users/{id}', [UserController::class, 'show']);
    $app->post('/users', [UserController::class, 'create']);
    $app->put('/users/{id}', [UserController::class, 'update']);
    $app->delete('/users/{id}', [UserController::class, 'delete']);
};
