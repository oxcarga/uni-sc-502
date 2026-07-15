<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use Slim\App;

return function (App $app) {
    $app->post('/auth/login', [AuthController::class, 'login']);
};
