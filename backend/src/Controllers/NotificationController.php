<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\NotificationRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController
{
    public function __construct(
        private readonly NotificationRepository $notifications,
        private Logger $logger
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        try {
            $limit = (int) ($request->getQueryParams()['limit'] ?? 50);
            $userId = (int) $auth['id'];
            $list = $this->notifications->findByUser($userId, $limit);
            $unread = $this->notifications->countUnread($userId);

            return JsonResponse::success($response, [
                'unread_count' => $unread,
                'notifications' => $list,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar notificaciones.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar notificaciones.', 500);
        }
    }

    public function markRead(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return JsonResponse::error($response, 'Notificación no encontrada.', 404);
        }

        try {
            $existing = $this->notifications->findByIdForUser($id, (int) $auth['id']);
            if ($existing === null) {
                return JsonResponse::error($response, 'Notificación no encontrada.', 404);
            }

            $updated = $this->notifications->markRead($id, (int) $auth['id']);

            return JsonResponse::success($response, $updated, 'Notificación marcada como leída.');
        } catch (PDOException $error) {
            $this->logger->error('Error al marcar notificación.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al marcar la notificación.', 500);
        }
    }
}
