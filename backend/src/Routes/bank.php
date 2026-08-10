<?php

declare(strict_types=1);

use App\Controllers\AppointmentController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/bank/appointments', [AppointmentController::class, 'indexBank'])
        ->add(AuthMiddleware::class);
    $app->post('/bank/appointments/{id}/complete', [AppointmentController::class, 'complete'])
        ->add(AuthMiddleware::class);
};
