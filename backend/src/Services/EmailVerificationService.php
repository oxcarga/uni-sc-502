<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EmailVerificationTokenRepository;
use App\Repositories\UserRepository;
use App\Support\SmtpMailer;
use DateInterval;
use DateTimeImmutable;
use Monolog\Logger;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

class EmailVerificationService
{
    private const TOKEN_TTL_HOURS = 24;

    public function __construct(
        private readonly PDO $pdo,
        private readonly UserRepository $users,
        private readonly EmailVerificationTokenRepository $tokens,
        private readonly SmtpMailer $mailer,
        private readonly Logger $logger,
        private readonly string $appUrl,
    ) {
    }

    /**
     * Emite un token nuevo, invalida pendientes y envía el correo (o deja el enlace en logs).
     */
    public function issueAndSend(array $user): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = (new DateTimeImmutable('now'))
            ->add(new DateInterval('PT' . self::TOKEN_TTL_HOURS . 'H'))
            ->format('Y-m-d H:i:s');

        $this->pdo->beginTransaction();
        try {
            $this->tokens->invalidatePendingForUser((int) $user['id']);
            $this->tokens->create((int) $user['id'], $tokenHash, $expiresAt);
            $this->pdo->commit();
        } catch (PDOException $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        $confirmUrl = rtrim($this->appUrl, '/') . '/confirm-email/?token=' . urlencode($plainToken);
        $subject = 'Confirma tu cuenta en Pulso Solidario';
        $body = "Hola {$user['first_name']},\n\n"
            . "Confirma tu correo abriendo este enlace (válido "
            . self::TOKEN_TTL_HOURS
            . " horas):\n\n"
            . $confirmUrl
            . "\n\nSi no creaste esta cuenta, ignora este mensaje.\n";

        $this->logger->info('Enlace de confirmación de correo generado.', [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'confirm_url' => $confirmUrl,
        ]);

        try {
            $this->mailer->send((string) $user['email'], $subject, $body);
        } catch (Throwable $error) {
            // El token ya existe; el enlace queda en logs para desarrollo local.
            $this->logger->error('No se pudo enviar el correo de confirmación.', [
                'email' => $user['email'],
                'error' => $error->getMessage(),
                'confirm_url' => $confirmUrl,
            ]);
        }
    }

    /**
     * Confirma el correo con el token en claro. Devuelve el usuario público de sesión.
     *
     * @return array{id:mixed,first_name:mixed,last_name:mixed,email:mixed,role:mixed}
     */
    public function confirm(string $plainToken): array
    {
        if ($plainToken === '') {
            throw new RuntimeException('TOKEN_INVALID');
        }

        $tokenHash = hash('sha256', $plainToken);
        $token = $this->tokens->findByTokenHash($tokenHash);

        if ($token === null || $token['used_at'] !== null) {
            throw new RuntimeException('TOKEN_INVALID');
        }

        $expiresAt = new DateTimeImmutable((string) $token['expires_at']);
        if ($expiresAt < new DateTimeImmutable('now')) {
            throw new RuntimeException('TOKEN_EXPIRED');
        }

        $user = $this->users->findById((int) $token['user_id']);
        if ($user === null || !(bool) $user['active']) {
            throw new RuntimeException('TOKEN_INVALID');
        }

        $this->pdo->beginTransaction();
        try {
            $this->tokens->markUsed((int) $token['id']);
            $confirmed = $this->users->markEmailConfirmed((int) $user['id']);
            if ($confirmed === null) {
                throw new RuntimeException('TOKEN_INVALID');
            }
            $this->pdo->commit();
        } catch (PDOException $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        return UserRepository::toSession($confirmed);
    }

    /**
     * Reenvía confirmación si aplica. No revela si el email existe.
     */
    public function resendIfApplicable(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if (
            $user === null
            || !(bool) $user['active']
            || (bool) $user['email_confirmed']
        ) {
            return;
        }

        $this->issueAndSend($user);
    }
}
