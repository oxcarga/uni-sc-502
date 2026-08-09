<?php

declare(strict_types=1);

use App\Controllers\DonorProfileController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/donor/profile', [DonorProfileController::class, 'show'])
        ->add(AuthMiddleware::class);
    $app->put('/donor/profile', [DonorProfileController::class, 'update'])
        ->add(AuthMiddleware::class);
};
