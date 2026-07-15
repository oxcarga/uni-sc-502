# Inicio rápido — Configuración con Docker

## Configuración en un solo comando

Para iniciar todo el entorno de desarrollo:

```bash
cd proyecto/src
docker-compose up -d
```

¡Listo! Tu proyecto ya está en ejecución:
- 🌐 Frontend: http://localhost:3000
- 🔧 API: http://localhost:3001/api/
- 📧 Mailhog (correos de prueba): http://localhost:8025
- 🗄️ phpMyAdmin: http://localhost:3002
- 🔌 MySQL: localhost:3306

## Detener todo

```bash
docker-compose down
```

## Antes de empezar

1. (Opcional) Copia y personaliza las variables de entorno:
   ```bash
   cp .env.example .env
   ```

2. Los directorios se crean automáticamente. ¡Solo agrega tus archivos!

## Estructura del proyecto

```
./
├── docker-compose.yml      # Configuración de Docker ⭐ Ejecutar desde aquí
├── Dockerfile              # Construcción del contenedor PHP
├── docker-entrypoint.sh    # Script de inicio del contenedor
├── .env.example            # Plantilla de variables de entorno
├── DOCKER.md               # Documentación completa
├── frontend/               # 🌐 Interfaz web (Vite + JS) → http://localhost:3000
├── backend/                # 🔧 API REST (SlimPHP) → http://localhost:3001/api/
└── database/               # 🗄️ Scripts SQL de inicialización
```

## Mapeo de URLs

| Ubicación del archivo | URL de acceso |
|-----------------------|---------------|
| `frontend/src/` (Vite) | http://localhost:3000 |
| `backend/public/index.php` | http://localhost:3001/api/ |

## Comandos de Docker frecuentes

| Comando | Propósito |
|---------|-----------|
| `docker-compose up -d` | Iniciar todos los servicios |
| `docker-compose down` | Detener todos los servicios |
| `docker-compose logs -f` | Ver registros en vivo |
| `docker-compose logs -f backend` | Ver registros del servidor backend PHP |
| `docker-compose logs -f db` | Ver registros de MySQL |
| `docker-compose exec backend bash` | Acceder a la shell del contenedor PHP |
| `docker-compose exec db mysql -u pulso_user -p pulso_solidario` | Acceder a la CLI de MySQL |
| `docker-compose ps` | Mostrar contenedores en ejecución |
| `docker-compose up -d --build` | Reconstruir e iniciar los servicios |

## Solución de problemas

**¿Los servicios no inician?**
```bash
docker-compose down -v
docker-compose up -d --build
```

**Verificar si los contenedores están en ejecución:**
```bash
docker-compose ps
```

**Ver registros detallados:**
```bash
docker-compose logs backend
docker-compose logs db
```

Para la documentación completa, consulta **DOCKER.md**
