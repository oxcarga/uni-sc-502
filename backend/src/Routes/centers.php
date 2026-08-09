<?php

declare(strict_types=1);

use App\Controllers\CenterController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/centers', [CenterController::class, 'index'])
        ->add(AuthMiddleware::class);
    $app->get('/centers/{id}', [CenterController::class, 'show'])
        ->add(AuthMiddleware::class);
};
