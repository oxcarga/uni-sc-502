# Base de datos — Scripts de inicialización (MySQL / Docker)

Scripts SQL para el entorno local con Docker. El servicio `db` en `docker-compose.yml` ya está configurado así:

```yaml
db:
  volumes:
    - ./database:/docker-entrypoint-initdb.d
```

MySQL crea la base de datos `pulso_solidario` (variable `MYSQL_DATABASE`) y, si el volumen `mysql_data` está vacío, ejecuta también los `.sql` de esta carpeta **en orden alfabético** (solo la primera vez).

En **cada** `docker-compose up`, el contenedor `backend` ejecuta `provision.sh` antes de arrancar Apache (vía `docker-entrypoint.sh`), de modo que esquema y datos de ejemplo quedan siempre aplicados de forma idempotente.

## Archivos

```
database/
├── 01_init.sql     # Tablas (esquema MySQL)
├── 02_seed.sql     # Datos de ejemplo
├── provision.sh    # Reaplicar esquema/datos sin borrar volumen
└── README.md       # Esta guía operativa
```

Plan de implementación de **toda la app** (DB + BE + FE + Config): [../plan.md](../plan.md).

## Flujo automático

```
docker-compose up
    → db (MySQL healthy)
    → backend/docker-entrypoint.sh
        → /database/provision.sh   ← 01_init.sql + 02_seed.sql
        → Apache
```

No hace falta ejecutar `provision.sh` a mano salvo depuración.

## Reinicio limpio (borrar todos los datos)

```bash
cd src
docker-compose down -v
docker-compose up -d --build
```

## Convención de nombres

Usa prefijos numéricos para controlar el orden de ejecución:

| Archivo        | Contenido              |
|----------------|------------------------|
| `01_init.sql`  | `CREATE TABLE IF NOT EXISTS` |
| `02_seed.sql`  | `INSERT IGNORE` (idempotente) |
| `03_*.sql`     | Procedimientos, vistas, etc. |

Solo deben quedar en esta carpeta los `.sql` que MySQL deba ejecutar (más `provision.sh` y este README). El plan del proyecto vive en `../plan.md`.

## Verificar

```bash
docker-compose exec db mysql -u pulso_user -ppulso_password pulso_solidario -e "SELECT * FROM usuarios;"
```

O en phpMyAdmin: http://localhost:3002 (`pulso_user` / `pulso_password`).

## Solución de problemas

**La tabla `usuarios` no existe**

- El volumen ya existía antes de añadir los scripts → ejecuta `./database/provision.sh` o `docker-compose down -v`.
- Revisa logs: `docker-compose logs db`

**Error de sintaxis al iniciar**

- Revisa que los scripts usen sintaxis MySQL 8 (InnoDB, `AUTO_INCREMENT`, etc.).
