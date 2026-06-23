# Docker Setup for Pulso Solidario

This Docker Compose configuration sets up the complete development environment for the Pulso Solidario project with PHP backend, MySQL database, and a web interface.

## Architecture

The setup includes three main services:

- **web**: PHP 8.2 Apache server (port 8000)
- **db**: MySQL 8.0 database server (port 3306)
- **phpmyadmin**: MySQL management interface (port 8080)

## Project Structure

```
./
├── frontend/           # HTML, CSS, JavaScript files
├── backend/            # PHP application files
└── database/           # SQL initialization scripts
```

## Prerequisites

- Docker Desktop installed and running
- Docker Compose (included with Docker Desktop)

## Quick Start

### 1. Setup Environment Variables

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` if you want to change default database credentials.

### 2. Start the Services

```bash
docker-compose up -d
```

This command will:
- Build the PHP Apache image
- Start MySQL database
- Start phpMyAdmin
- Create a shared network for all services

### 3. Verify Services are Running

```bash
docker-compose ps
```

## Accessing Services

| Service | URL | Default Credentials |
|---------|-----|-------------------|
| Web Application | http://localhost:8000 | - |
| phpMyAdmin | http://localhost:8080 | User: `pulso_user` / Pass: `pulso_password` |
| MySQL | localhost:3306 | User: `pulso_user` / Pass: `pulso_password` |

## Useful Commands

### Start Services
```bash
docker-compose up -d
```

### Stop Services
```bash
docker-compose down
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f web
docker-compose logs -f db
```

### Rebuild Services
```bash
docker-compose up -d --build
```

### Stop and Remove Data
```bash
docker-compose down -v
```

### Access PHP Container Shell
```bash
docker-compose exec web bash
```

### Access MySQL Console
```bash
docker-compose exec db mysql -u pulso_user -p pulso_solidario
```

## Development Workflow

### Adding Frontend Files

Place your HTML, CSS, and JavaScript files in `./frontend/`. They will be accessible at `http://localhost:8000/`.

Example structure:
```
frontend/
├── index.html
├── css/
│   └── style.css
└── js/
    └── app.js
```

### Adding Backend PHP Files

Place your PHP files in `./backend/`. The backend is configured to handle PHP files directly.

Example structure:
```
backend/
├── index.php
├── api/
│   └── endpoint.php
└── config/
    └── database.php
```

### Database Initialization

Place SQL initialization scripts in `./database/`. These will be automatically executed when the database container starts for the first time.

Example:
```
database/
└── init.sql
```

### Sample Database Connection (PHP)

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

## Troubleshooting

### Database Connection Issues

If your PHP application can't connect to the database:
- Make sure the `MYSQL_HOST` is set to `db` (the service name)
- Verify credentials match in `.env` and your PHP configuration
- Check logs: `docker-compose logs db`

### Port Already in Use

If ports 8000, 3306, or 8080 are already in use:
1. Change the port mappings in `docker-compose.yml`
2. Rebuild: `docker-compose up -d --build`

### Container Won't Start

Check the logs:
```bash
docker-compose logs
```

Common issues:
- MySQL initialization takes time - give it 10-15 seconds
- Permission issues - try: `docker-compose down -v && docker-compose up -d`

### Clear Everything and Start Fresh

```bash
docker-compose down -v
docker system prune -a
docker-compose up -d
```

## Advanced Configuration

### Using Custom MySQL Image

To use a different MySQL version, edit `docker-compose.yml`:
```yaml
db:
  image: mysql:5.7  # or any other version
```

### Persistent PHP Configuration

To add custom PHP configuration, create a `php.ini` file and add to the Dockerfile:
```dockerfile
COPY php.ini /usr/local/etc/php/conf.d/
```

### Using Redis for Caching

Add to `docker-compose.yml`:
```yaml
redis:
  image: redis:7-alpine
  ports:
    - "6379:6379"
  networks:
    - pulso-network
```

## Production Considerations

This setup is designed for **development**. For production:
- Use environment-specific configurations
- Implement proper backup strategies for MySQL volumes
- Use a production-grade web server configuration
- Add security headers and SSL/TLS
- Implement proper logging and monitoring
- Consider using container registries for image management

## Support

For issues or questions about this Docker setup, refer to:
- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PHP Docker Official Image](https://hub.docker.com/_/php)
- [MySQL Docker Official Image](https://hub.docker.com/_/mysql)
