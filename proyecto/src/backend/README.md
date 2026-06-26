# Backend — API con SlimPHP

API REST del proyecto **Pulso Solidario**, implementada con [Slim Framework 4](https://www.slimframework.com/). Todos los endpoints se sirven bajo el prefijo `/api/`.

## ¿Qué es SlimPHP?

Slim es un micro-framework PHP para construir APIs y aplicaciones web. Proporciona:

- **Enrutamiento** declarativo (`GET /users`, `POST /donations`, etc.)
- **Middleware** para autenticación, CORS, validación, etc.
- **Inyección de dependencias** vía PSR-11 (contenedor)
- **Manejo de peticiones/respuestas** con estándares PSR-7

En lugar de un archivo PHP por endpoint (`users/get.php`), defines rutas en código y un único **front controller** (`index.php`) recibe todas las peticiones.

## Requisitos

- PHP 8.2+ y Composer — incluidos en el contenedor Docker `backend` (no necesitas instalarlos en tu máquina)
- Extensión `pdo_mysql` (ya instalada en el `Dockerfile`)
- Módulo Apache `rewrite` habilitado (ya configurado en el `Dockerfile`)

## Instalación de dependencias

En este proyecto se usa **Docker como entorno principal**. Composer vive dentro del contenedor `backend` (ver `Dockerfile`); la carpeta `vendor/` se genera en `backend/` y queda disponible en el contenedor gracias al volumen montado.

**Primera vez o tras clonar el repo** (desde `src/`):

```bash
docker-compose up -d --build
docker exec -it pulso-solidario-backend bash -c "cd /var/www/backend && composer install"
```

Ese comando se ejecuta **dentro del contenedor**, en la ruta donde Docker monta `backend/`. No hace falta tener Composer instalado localmente.

**Si modificas `composer.json`** (añades un paquete, etc.):

```bash
docker exec -it pulso-solidario-backend bash -c "cd /var/www/backend && composer require nombre/paquete"
# o, si solo cambió composer.lock:
docker exec -it pulso-solidario-backend bash -c "cd /var/www/backend && composer install"
```


## Estructura del proyecto

```
backend/
├── public/                    # Única carpeta expuesta publicamente
│   ├── .htaccess              # Reescritura de URLs hacia index.php
│   └── index.php              # Front controller (punto de entrada)
├── src/
│   ├── Routes/
│   │   ├── users.php          # Rutas de usuarios
│   │   ├── donations.php      # Rutas de donaciones
│   │   └── bloodbank.php      # Rutas del banco de sangre
│   ├── Controllers/
│   │   ├── UserController.php
│   │   └── DonationController.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── CorsMiddleware.php
│   ├── Services/              # Lógica de negocio
│   └── Database/
│       └── Connection.php     # Conexión PDO
├── config/
│   └── settings.php           # Configuración de la aplicación
├── composer.json
├── composer.lock
└── vendor/                    # Dependencias (generado por Composer)
```

### Carpeta `public/` y exposición del api

El backend sigue el patrón recomendado por Slim: **solo el contenido de `public/` es accesible desde el navegador**. El resto del código (`src/`, `vendor/`, `config/`, `composer.json`, etc.) vive fuera del document root y no debe poder consultarse por HTTP.

#### ¿Por qué esta separación?

Si toda la carpeta `backend/` se sirviera directamente bajo `/api/`, archivos sensibles quedarían expuestos, por ejemplo:

| Ruta en el navegador | Riesgo |
|----------------------|--------|
| `/api/composer.json` | Revela dependencias y estructura del proyecto |
| `/api/composer.lock` | Expone versiones exactas de paquetes |
| `/api/vendor/...` | Dependencias accesibles desde fuera |
| `/api/src/...` | Código fuente alcanzable por URL |

Con `public/` como única entrada del api, esas rutas **no existen** para Apache y las peticiones pasan por Slim o devuelven 404.

#### ¿Por qué no montar solo `public/` en Docker?

Podría parecer suficiente montar `./backend/public` en el contenedor, pero `public/index.php` necesita cargar archivos del directorio padre:

```php
require __DIR__ . '/../vendor/autoload.php';
(require __DIR__ . '/../src/Routes/users.php')($app);
```

Si solo se montara `public/`, `vendor/` y `src/` no estarían disponibles dentro del contenedor y la aplicación fallaría.

**Solución adoptada:** montar todo `backend/` en `/var/www/backend` (fuera del document root del frontend) y usar un **alias de Apache** para exponer únicamente `public/` bajo la URL `/api/`.

```
Host (tu máquina)                    Contenedor Docker
─────────────────                    ─────────────────
backend/                    →        /var/www/backend/
  public/index.php          →          public/  ──alias──→  /api/  (visible en web)
  src/                      →          src/     (no expuesto)
  vendor/                   →          vendor/  (no expuesto)
  composer.json             →          composer.json (no expuesto)

frontend/                   →        /var/www/html/  →  http://localhost:3000/
```

### Despliegue con Docker

En `docker-compose.yml`, el servicio `backend` define:

```yaml
volumes:
  - ./frontend:/var/www/html
  - ./backend:/var/www/backend
```

- **Frontend:** `./frontend` es el document root de Apache (`/`).
- **Backend:** `./backend` se monta en `/var/www/backend`, **no** dentro de `/var/www/html`.
- **API:** Apache enlaza `/api` con `/var/www/backend/public` mediante `apache-api.conf`.

Tras cambiar la configuración de Apache o Docker, reconstruye el contenedor:

```bash
docker-compose up -d --build
```

## Configuración de Apache

La API requiere dos piezas de configuración que trabajan en conjunto.

### 1. Alias de Apache (`../apache-api.conf`)

Archivo en la raíz de `src/`, copiado al contenedor por el `Dockerfile`:

```apache
Alias /api /var/www/backend/public

<Directory /var/www/backend/public>
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

| Directiva | Función |
|-----------|---------|
| `Alias /api ...` | Las peticiones a `http://localhost:3001/api/*` se resuelven en `public/`, no en `frontend/` |
| `AllowOverride All` | Permite que `public/.htaccess` aplique reglas de reescritura |
| `Require all granted` | Autoriza el acceso a esa carpeta |

### 2. Reescritura de URLs (`public/.htaccess`)

Dentro de `public/`, las rutas de Slim no son archivos físicos. Este archivo envía esas peticiones al front controller:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

| Línea | Significado |
|-------|-------------|
| `RewriteEngine On` | Activa el módulo de reescritura (`mod_rewrite`) |
| `RewriteCond ... !-f` | No reescribe si la URL apunta a un **archivo** real en `public/` |
| `RewriteCond ... !-d` | No reescribe si la URL apunta a un **directorio** real en `public/` |
| `RewriteRule ^ index.php` | Todo lo demás se atiende con `index.php` |
| `[QSA,L]` | Conserva los parámetros de consulta (`?id=1`) y detiene el procesamiento de reglas |

### Flujo de una petición

Ejemplo: `GET http://localhost:3001/api/users`

1. Apache recibe la petición en el puerto 80 dentro de Docker (mapeado a `3001` en el host - el host es su computadora, la que hospeda Docker).
2. El **alias** redirige `/api/users` hacia `/var/www/backend/public/users`.
3. No existe el archivo `users` en `public/`.
4. `.htaccess` reescribe internamente a `public/index.php`.
5. Slim recibe la ruta `/users` (con `setBasePath('/api')`) y ejecuta el handler definido en `src/Routes/users.php`.

```
Navegador → /api/users
    → Alias Apache → /var/www/backend/public/users
    → .htaccess → public/index.php
    → Slim → src/Routes/users.php
```

## Punto de entrada (`public/index.php`)

Todas las peticiones a `/api/*` pasan por `public/index.php`, que bootstrapea Slim y carga las rutas:

```php
<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

// La app vive bajo /api en el servidor
$app->setBasePath('/api');

// Cargar rutas
...
(require __DIR__ . '/../src/Routes/users.php')($app);
...

$app->run();
```

## Definición de rutas

Cada archivo en `src/Routes/` exporta una función que recibe la instancia de Slim:

```php
<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app) {
    $app->get('/users', function (Request $request, Response $response) {
        $payload = json_encode(['success' => true, 'data' => []]);
        $response->getBody()->write($payload);
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/users', \App\Controllers\UserController::class . ':create');
};
```

Registra nuevas rutas en `public/index.php`:

```php
(require __DIR__ . '/../src/Routes/donations.php')($app);
(require __DIR__ . '/../src/Routes/bloodbank.php')($app);
```

## Conexión a la base de datos

Variables disponibles en el contenedor (definidas en `docker-compose.yml` y `.env`):

| Variable | Valor por defecto | Uso |
|----------|-------------------|-----|
| `MYSQL_HOST` | `db` | Hostname del servicio MySQL en Docker |
| `MYSQL_USER` | `pulso_user` | Usuario de la base de datos |
| `MYSQL_PASSWORD` | `pulso_password` | Contraseña |
| `MYSQL_DATABASE` | `pulso_solidario` | Nombre de la BD |

Ejemplo `src/Database/Connection.php`:

```php
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
            ]
        );
    }
}
```

Regístrala en el contenedor DI para inyectarla en controladores.

## URLs de la API

Con Slim, las rutas no dependen de archivos `.php` individuales:

| Ruta Slim | URL | Método |
|-----------|-----|--------|
| `/` | http://localhost:3001/api/ | GET |
| `/users` | http://localhost:3001/api/users | GET |
| `/users` | http://localhost:3001/api/users | POST |
| `/users/{id}` | http://localhost:3001/api/users/1 | GET |
| `/donations` | http://localhost:3001/api/donations | GET |
| `/bloodbank/inventory` | http://localhost:3001/api/bloodbank/inventory | GET |

## Llamar al backend desde el frontend

**JavaScript:**

```javascript
// GET
const response = await fetch('/api/users');
const data = await response.json();

// POST
await fetch('/api/users', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ name: 'Juan', email: 'juan@example.com' }),
});
```

**PHP (cURL):**

```php
<?php
$ch = curl_init('http://localhost:3001/api/users');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$data = json_decode($response, true);
```

## Middleware

Slim permite encadenar middleware global o por ruta:

```php
// Global
$app->add(\App\Middleware\CorsMiddleware::class);

// Por grupo de rutas
$app->group('/users', function ($group) {
    $group->get('', UserController::class . ':index');
    $group->post('', UserController::class . ':create');
})->add(\App\Middleware\AuthMiddleware::class);
```

Casos típicos en este proyecto:

- **CORS** — si el frontend se sirve desde otro origen
- **Auth** — verificar sesión o JWT en rutas protegidas
- **Validación** — comprobar el cuerpo JSON antes del controlador

## Formato de respuesta JSON

Mantén un formato consistente en todos los endpoints:

```json
{
  "success": true,
  "data": [],
  "message": "Operación completada",
  "timestamp": "2026-06-23T21:34:00Z"
}
```

En errores:

```json
{
  "success": false,
  "error": "Descripción del error",
  "code": 400
}
```

## Desarrollo con Docker

```bash
# Desde src/
docker-compose up -d --build

# Instalar dependencias dentro del contenedor
docker exec -it pulso-solidario-backend bash -c "cd /var/www/backend && composer install"

# Ver logs
docker-compose logs -f backend
```

Los cambios en `src/` y en las rutas se reflejan al instante gracias al volumen montado. Si modificas `composer.json`, vuelve a ejecutar `composer install` **dentro del contenedor** (ver sección [Instalación de dependencias](#instalación-de-dependencias)).

### Comprobaciones útiles

| URL | Resultado esperado |
|-----|-------------------|
| http://localhost:3001/api/ | JSON de bienvenida de la API |
| http://localhost:3001/api/users | JSON de la ruta de usuarios |
| http://localhost:3001/api/composer.json | **404** (archivo no expuesto) |
| http://localhost:3001/api/../composer.json | **404** (fuera de `public/`) |

## Buenas prácticas con Slim

1. **Un front controller** — Toda la API pasa por `public/index.php`; no crees archivos PHP sueltos por endpoint
2. **No expongas código fuera de `public/`** — Añade assets públicos solo dentro de `public/`; nunca muevas `vendor/` ni `src/` al document root
3. **Controladores delgados** — La lógica de negocio va en `Services/`, no en las rutas
4. **Respuestas PSR-7** — Escribe JSON en el body con el header `Content-Type: application/json`
5. **Códigos HTTP** — `201` al crear, `404` si no existe, `422` para validación fallida
6. **Variables de entorno** — No hardcodees credenciales; usa `getenv()` con las variables de Docker
7. **Autoload PSR-4** — Configurado en `composer.json` bajo el namespace `App\`

## Recursos

- [Documentación Slim 4](https://www.slimframework.com/docs/v4/)
- [Slim — Routing](https://www.slimframework.com/docs/v4/objects/routing.html)
- [Slim — Middleware](https://www.slimframework.com/docs/v4/concepts/middleware.html)
- [PSR-7 HTTP Message](https://www.php-fig.org/psr/psr-7/)
