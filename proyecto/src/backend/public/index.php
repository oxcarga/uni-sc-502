<?php

// Evita conversiones implícitas de tipos
declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\UserController;
use App\Database\Connection;
use App\Repositories\EmailVerificationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\EmailVerificationService;
use App\Support\SmtpMailer;
use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Log\LoggerInterface;

// Carga las dependencias
require __DIR__ . '/../vendor/autoload.php';

// Crea el contenedor de dependencias
$container = new Container();
// Inyeccion conexion a la base de datos
$container->set(\PDO::class, fn () => Connection::get());

// Inyeccion repositorios
$container->set(UserRepository::class, fn ($c) => new UserRepository($c->get(\PDO::class)));
$container->set(
    EmailVerificationTokenRepository::class,
    fn ($c) => new EmailVerificationTokenRepository($c->get(\PDO::class))
);

$container->set(
    SmtpMailer::class,
    fn () => new SmtpMailer(
        (string) (getenv('SMTP_HOST') ?: 'mailhog'),
        (int) (getenv('SMTP_PORT') ?: 1025),
        (string) (getenv('MAIL_FROM') ?: 'noreply@pulso-solidario.local'),
    )
);

$container->set(
    EmailVerificationService::class,
    fn ($c) => new EmailVerificationService(
        $c->get(\PDO::class),
        $c->get(UserRepository::class),
        $c->get(EmailVerificationTokenRepository::class),
        $c->get(SmtpMailer::class),
        $c->get(LoggerInterface::class),
        (string) (getenv('APP_URL') ?: 'http://localhost:3000'),
    )
);

// Inyeccion controladores
$container->set(
    UserController::class,
    fn ($c) => new UserController(
        $c->get(UserRepository::class),
        $c->get(EmailVerificationService::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AuthController::class,
    fn ($c) => new AuthController(
        $c->get(UserRepository::class),
        $c->get(EmailVerificationService::class),
        $c->get(LoggerInterface::class)
    )
);

(require __DIR__ . '/../src/Support/Logger.php')($container);

// Configura el contenedor de dependencias
AppFactory::setContainer($container);

// Creacion de la aplicacion Slim
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->setBasePath('/api');

(require __DIR__ . '/../src/Routes/index.php')($app);
(require __DIR__ . '/../src/Routes/auth.php')($app);
(require __DIR__ . '/../src/Routes/users.php')($app);

$app->run();
