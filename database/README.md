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
Diagrama entidad-relación (Mermaid): [ERD.md](./ERD.md).

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
# Desde la raíz del repositorio
docker-compose down -v
docker-compose up -d --build
```

## Convención de nombres

Tablas y columnas en **inglés**. Usa prefijos numéricos para controlar el orden de ejecución:

| Archivo        | Contenido              |
|----------------|------------------------|
| `01_init.sql`  | `DROP` + `CREATE TABLE` (esquema completo) |
| `02_seed.sql`  | `INSERT IGNORE` (idempotente) |
| `03_*.sql`     | Procedimientos, vistas, etc. |

Solo deben quedar en esta carpeta los `.sql` que MySQL deba ejecutar (más `provision.sh` y este README). El plan del proyecto vive en `../plan.md`.

## Tablas

| Tabla | Rol |
|-------|-----|
| `users` | Cuentas (`donor` / `bank` / `admin`) |
| `email_verification_tokens` | Tokens de confirmación de correo |
| `donation_centers` | Centros / bancos de sangre |
| `donor_profiles` | Perfil clínico del donante (`blood_type`, elegibilidad, ubicación) |
| `bank_profiles` | Vincula usuario `bank` → centro |
| `medical_institutions` | Hospitales que solicitan sangre |
| `appointments` | Citas de donación |
| `donations` | Historial de donaciones completadas |
| `blood_units` | Unidades individuales (trazabilidad) |
| `inventory` | Stock por centro × tipo de sangre |
| `inventory_movements` | Libro append-only de movimientos |
| `requests` | Solicitudes médicas |
| `alerts` | Alertas de stock crítico |
| `donation_policies` | Umbrales e intervalos de negocio |
| `notifications` | Avisos in-app |
| `audit_log` | Auditoría admin |
| `achievements` / `donor_achievements` | Logros / gamificación |

`blood_type` vive en `donor_profiles` (ya no en `users`).

## Cuentas demo (`02_seed.sql`)

Contraseña: `demo1234`

| Correo | Rol |
|--------|-----|
| `donante@test.com` | `donor` |
| `banco@test.com` | `bank` |
| `admin@test.com` | `admin` |

## Verificar

```bash
docker-compose exec db mysql -u pulso_user -ppulso_password pulso_solidario -e "
SHOW TABLES;
SELECT id, email, role FROM users;
SELECT user_id, blood_type, eligible FROM donor_profiles;
SELECT center_id, blood_type, units FROM inventory ORDER BY blood_type;
"
```

O en phpMyAdmin: http://localhost:3002 (`pulso_user` / `pulso_password`).

## Solución de problemas

**La tabla `users` no existe / faltan tablas nuevas**

- El volumen ya existía antes de añadir los scripts → ejecuta `./database/provision.sh` o `docker-compose down -v`.
- Tras cambios de esquema, `provision.sh` reaplica `01_init.sql` (DROP + CREATE) y el seed.
- Revisa logs: `docker-compose logs db`

**Error de sintaxis al iniciar**

- Revisa que los scripts usen sintaxis MySQL 8 (InnoDB, `AUTO_INCREMENT`, etc.).
