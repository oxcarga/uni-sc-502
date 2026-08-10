<?php

declare(strict_types=1);

use App\Controllers\AchievementController;
use App\Controllers\AppointmentController;
use App\Controllers\DonorProfileController;
use App\Middleware\AuthMiddleware;
use Slim\App;

return function (App $app) {
    $app->get('/donor/profile', [DonorProfileController::class, 'show'])
        ->add(AuthMiddleware::class);
    $app->put('/donor/profile', [DonorProfileController::class, 'update'])
        ->add(AuthMiddleware::class);

    $app->get('/donor/appointments', [AppointmentController::class, 'indexDonor'])
        ->add(AuthMiddleware::class);
    $app->post('/donor/appointments', [AppointmentController::class, 'createDonor'])
        ->add(AuthMiddleware::class);
    $app->patch('/donor/appointments/{id}', [AppointmentController::class, 'patchDonor'])
        ->add(AuthMiddleware::class);

    $app->get('/donor/donations', [AppointmentController::class, 'indexDonorDonations'])
        ->add(AuthMiddleware::class);

    $app->get('/donor/achievements', [AchievementController::class, 'indexDonor'])
        ->add(AuthMiddleware::class);
};
