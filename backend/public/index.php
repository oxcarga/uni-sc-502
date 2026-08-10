<?php

// Evita conversiones implícitas de tipos
declare(strict_types=1);

use App\Controllers\AppointmentController;
use App\Controllers\AuthController;
use App\Controllers\CenterController;
use App\Controllers\DonorProfileController;
use App\Controllers\UserController;
use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Repositories\AppointmentRepository;
use App\Repositories\BankProfileRepository;
use App\Repositories\DonationCenterRepository;
use App\Repositories\DonationPolicyRepository;
use App\Repositories\DonationRepository;
use App\Repositories\DonorProfileRepository;
use App\Repositories\EmailVerificationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\EmailVerificationService;
use App\Support\Session;
use App\Support\SmtpMailer;
use DI\Container;
use Slim\Factory\AppFactory;
use Psr\Log\LoggerInterface;

// Carga las dependencias
require __DIR__ . '/../vendor/autoload.php';

// Sesión de servidor (cookie HttpOnly) antes de atender la request
Session::start();

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
    DonorProfileRepository::class,
    fn ($c) => new DonorProfileRepository($c->get(\PDO::class))
);
$container->set(
    DonationCenterRepository::class,
    fn ($c) => new DonationCenterRepository($c->get(\PDO::class))
);
$container->set(
    AppointmentRepository::class,
    fn ($c) => new AppointmentRepository($c->get(\PDO::class))
);
$container->set(
    DonationRepository::class,
    fn ($c) => new DonationRepository($c->get(\PDO::class))
);
$container->set(
    BankProfileRepository::class,
    fn ($c) => new BankProfileRepository($c->get(\PDO::class))
);
$container->set(
    DonationPolicyRepository::class,
    fn ($c) => new DonationPolicyRepository($c->get(\PDO::class))
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
$container->set(
    DonorProfileController::class,
    fn ($c) => new DonorProfileController(
        $c->get(UserRepository::class),
        $c->get(DonorProfileRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    CenterController::class,
    fn ($c) => new CenterController(
        $c->get(DonationCenterRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AppointmentController::class,
    fn ($c) => new AppointmentController(
        $c->get(\PDO::class),
        $c->get(AppointmentRepository::class),
        $c->get(DonationRepository::class),
        $c->get(DonationCenterRepository::class),
        $c->get(DonorProfileRepository::class),
        $c->get(BankProfileRepository::class),
        $c->get(DonationPolicyRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AuthMiddleware::class,
    fn ($c) => new AuthMiddleware($c->get(UserRepository::class))
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
(require __DIR__ . '/../src/Routes/donor.php')($app);
(require __DIR__ . '/../src/Routes/bank.php')($app);
(require __DIR__ . '/../src/Routes/centers.php')($app);

$app->run();
