# Configuración de Docker para Pulso Solidario

Documentación del entorno Docker: servicios, volúmenes, red y archivos de infraestructura.

Para el día a día, empieza por la sección **Cómo Empezar** del **[README.md](README.md)**. Para desarrollo de la API, consulta **[backend/README.md](backend/README.md)**.

## Servicios

| Servicio | Contenedor | Imagen / build | Puerto (host) |
|----------|------------|----------------|---------------|
| **backend** | `pulso-solidario-backend` | `Dockerfile` (PHP 8.2 + Apache) | 3001 → 80 (API) |
| **frontend** | `pulso-solidario-frontend` | `nginx:alpine` | 3000 → 80 |
| **db** | `pulso-solidario-db` | `mysql:8.0` | 3306 → 3306 |
| **mailhog** | `pulso-solidario-mailhog` | `mailhog/mailhog:v1.0.1` | 8025 (UI) · 1025 (SMTP) |
| **phpmyadmin** | `pulso-solidario-phpmyadmin` | `phpmyadmin:latest` | 3002 → 80 |

Los cinco servicios comparten la red `pulso-network`. El servicio `backend` resuelve la base de datos por el hostname `db` y el SMTP por `mailhog` (nombres de servicio en Compose, no `localhost`).

## Archivos de infraestructura

```
./
├── docker-compose.yml      # Orquestación de servicios, volúmenes y variables
├── Dockerfile              # Imagen PHP: extensiones, Composer, Apache
├── apache-api.conf         # Alias /api → backend/public (copiado al contenedor)
├── nginx-frontend.conf     # Nginx: estáticos + proxy /api/ → backend
├── docker-entrypoint.sh    # provision.sh + arranque de Apache
├── .env.example            # Plantilla de credenciales
├── frontend/               # Interfaz web (HTML/JS/CSS)
├── backend/                # API SlimPHP
└── database/               # Scripts SQL y provision.sh
```

### `docker-compose.yml`

- **backend** se construye desde el `Dockerfile` y monta `./backend` y `./database` (solo lectura).
- **frontend** usa `nginx:alpine`, monta `./frontend` como raíz HTML y aplica `nginx-frontend.conf`.
- **db** persiste datos en el volumen nombrado `mysql_data` e inicializa la BD con scripts en `./database/` (solo si el volumen está vacío).
- **mailhog** captura SMTP en desarrollo (UI en 8025).
- **phpmyadmin** se conecta a `db` mediante variables `PMA_*`.

### `Dockerfile`

Construye la imagen del servicio `backend`:

- PHP 8.2 con Apache y `mod_rewrite`
- Extensiones `mysqli`, `pdo`, `pdo_mysql`
- Herramientas: `git`, `curl`, `zip`/`unzip`, `mariadb-client` (para `provision.sh`)
- Composer
- Configuración de Apache para la API (`apache-api.conf`)

### `nginx-frontend.conf`

- Sirve HTML/JS/CSS estáticos desde `frontend/`
- Proxy de `/api/` hacia `http://backend:80/api/` (reenvía cookies)
- Vistas en `frontend/pages/` con URLs públicas `/login/`, `/register/`, `/confirm-email/`, `/dashboard/`
- `/pages/` no es URL pública (responde 404)
- `/` redirige a `/login/`
- Sin caché en desarrollo

### Volúmenes

| Servicio | Montaje en el host | Ruta en el contenedor | Rol |
|----------|-------------------|----------------------|-----|
| **backend** | `./backend` | `/var/www/backend` | Código de la API (`/api/` → `public/`) |
| **backend** | `./database` | `/database` (ro) | `provision.sh` en cada arranque |
| **frontend** | `./frontend` | `/usr/share/nginx/html` (ro) | HTML/JS/CSS estáticos |
| **frontend** | `./nginx-frontend.conf` | `/etc/nginx/conf.d/default.conf` (ro) | Config Nginx |
| **db** | `mysql_data` (volumen) | `/var/lib/mysql` | Persistencia MySQL |
| **db** | `./database` | `/docker-entrypoint-initdb.d` | Init SQL (solo volumen vacío) |

El servicio **backend** expone la API en el puerto 3001. El servicio **frontend** sirve el sitio en el puerto 3000 y hace proxy de `/api` hacia `backend`.

## Variables de entorno

Definidas en `.env` (opcional; hay valores por defecto en `docker-compose.yml`):

| Variable | Uso |
|----------|-----|
| `DB_ROOT_PASSWORD` | Contraseña root de MySQL |
| `DB_USER` / `DB_PASSWORD` / `DB_NAME` | Credenciales de la aplicación |
| `APP_ENV` | Ambiente (`local` por defecto). El FE consulta `GET /api/config` y solo en `local`/`development` muestra tips de Mailhog. En `production` la cookie de sesión `PULSOSESSID` usa `Secure` |
| `APP_URL` | Base del frontend para enlaces de confirmación (default `http://localhost:3000`) |
| `SMTP_HOST` / `SMTP_PORT` | SMTP (default Mailhog: `mailhog:1025`) |
| `MAIL_FROM` | Remitente de correos de confirmación |

El servicio `backend` recibe: `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`, `APP_ENV`, `APP_URL`, `SMTP_HOST`, `SMTP_PORT`, `MAIL_FROM`.

> Nota: `.env.example` incluye `APP_DEBUG` y variables `PHPMYADMIN_*`, pero Compose no las inyecta al contenedor; phpMyAdmin usa `DB_USER` / `DB_PASSWORD` vía `PMA_*`.

## Inicio y verificación

```bash
cp .env.example .env   # opcional
docker-compose up -d --build
docker-compose ps
```

| Recurso | URL | Credenciales |
|---------|-----|--------------|
| Frontend | http://localhost:3000 | — |
| API | http://localhost:3001/api/ | — |
| Mailhog (correos de prueba) | http://localhost:8025 | — |
| phpMyAdmin | http://localhost:3002 | `pulso_user` / `pulso_password` |
| MySQL (desde el host) | `localhost:3306` | `pulso_user` / `pulso_password` |

### Correo de confirmación (local)

Tras registrarte, el backend envía el enlace por SMTP a **Mailhog**. Ábrelo en http://localhost:8025, entra al mensaje y usa el enlace (`/confirm-email/?token=…`). Si SMTP falla, el mismo enlace queda en los logs del backend (`docker-compose logs backend`).

### Sesión (cookie HttpOnly)

Login y confirmación de correo crean una sesión PHP (`PULSOSESSID`, HttpOnly, `SameSite=Lax`, path `/`). El FE llama a la API con `credentials: 'include'` vía el proxy Nginx de `/api/`. Las rutas `/dashboard/*` usan un auth guard que consulta `GET /api/auth/me`.

Las vistas viven en `frontend/pages/` (`login`, `register`, `confirm-email`, `dashboard`, …). Nginx las sirve con las URLs públicas `/login/`, `/register/`, etc.; los assets compartidos siguen en `frontend/{css,js,images}/`.

## Flujo de desarrollo

Los volúmenes montan el código fuente directamente: los cambios en `frontend/` y `backend/` se reflejan al recargar el navegador (el frontend es estático servido por Nginx; no hay bundler ni HMR).

| Qué desarrollas | Dónde | Documentación |
|-----------------|-------|---------------|
| Interfaz web | `frontend/` | HTML/JS/CSS estáticos; proxy `/api/` en `nginx-frontend.conf` |
| API (SlimPHP) | `backend/` | [backend/README.md](backend/README.md) |
| Esquema / datos iniciales | `database/*.sql` | `provision.sh` en cada arranque del `backend`; además MySQL ejecuta los `.sql` si el volumen `mysql_data` está vacío |

Dependencias PHP del backend:

```bash
docker exec -it pulso-solidario-backend bash -c "cd /var/www/backend && composer install"
```

Reconstruye la imagen (`--build`) cuando cambies `Dockerfile`, `apache-api.conf` o `docker-entrypoint.sh`. Cambios en `nginx-frontend.conf` bastan con reiniciar el servicio `frontend` (está montado como volumen).

## Comandos útiles

Los comandos habituales están en la sección **Cómo Empezar** del **[README.md](README.md)**. Adicionales:

```bash
# Eliminar volúmenes (borra datos de MySQL)
docker-compose down -v

# Shell dentro del contenedor backend
docker-compose exec backend bash

# Consola MySQL
docker-compose exec db mysql -u pulso_user -p pulso_solidario

# Reaplicar esquema/seeds sin borrar el volumen
docker-compose exec backend /database/provision.sh
```

## Solución de problemas

### La API devuelve 404 o Apache no encuentra `/api`

- Verifica que la imagen incluya `apache-api.conf`: `docker-compose up -d --build`
- Revisa logs: `docker-compose logs -f backend`

### PHP no conecta a MySQL

- Usa `MYSQL_HOST=db` dentro del contenedor `backend`, no `localhost`
- Comprueba que credenciales en `.env` coincidan con las del servicio `db`
- MySQL tarda unos segundos en iniciar; revisa: `docker-compose logs db`

### Puerto en uso (3000, 3001, 3002, 3306, 1025 u 8025)

Edita el mapeo en `docker-compose.yml` y vuelve a levantar los servicios.

### Reinicio limpio

```bash
docker-compose down -v
docker-compose up -d --build
```

## Configuración avanzada

### Otra versión de MySQL

```yaml
db:
  image: mysql:5.7
```

### PHP personalizado

Añade al `Dockerfile`:

```dockerfile
COPY php.ini /usr/local/etc/php/conf.d/
```

### Servicios adicionales (ej. Redis)

```yaml
redis:
  image: redis:7-alpine
  ports:
    - "6379:6379"
  networks:
    - pulso-network
```

## Producción

Esta configuración es para **desarrollo**. En producción considera: variables por entorno, backups del volumen `mysql_data`, HTTPS, endurecimiento de Apache/PHP y no exponer phpMyAdmin, Mailhog ni MySQL al exterior sin necesidad.

## Referencias externas

- [Docker Docs](https://docs.docker.com/)
- [Docker Compose Docs](https://docs.docker.com/compose/)
- [Imagen PHP](https://hub.docker.com/_/php)
- [Imagen MySQL](https://hub.docker.com/_/mysql)
- [Nginx](https://hub.docker.com/_/nginx)
- [Mailhog](https://github.com/mailhog/MailHog)
