<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Sesión de servidor (cookie HttpOnly). Fuente de verdad de autenticación.
 */
class Session
{
    private const COOKIE_NAME = 'PULSOSESSID';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $environment = strtolower(trim((string) (getenv('APP_ENV') ?: 'local')));
        $secure = $environment === 'production';

        session_name(self::COOKIE_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function login(int $userId): void
    {
        self::start();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    public static function logout(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'] ?? '/',
                    'domain' => $params['domain'] ?? '',
                    'secure' => (bool) ($params['secure'] ?? false),
                    'httponly' => (bool) ($params['httponly'] ?? true),
                    'samesite' => $params['samesite'] ?? 'Lax',
                ]
            );
        }

        session_destroy();
    }

    public static function userId(): ?int
    {
        self::start();

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $userId = (int) $_SESSION['user_id'];

        return $userId > 0 ? $userId : null;
    }
}
