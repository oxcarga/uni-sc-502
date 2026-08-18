<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

class Connection
{
    public static function get(): PDO
    {
        $host = getenv('MYSQL_HOST') ?: 'db';
        $user = getenv('MYSQL_USER') ?: 'pulso_user';
        $pass = getenv('MYSQL_PASSWORD') ?: 'pulso_password';
        $dbname = getenv('MYSQL_DATABASE') ?: 'pulso_solidario';

        return new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
            ]
        );
    }
}
