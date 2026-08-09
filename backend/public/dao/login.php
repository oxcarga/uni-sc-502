<?php

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Usuario.php';
require_once __DIR__ . '/UsuarioDAO.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$email = strtolower(trim((string) ($_POST['email'] ?? '')));
$password = (string) ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    header('Location: login.html?error=' . urlencode('Debes indicar correo y contraseña.'));
    exit;
}

try {
    $dao = new UsuarioDAO(Database::getConnection());
    $usuario = $dao->findByEmail($email);

    if ($usuario === null || !password_verify($password, $usuario->getPasswordHash())) {
        header('Location: login.html?error=' . urlencode('Correo o contraseña incorrectos.'));
        exit;
    }

    if (!$usuario->isActive()) {
        header('Location: login.html?error=' . urlencode('Correo o contraseña incorrectos.'));
        exit;
    }

    if (!$usuario->isEmailConfirmed()) {
        header('Location: login.html?error=' . urlencode('Debes confirmar tu correo antes de iniciar sesión.'));
        exit;
    }

    $_SESSION['usuario_id'] = $usuario->getId();
    $_SESSION['usuario_nombre'] = $usuario->getFullName();
    $_SESSION['usuario_email'] = $usuario->getEmail();

    header('Location: login.html?ok=' . urlencode('Bienvenido, ' . $usuario->getFullName()));
    exit;
} catch (PDOException $error) {
    header('Location: login.html?error=' . urlencode('Error al iniciar sesión. Intenta de nuevo.'));
    exit;
}
