<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use Slim\App;

return function (App $app) {
    $app->post('/auth/login', [AuthController::class, 'login']);
    $app->post('/auth/confirm-email', [AuthController::class, 'confirmEmail']);
    $app->get('/auth/confirm-email', [AuthController::class, 'confirmEmail']);
    $app->post('/auth/resend-confirmation', [AuthController::class, 'resendConfirmation']);
};
