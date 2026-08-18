<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\DonationCenterRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CenterController
{
    public function __construct(
        private readonly DonationCenterRepository $centers,
        private Logger $logger
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');

        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        $role = (string) ($auth['role'] ?? '');

        $includeInactive = $role === 'admin'
            && (
                ($request->getQueryParams()['all'] ?? '') === '1'
                || ($request->getQueryParams()['include_inactive'] ?? '') === '1'
            );

        try {
            $list = $this->centers->findAll(activeOnly: !$includeInactive);

            return JsonResponse::success($response, $list);
        } catch (PDOException $error) {
            $this->logger->error(
                'Error al listar centros.',
                ['error' => $error->getMessage()]
            );

            return JsonResponse::error(
                $response,
                'Error al listar centros.',
                500
            );
        }
    }

    public function show(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $auth = $request->getAttribute('auth_user');

        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            return JsonResponse::error(
                $response,
                'Centro no encontrado.',
                404
            );
        }

        $role = (string) ($auth['role'] ?? '');
        $activeOnly = $role !== 'admin';

        try {
            $center = $this->centers->findById(
                $id,
                activeOnly: $activeOnly
            );

            if ($center === null) {
                return JsonResponse::error(
                    $response,
                    'Centro no encontrado.',
                    404
                );
            }

            return JsonResponse::success($response, $center);
        } catch (PDOException $error) {
            $this->logger->error(
                'Error al consultar centro.',
                ['error' => $error->getMessage()]
            );

            return JsonResponse::error(
                $response,
                'Error al consultar el centro.',
                500
            );
        }
    }

    public function create(
        Request $request,
        Response $response
    ): Response {
        $auth = $request->getAttribute('auth_user');

        if (!is_array($auth)) {
            return JsonResponse::error(
                $response,
                'No autenticado.',
                401
            );
        }

        if (($auth['role'] ?? '') !== 'admin') {
            return JsonResponse::error(
                $response,
                'No autorizado.',
                403
            );
        }

        $body = (array) $request->getParsedBody();

        $validation = $this->validateCenterData($body);

        if ($validation['error'] !== null) {
            return JsonResponse::error(
                $response,
                $validation['error'],
                422
            );
        }

        try {
            $center = $this->centers->create(
                $validation['data']
            );

            return JsonResponse::success(
                $response,
                $center,
                'Banco creado correctamente.',
                201
            );
        } catch (PDOException $error) {
            $this->logger->error(
                'Error al crear centro.',
                ['error' => $error->getMessage()]
            );

            if (
                (string) $error->getCode() === '23000'
                && $validation['data']['code'] !== null
            ) {
                return JsonResponse::error(
                    $response,
                    'Ya existe un banco con ese código.',
                    422
                );
            }

            return JsonResponse::error(
                $response,
                'Error al crear el banco.',
                500
            );
        }
    }

    public function update(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $auth = $request->getAttribute('auth_user');

        if (!is_array($auth)) {
            return JsonResponse::error(
                $response,
                'No autenticado.',
                401
            );
        }

        if (($auth['role'] ?? '') !== 'admin') {
            return JsonResponse::error(
                $response,
                'No autorizado.',
                403
            );
        }

        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            return JsonResponse::error(
                $response,
                'Centro no encontrado.',
                404
            );
        }

        $body = (array) $request->getParsedBody();

        $validation = $this->validateCenterData($body);

        if ($validation['error'] !== null) {
            return JsonResponse::error(
                $response,
                $validation['error'],
                422
            );
        }

        try {
            $existing = $this->centers->findById(
                $id,
                activeOnly: false
            );

            if ($existing === null) {
                return JsonResponse::error(
                    $response,
                    'Centro no encontrado.',
                    404
                );
            }

            $updated = $this->centers->update(
                $id,
                $validation['data']
            );

            return JsonResponse::success(
                $response,
                $updated,
                'Banco actualizado correctamente.'
            );
        } catch (PDOException $error) {
            $this->logger->error(
                'Error al actualizar centro.',
                ['error' => $error->getMessage()]
            );

            if (
                (string) $error->getCode() === '23000'
                && $validation['data']['code'] !== null
            ) {
                return JsonResponse::error(
                    $response,
                    'Ya existe un banco con ese código.',
                    422
                );
            }

            return JsonResponse::error(
                $response,
                'Error al actualizar el banco.',
                500
            );
        }
    }

    public function updateActive(
        Request $request,
        Response $response,
        array $args
    ): Response {
        $auth = $request->getAttribute('auth_user');

        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        if (($auth['role'] ?? '') !== 'admin') {
            return JsonResponse::error(
                $response,
                'No autorizado.',
                403
            );
        }

        $id = (int) ($args['id'] ?? 0);

        if ($id <= 0) {
            return JsonResponse::error(
                $response,
                'Centro no encontrado.',
                404
            );
        }

        $body = (array) $request->getParsedBody();

        if (!array_key_exists('active', $body)) {
            return JsonResponse::error(
                $response,
                'El campo active es obligatorio.',
                422
            );
        }

        $active = filter_var(
            $body['active'],
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );

        if ($active === null) {
            return JsonResponse::error(
                $response,
                'El valor de active no es válido.',
                422
            );
        }

        try {
            $center = $this->centers->findById(
                $id,
                activeOnly: false
            );

            if ($center === null) {
                return JsonResponse::error(
                    $response,
                    'Centro no encontrado.',
                    404
                );
            }

            $updated = $this->centers->updateActive(
                $id,
                $active
            );

            return JsonResponse::success(
                $response,
                $updated,
                $active
                    ? 'Centro activado correctamente.'
                    : 'Centro desactivado correctamente.'
            );
        } catch (PDOException $error) {
            $this->logger->error(
                'Error al actualizar estado del centro.',
                ['error' => $error->getMessage()]
            );

            return JsonResponse::error(
                $response,
                'Error al actualizar el centro.',
                500
            );
        }
    }

    /**
     * @param array<string, mixed> $body
     * @return array{
     *     error: ?string,
     *     data: array<string, mixed>
     * }
     */
    private function validateCenterData(array $body): array
    {
        $name = trim((string) ($body['name'] ?? ''));
        $address = trim((string) ($body['address'] ?? ''));

        if ($name === '') {
            return [
                'error' => 'El nombre del banco es obligatorio.',
                'data' => [],
            ];
        }

        if ($address === '') {
            return [
                'error' => 'La dirección del banco es obligatoria.',
                'data' => [],
            ];
        }

        $email = trim(
            (string) ($body['contact_email'] ?? '')
        );

        if (
            $email !== ''
            && filter_var(
                $email,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            return [
                'error' => 'El correo de contacto no es válido.',
                'data' => [],
            ];
        }

        $active = true;

        if (array_key_exists('active', $body)) {
            $parsedActive = filter_var(
                $body['active'],
                FILTER_VALIDATE_BOOL,
                FILTER_NULL_ON_FAILURE
            );

            if ($parsedActive === null) {
                return [
                    'error' => 'El valor de active no es válido.',
                    'data' => [],
                ];
            }

            $active = $parsedActive;
        }

        return [
            'error' => null,
            'data' => [
                'code' => $this->nullableString(
                    $body['code'] ?? null
                ),
                'name' => $name,
                'description' => $this->nullableString(
                    $body['description'] ?? null
                ),
                'address' => $address,
                'province' => $this->nullableString(
                    $body['province'] ?? null
                ),
                'canton' => $this->nullableString(
                    $body['canton'] ?? null
                ),
                'region' => $this->nullableString(
                    $body['region'] ?? null
                ),
                'contact_name' => $this->nullableString(
                    $body['contact_name'] ?? null
                ),
                'contact_phone' => $this->nullableString(
                    $body['contact_phone'] ?? null
                ),
                'contact_email' =>
                    $email !== '' ? $email : null,
                'active' => $active,
            ],
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text !== '' ? $text : null;
    }
}