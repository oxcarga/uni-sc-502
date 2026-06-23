# Backend - PHP

Place your PHP backend files here.

## Structure

```
backend/
├── index.php           # Main entry point
├── api/
│   └── endpoint.php    # API endpoints
└── config/
    └── database.php    # Database configuration
```

## Database Connection Example

```php
<?php
$host = getenv('MYSQL_HOST') ?: 'db';
$user = getenv('MYSQL_USER') ?: 'pulso_user';
$pass = getenv('MYSQL_PASSWORD') ?: 'pulso_password';
$dbname = getenv('MYSQL_DATABASE') ?: 'pulso_solidario';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $user,
        $pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

## Accessing from Frontend

Make API calls to `http://localhost:8000/api/endpoint.php`
