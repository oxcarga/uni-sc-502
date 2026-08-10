<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AppointmentRepository;
use App\Repositories\BankProfileRepository;
use App\Repositories\DonationCenterRepository;
use App\Repositories\DonationPolicyRepository;
use App\Repositories\DonationRepository;
use App\Repositories\DonorProfileRepository;
use App\Repositories\InventoryRepository;
use App\Support\JsonResponse;
use Monolog\Logger;
use PDO;
use PDOException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AppointmentController
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AppointmentRepository $appointments,
        private readonly DonationRepository $donations,
        private readonly DonationCenterRepository $centers,
        private readonly DonorProfileRepository $profiles,
        private readonly BankProfileRepository $bankProfiles,
        private readonly DonationPolicyRepository $policies,
        private readonly InventoryRepository $inventory,
        private Logger $logger
    ) {
    }

    public function indexDonor(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'donor') {
            return JsonResponse::error($response, 'Solo donantes pueden listar sus citas.', 403);
        }

        try {
            $list = $this->appointments->findByDonor((int) $auth['id']);
            return JsonResponse::success($response, $list);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar citas del donante.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar citas.', 500);
        }
    }

    public function createDonor(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'donor') {
            return JsonResponse::error($response, 'Solo donantes pueden agendar citas.', 403);
        }

        $donorId = (int) $auth['id'];
        $body = (array) $request->getParsedBody();
        $centerId = (int) ($body['center_id'] ?? 0);
        $scheduledAt = trim((string) ($body['scheduled_at'] ?? ''));
        $notes = $this->nullableString($body['notes'] ?? null);

        if ($centerId <= 0 || $scheduledAt === '') {
            return JsonResponse::error($response, 'Indica centro y fecha/hora de la cita.', 422);
        }

        $scheduled = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $scheduledAt)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i', $scheduledAt)
            ?: \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $scheduledAt);

        if ($scheduled === false) {
            return JsonResponse::error($response, 'La fecha/hora no es válida.', 422);
        }

        if ($scheduled <= new \DateTimeImmutable('now')) {
            return JsonResponse::error($response, 'La cita debe ser en el futuro.', 422);
        }

        try {
            $center = $this->centers->findById($centerId, activeOnly: true);
            if ($center === null) {
                return JsonResponse::error($response, 'Centro no encontrado o inactivo.', 404);
            }

            $validation = $this->validateScheduleAgainstCenter($center, $scheduled);
            if ($validation !== null) {
                return JsonResponse::error($response, $validation, 422);
            }

            $profile = $this->profiles->ensureForUser($donorId);
            if (empty($profile['blood_type'])) {
                return JsonResponse::error(
                    $response,
                    'Completa tu tipo de sangre en el perfil antes de agendar.',
                    422
                );
            }

            $intervalDays = $this->policies->donorIntervalDays();
            if (!empty($profile['last_donation_at'])) {
                $last = new \DateTimeImmutable((string) $profile['last_donation_at']);
                $eligibleFrom = $last->modify("+{$intervalDays} days");
                if ($scheduled < $eligibleFrom) {
                    return JsonResponse::error(
                        $response,
                        "Debes esperar {$intervalDays} días entre donaciones. Elegible a partir del "
                            . $eligibleFrom->format('Y-m-d') . '.',
                        422
                    );
                }
            }

            if ($this->appointments->hasOpenFutureForDonor($donorId)) {
                return JsonResponse::error(
                    $response,
                    'Ya tienes una cita pendiente o confirmada. Cancélala antes de agendar otra.',
                    422
                );
            }

            $dayCount = $this->appointments->countForCenterOnDate(
                $centerId,
                $scheduled->format('Y-m-d')
            );
            $capacity = (int) ($center['daily_capacity'] ?? 0);
            if ($capacity > 0 && $dayCount >= $capacity) {
                return JsonResponse::error($response, 'El centro no tiene cupo para esa fecha.', 422);
            }

            $created = $this->appointments->create(
                $donorId,
                $centerId,
                $scheduled->format('Y-m-d H:i:s'),
                $notes
            );

            return JsonResponse::success($response, $created, 'Cita agendada.', 201);
        } catch (PDOException $error) {
            $this->logger->error('Error al agendar cita.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al agendar la cita.', 500);
        }
    }

    public function patchDonor(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'donor') {
            return JsonResponse::error($response, 'Solo donantes pueden modificar sus citas.', 403);
        }

        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return JsonResponse::error($response, 'Cita no encontrada.', 404);
        }

        $body = (array) $request->getParsedBody();
        $status = trim((string) ($body['status'] ?? ''));
        if ($status !== 'cancelled') {
            return JsonResponse::error($response, 'Solo puedes cancelar la cita (status=cancelled).', 422);
        }

        try {
            $appointment = $this->appointments->findById($id);
            if ($appointment === null || (int) $appointment['donor_id'] !== (int) $auth['id']) {
                return JsonResponse::error($response, 'Cita no encontrada.', 404);
            }

            if (!in_array($appointment['status'], ['pending', 'confirmed'], true)) {
                return JsonResponse::error($response, 'Esta cita ya no se puede cancelar.', 422);
            }

            $updated = $this->appointments->updateStatus($id, 'cancelled');
            return JsonResponse::success($response, $updated, 'Cita cancelada.');
        } catch (PDOException $error) {
            $this->logger->error('Error al cancelar cita.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al cancelar la cita.', 500);
        }
    }

    public function indexBank(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        $role = (string) ($auth['role'] ?? '');
        if (!in_array($role, ['bank', 'admin'], true)) {
            return JsonResponse::error($response, 'No autorizado.', 403);
        }

        try {
            $centerId = $this->resolveCenterId($auth, $request);
            if ($centerId === null) {
                return JsonResponse::error($response, 'No hay centro asociado a esta cuenta.', 422);
            }

            $list = $this->appointments->findByCenter($centerId);
            return JsonResponse::success($response, [
                'center_id' => $centerId,
                'appointments' => $list,
            ]);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar citas del banco.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar citas.', 500);
        }
    }

    public function complete(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth)) {
            return JsonResponse::error($response, 'No autenticado.', 401);
        }

        $role = (string) ($auth['role'] ?? '');
        if (!in_array($role, ['bank', 'admin'], true)) {
            return JsonResponse::error($response, 'No autorizado.', 403);
        }

        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return JsonResponse::error($response, 'Cita no encontrada.', 404);
        }

        try {
            $centerId = $this->resolveCenterId($auth, $request);
            if ($centerId === null && $role === 'bank') {
                return JsonResponse::error($response, 'No hay centro asociado a esta cuenta.', 422);
            }

            $this->pdo->beginTransaction();

            $locked = $this->appointments->findByIdForUpdate($id);
            if ($locked === null) {
                $this->pdo->rollBack();
                return JsonResponse::error($response, 'Cita no encontrada.', 404);
            }

            if ($role === 'bank' && (int) $locked['center_id'] !== $centerId) {
                $this->pdo->rollBack();
                return JsonResponse::error($response, 'La cita no pertenece a tu centro.', 403);
            }

            if (!in_array($locked['status'], ['pending', 'confirmed'], true)) {
                $this->pdo->rollBack();
                return JsonResponse::error($response, 'Solo se pueden completar citas pendientes o confirmadas.', 422);
            }

            if ($this->donations->existsForAppointment($id)) {
                $this->pdo->rollBack();
                return JsonResponse::error($response, 'Esta cita ya tiene una donación registrada.', 422);
            }

            $profile = $this->profiles->ensureForUser((int) $locked['donor_id']);
            $bloodType = $profile['blood_type'] ?? null;
            if ($bloodType === null || $bloodType === '') {
                $this->pdo->rollBack();
                return JsonResponse::error(
                    $response,
                    'El donante no tiene tipo de sangre en su perfil.',
                    422
                );
            }

            $donatedAt = (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s');
            $this->appointments->updateStatus($id, 'completed');
            $created = $this->donations->createFromAppointment(
                (int) $locked['donor_id'],
                (int) $locked['center_id'],
                $id,
                (string) $bloodType,
                $donatedAt
            );
            $this->profiles->touchLastDonation((int) $locked['donor_id'], $donatedAt);

            $stock = $this->inventory->applyChange(
                (int) $locked['center_id'],
                (string) $bloodType,
                1,
                1,
                'receipt',
                (int) $auth['id'],
                'Recepción por donación completada',
                (int) $created['donation']['id'],
                (int) $created['blood_unit']['id']
            );

            $this->pdo->commit();

            $appointment = $this->appointments->findById($id);

            return JsonResponse::success($response, [
                'appointment' => $appointment,
                'donation' => $created['donation'],
                'blood_unit' => $created['blood_unit'],
                'inventory' => $stock['inventory'],
                'movement_id' => $stock['movement_id'],
            ], 'Donación registrada e inventario actualizado.');
        } catch (PDOException $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            $this->logger->error('Error al completar cita.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al completar la cita.', 500);
        }
    }

    public function indexDonorDonations(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth_user');
        if (!is_array($auth) || ($auth['role'] ?? '') !== 'donor') {
            return JsonResponse::error($response, 'Solo donantes pueden ver su historial.', 403);
        }

        try {
            $list = $this->donations->findByDonor((int) $auth['id']);
            return JsonResponse::success($response, $list);
        } catch (PDOException $error) {
            $this->logger->error('Error al listar donaciones.', ['error' => $error->getMessage()]);
            return JsonResponse::error($response, 'Error al listar donaciones.', 500);
        }
    }

    /** @param array<string, mixed> $auth */
    private function resolveCenterId(array $auth, Request $request): ?int
    {
        $role = (string) ($auth['role'] ?? '');
        if ($role === 'bank') {
            return $this->bankProfiles->findCenterIdByUserId((int) $auth['id']);
        }

        if ($role === 'admin') {
            $centerId = (int) ($request->getQueryParams()['center_id'] ?? 0);
            if ($centerId > 0) {
                return $centerId;
            }
            // Fallback demo: primer centro activo
            $centers = $this->centers->findAll(activeOnly: true);
            return isset($centers[0]['id']) ? (int) $centers[0]['id'] : null;
        }

        return null;
    }

    /** @param array<string, mixed> $center */
    private function validateScheduleAgainstCenter(array $center, \DateTimeImmutable $scheduled): ?string
    {
        $open = $center['open_time'] ?? null;
        $close = $center['close_time'] ?? null;
        if ($open && $close) {
            $time = $scheduled->format('H:i:s');
            $openStr = strlen((string) $open) === 5 ? $open . ':00' : (string) $open;
            $closeStr = strlen((string) $close) === 5 ? $close . ':00' : (string) $close;
            if ($time < $openStr || $time > $closeStr) {
                return 'La hora está fuera del horario del centro ('
                    . substr($openStr, 0, 5) . '–' . substr($closeStr, 0, 5) . ').';
            }
        }

        return null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
