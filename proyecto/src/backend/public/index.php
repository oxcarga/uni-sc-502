<?php

// Evita conversiones implícitas de tipos
declare(strict_types=1);

use App\Controllers\UserController;
use App\Database\Connection;
use App\Repositories\UserRepository;
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

// Inyeccion controladores
$container->set(UserController::class, fn ($c) => new UserController($c->get(UserRepository::class), $c->get(LoggerInterface::class)));

(require __DIR__ . '/../src/Support/Logger.php')($container);

// Configura el contenedor de dependencias
AppFactory::setContainer($container);

// Creacion de la aplicacion Slim
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->setBasePath('/api');

(require __DIR__ . '/../src/Routes/index.php')($app);
(require __DIR__ . '/../src/Routes/users.php')($app);

$app->run();
