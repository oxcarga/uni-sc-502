<?php

// Evita conversiones implícitas de tipos
declare(strict_types=1);

use App\Controllers\AchievementController;
use App\Controllers\AdminAuditController;
use App\Controllers\AdminDashboardController;
use App\Controllers\AdminPolicyController;
use App\Controllers\AdminUserController;
use App\Controllers\AlertController;
use App\Controllers\AppointmentController;
use App\Controllers\AuthController;
use App\Controllers\BankDonorController;
use App\Controllers\CenterController;
use App\Controllers\DonorProfileController;
use App\Controllers\InventoryController;
use App\Controllers\NotificationController;
use App\Controllers\RequestController;
use App\Controllers\UserController;
use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Repositories\AchievementRepository;
use App\Repositories\AlertRepository;
use App\Repositories\AppointmentRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\BankProfileRepository;
use App\Repositories\BloodUnitRepository;
use App\Repositories\DonationCenterRepository;
use App\Repositories\DonationPolicyRepository;
use App\Repositories\DonationRepository;
use App\Repositories\DonorProfileRepository;
use App\Repositories\EmailVerificationTokenRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\RequestRepository;
use App\Repositories\UserRepository;
use App\Services\AchievementService;
use App\Services\EmailVerificationService;
use App\Services\InventoryAlertService;
use App\Services\NotificationDispatchService;
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
    InventoryRepository::class,
    fn ($c) => new InventoryRepository($c->get(\PDO::class))
);
$container->set(
    RequestRepository::class,
    fn ($c) => new RequestRepository($c->get(\PDO::class))
);
$container->set(
    AlertRepository::class,
    fn ($c) => new AlertRepository($c->get(\PDO::class))
);
$container->set(
    BloodUnitRepository::class,
    fn ($c) => new BloodUnitRepository($c->get(\PDO::class))
);
$container->set(
    NotificationRepository::class,
    fn ($c) => new NotificationRepository($c->get(\PDO::class))
);
$container->set(
    AuditLogRepository::class,
    fn ($c) => new AuditLogRepository($c->get(\PDO::class))
);
$container->set(
    AchievementRepository::class,
    fn ($c) => new AchievementRepository($c->get(\PDO::class))
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
$container->set(
    NotificationDispatchService::class,
    fn ($c) => new NotificationDispatchService(
        $c->get(NotificationRepository::class),
        $c->get(DonorProfileRepository::class)
    )
);
$container->set(
    InventoryAlertService::class,
    fn ($c) => new InventoryAlertService(
        $c->get(AlertRepository::class),
        $c->get(DonationPolicyRepository::class),
        $c->get(NotificationDispatchService::class)
    )
);
$container->set(
    AchievementService::class,
    fn ($c) => new AchievementService(
        $c->get(AchievementRepository::class),
        $c->get(DonationRepository::class)
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
        $c->get(BankProfileRepository::class),
        $c->get(AuditLogRepository::class),
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
        $c->get(InventoryRepository::class),
        $c->get(InventoryAlertService::class),
        $c->get(AchievementService::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AchievementController::class,
    fn ($c) => new AchievementController(
        $c->get(AchievementService::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    InventoryController::class,
    fn ($c) => new InventoryController(
        $c->get(InventoryRepository::class),
        $c->get(BankProfileRepository::class),
        $c->get(DonationCenterRepository::class),
        $c->get(DonationPolicyRepository::class),
        $c->get(InventoryAlertService::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    RequestController::class,
    fn ($c) => new RequestController(
        $c->get(\PDO::class),
        $c->get(RequestRepository::class),
        $c->get(BloodUnitRepository::class),
        $c->get(InventoryRepository::class),
        $c->get(BankProfileRepository::class),
        $c->get(DonationCenterRepository::class),
        $c->get(InventoryAlertService::class),
        $c->get(AuditLogRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AlertController::class,
    fn ($c) => new AlertController(
        $c->get(AlertRepository::class),
        $c->get(BankProfileRepository::class),
        $c->get(DonationCenterRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    BankDonorController::class,
    fn ($c) => new BankDonorController(
        $c->get(DonorProfileRepository::class),
        $c->get(BankProfileRepository::class),
        $c->get(DonationCenterRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    NotificationController::class,
    fn ($c) => new NotificationController(
        $c->get(NotificationRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AdminDashboardController::class,
    fn ($c) => new AdminDashboardController(
        $c->get(UserRepository::class),
        $c->get(DonationCenterRepository::class),
        $c->get(AlertRepository::class),
        $c->get(RequestRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AdminUserController::class,
    fn ($c) => new AdminUserController(
        $c->get(UserRepository::class),
        $c->get(AuditLogRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AdminPolicyController::class,
    fn ($c) => new AdminPolicyController(
        $c->get(DonationPolicyRepository::class),
        $c->get(AuditLogRepository::class),
        $c->get(LoggerInterface::class)
    )
);
$container->set(
    AdminAuditController::class,
    fn ($c) => new AdminAuditController(
        $c->get(AuditLogRepository::class),
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
(require __DIR__ . '/../src/Routes/notifications.php')($app);
(require __DIR__ . '/../src/Routes/admin.php')($app);

$app->run();
