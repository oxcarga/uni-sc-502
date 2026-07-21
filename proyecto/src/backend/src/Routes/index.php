<?php

declare(strict_types=1);

use App\Support\JsonResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->get('/', function (Request $request, Response $response) {
        return JsonResponse::success($response, [
            'message' => 'Pulso Solidario API',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    });

    // Config pública para el FE (sin secretos). APP_ENV viene del contenedor / .env
    $app->get('/config', function (Request $request, Response $response) {
        $environment = strtolower(trim((string) (getenv('APP_ENV') ?: 'local')));

        return JsonResponse::success($response, [
            'environment' => $environment,
        ]);
    });
};