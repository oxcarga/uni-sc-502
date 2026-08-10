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
SELECT user_id, blood_type, eligible, phone, province FROM donor_profiles;
SELECT id, code, name, province, active FROM donation_centers;
SELECT center_id, blood_type, units FROM inventory ORDER BY blood_type;
"
```

### P4 — perfil donante y centros

Comprobar que no hay `blood_type` en `users` y que todo donante tiene perfil:

```bash
docker-compose exec db mysql -u pulso_user -ppulso_password pulso_solidario -e "
SHOW COLUMNS FROM users LIKE 'blood_type';
SELECT u.id, u.email, u.role, dp.blood_type, dp.eligible
  FROM users u
  LEFT JOIN donor_profiles dp ON dp.user_id = u.id
 WHERE u.role = 'donor';
SELECT COUNT(*) AS active_centers FROM donation_centers WHERE active = 1;
"
```

- `SHOW COLUMNS ... blood_type` debe devolver vacío.
- Cada fila `role=donor` debe tener `dp.blood_type` (o al menos fila en `donor_profiles`).
- Debe haber ≥1 centro activo (seed: Hospital Regional).

### P5 — citas y donaciones

```bash
docker-compose exec db mysql -u pulso_user -ppulso_password pulso_solidario -e "
SELECT id, code, donor_id, center_id, scheduled_at, status FROM appointments ORDER BY scheduled_at;
SELECT id, donor_id, appointment_id, blood_type, donated_at, certificate_code FROM donations;
SELECT id, code, donation_id, blood_type, status FROM blood_units;
SELECT key_name, value_text FROM donation_policies WHERE key_name = 'donor_interval_days';
"
```

**Flujo demo (UI):** login `donante@test.com` → Citas → agendar (o usar seed) → login `banco@test.com` → Citas → Completar → volver como donante y ver historial de donaciones.

### P6 — inventario

```bash
docker-compose exec db mysql -u pulso_user -ppulso_password pulso_solidario -e "
SELECT blood_type, units FROM inventory WHERE center_id = 1 ORDER BY blood_type;
SELECT id, type, blood_type, quantity, detail, created_at
  FROM inventory_movements WHERE center_id = 1 ORDER BY id DESC LIMIT 10;
SELECT key_name, value_text FROM donation_policies
  WHERE key_name LIKE 'inventory_%';
"
```

Seed: O- (28) y AB- (40) en crítico (&lt;50). Completar una cita (P5) deja un `receipt` y suma 1 unidad.

**Flujo demo (UI):** login `banco@test.com` → Inventario (semáforo + movimientos) → Registrar entrada / Ajustar stock.

O en phpMyAdmin: http://localhost:3002 (`pulso_user` / `pulso_password`).

## Solución de problemas

**La tabla `users` no existe / faltan tablas nuevas**

- El volumen ya existía antes de añadir los scripts → ejecuta `./database/provision.sh` o `docker-compose down -v`.
- Tras cambios de esquema, `provision.sh` reaplica `01_init.sql` (DROP + CREATE) y el seed.
- Revisa logs: `docker-compose logs db`

**Error de sintaxis al iniciar**

- Revisa que los scripts usen sintaxis MySQL 8 (InnoDB, `AUTO_INCREMENT`, etc.).
