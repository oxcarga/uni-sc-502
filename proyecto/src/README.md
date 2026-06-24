# Pulso Solidario — Código fuente

Raíz de la aplicación. Aquí vive el frontend, el backend y la configuración de Docker.

## 🚀 Cómo Empezar

### Prerequisitos

- **Docker Desktop** instalado y corriendo ([Descargar](https://www.docker.com/products/docker-desktop))
- Docker Compose (incluido en Docker Desktop)

### Iniciar el Proyecto con Docker

La forma más rápida y recomendada es usar Docker Compose:

```bash
# Desde esta carpeta (src/)
docker-compose up -d
```

¡Listo! Tu proyecto está corriendo en:
- 🌐 **Frontend/Backend**: http://localhost:8000
- 🗄️ **phpMyAdmin**: http://localhost:8080 (Usuario: `pulso_user` / Contraseña: `pulso_password`)
- 🔌 **MySQL**: localhost:3306

### Para Detener los Servicios

```bash
docker-compose down
```

### Documentación Completa de Docker

Para guías detalladas, comandos útiles y solución de problemas, consulta:
- **[QUICKSTART.md](QUICKSTART.md)** — Inicio rápido
- **[DOCKER.md](DOCKER.md)** — Documentación completa

### Alternativa: Desarrollo local sin Docker (obsoleto)

Si prefieres ejecutar sin Docker (requiere PHP y MySQL instalados localmente):

```bash
# Opción 1: Abrir directamente en el navegador
# Navega a ../estaticos/ y abre index.html

# Opción 2: Usar servidor HTTP local
python3 -m http.server 8000

# O con Node.js:
npx http-server .
```

**Nota**: Se recomienda usar Docker para asegurar consistencia en todos los entornos.

## Estructura

```
src/
├── docker-compose.yml      # Orquestación de servicios
├── Dockerfile              # Configuración PHP
├── docker-entrypoint.sh    # Script de inicialización
├── .env.example            # Variables de entorno (plantilla)
├── DOCKER.md               # Documentación Docker
├── QUICKSTART.md           # Guía de inicio rápido
├── frontend/               # Interfaz web (raíz en http://localhost:8000/)
├── backend/                # API PHP (prefijo /api/)
└── database/               # Scripts SQL de inicialización
```
