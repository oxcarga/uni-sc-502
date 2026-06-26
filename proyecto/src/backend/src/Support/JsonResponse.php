<?php

declare(strict_types=1);

namespace App\Support;

use Psr\Http\Message\ResponseInterface as Response;

class JsonResponse
{
    public static function success(
        Response $response,
        mixed $data = null,
        string $message = '',
        int $status = 200
    ): Response {
        $payload = ['success' => true];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($message !== '') {
            $payload['message'] = $message;
        }

        $response->getBody()->write((string) json_encode($payload));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public static function error(Response $response, string $error, int $status = 400): Response
    {
        $response->getBody()->write((string) json_encode([
            'success' => false,
            'error' => $error,
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
