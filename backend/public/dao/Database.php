<?php

declare(strict_types=1);

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = getenv('MYSQL_HOST') ?: 'db';
        $user = getenv('MYSQL_USER') ?: 'pulso_user';
        $pass = getenv('MYSQL_PASSWORD') ?: 'pulso_password';
        $dbname = getenv('MYSQL_DATABASE') ?: 'pulso_solidario';

        self::$connection = new PDO(
            "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return self::$connection;
    }
}
