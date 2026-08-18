# Plan de implementación — Pulso Solidario

> Documento de **acción** para agentes y desarrolladores. Cubre la app completa, no solo la base de datos.
> Cada fase debe avanzar los **cuatro pilares** en el mismo cambio (aunque alguno sea “sin cambios” documentado).

**Pilares (siempre en este orden dentro de cada fase):**

| Pilar | Ámbito | Rutas típicas |
|-------|--------|----------------|
| **DB** | Esquema MySQL + seeds | `database/01_init.sql`, `02_seed.sql`, `provision.sh` |
| **BE** | API PHP (Slim) | `backend/src/{Controllers,Repositories,Routes,Middleware,Services}/`, `backend/public/index.php` |
| **FE** | UI estática + JS | `frontend/pages/`, `frontend/js/` |
| **Config** | Docker, Nginx, Apache, env, docs de arranque | `docker-compose.yml`, `Dockerfile`, `nginx-frontend.conf`, `apache-api.conf`, `.env.example`, `DOCKER.md`, `docker-entrypoint.sh` |

**Motor:** MySQL 8.0 · BD `pulso_solidario`  
**Convención:** tablas/columnas, claves JSON, roles y rutas HTTP en inglés (p. ej. `users`, `first_name`, `/api/auth/confirm-email`).  
**Rutas FE internas:** `/dashboard/{donor,bank,admin}/` (no `/panel/`).  
**Última actualización:** 2026-08-18 (P11 cerrado).

Operación Docker/provision: [DOCKER.md](./DOCKER.md) · [database/README.md](./database/README.md) · ERD: [database/ERD.md](./database/ERD.md).

---

## Cómo usar este plan

1. Implementar **una fase a la vez**, en orden:  
   `P0 → P0b → P0c → P1 → P2 → P3 → P4 → P5 → P6 → P7 → P8 → P9 → P10 → P11 → P12 → P13 → P14 → P15 → P16`.
2. En cada fase cubrir los **4 pilares** (DB, BE, FE, Config). Si un pilar no aplica, dejar la subsección con `Sin cambios` y una línea de por qué.
3. Tras cambios de esquema: re-provisionar (`database/provision.sh` o `docker compose down -v && up -d`).
4. Al cerrar una fase: marcar checklist, poner ✅ al inicio del título `## Px` y en el mapa, y pasar a la siguiente.
5. Constantes de dominio (no negociar en cada PR):

| Concepto | Valores |
|----------|---------|
| Roles | `donor`, `bank`, `admin` |
| Tipos de sangre | `O+`, `O-`, `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-` |
| Umbrales inventario | saludable `>100`, moderado `50–100`, crítico `<50` (por tipo y centro); preferir leer de `donation_policies` desde P8 |
| Confirmación de correo | token de un solo uso, caduca en 24 h; sin confirmar no hay login |
| Citas (`appointments.status`) | `pending`, `confirmed`, `completed`, `cancelled`, `no_show` |
| Unidades (`blood_units.status`) | `available`, `assigned`, `discarded`, `expired` |
| Solicitudes (`requests.status`) | `pending`, `assigned`, `in_transit`, `completed`, `cancelled` |
| Prioridad | `low`, `normal`, `critical` |
| Movimientos | `receipt`, `assignment`, `adjustment`, `discard` |
| Alertas (`alerts.status`) | `active`, `resolved` |

---

## Estado actual (resumen)

| Área | Situación |
|------|-----------|
| Auth (P0–P0c) | ✅ Cerrado. El alta pública de donante es `POST /api/users` (así está implementado; login/sesión van en `/api/auth/*`). Confirmación de correo, sesión servidor y auth-guard listos. |
| Esquema MySQL | ✅ Tablas de dominio en `01_init.sql` + seed demo en `02_seed.sql` (centros, perfiles, citas, inventario, solicitudes, alertas, políticas, logros, notificaciones). Seed incluye donante `active = 0` y centro `BK-002` inactivo. |
| BE dominio | ✅ P4–P11 (MVP + gobierno admin de cuentas y centros) |
| FE paneles | ✅ Shells P1–P3 + flujos donante/banco P4–P9; admin: home KPIs, donantes, bancos (create/edit/activar), políticas y auditoría; banco: settings persistentes |
| Backlog | P12–P16 pendientes (notificaciones UX, elegibilidad/impacto, ciclo solicitudes/trazabilidad, reportes, cambio de rol admin) |

**Implicación:** P0–P11 cerrados. Siguiente: P12 (campana de notificaciones en todos los shells + prefs de despacho).

Fases P0–P11: **cerradas**. Fases P12–P16: **pendientes**.

**Huecos menores (no bloquean P10):**

- Campana in-app (`notifications-ui.js`) solo se monta si existe `#notif-bell` (hoy: home donante). El resto de shells tiene el botón deshabilitado “Próximamente” (P12).
- `POST /api/users` sigue siendo el alta pública (intencional). `GET/PUT/DELETE /api/users` exigen sesión **admin**.
- **Cambio de rol (P16):** `PATCH /api/admin/users/{id}` ya acepta `role` y audita `user.role_change`, pero el panel admin **no** expone esa acción (solo activar/desactivar donantes). Además, promover a `bank` no crea `bank_profiles` ni pide `center_id`, así que la cuenta no podría operar el panel banco.

---

## Punto de partida (baseline)

Estado previo a P0 (referencia histórica):

| Pilar | Contenido |
|-------|-----------|
| DB | `users` básico; seed de 4 correos del equipo |
| BE | CRUD `/api/users` |
| FE | `login/` stub y `registro/` parcial |
| Config | Compose (backend/frontend/db/phpmyadmin), Nginx proxy `/api` → backend |

---

## ✅ P0 — Auth y cuentas (login + registro + roles)

**Objetivo:** poder registrar un donante con contraseña, iniciar sesión y distinguir roles.

### DB

Ampliar `users` en `01_init.sql`:

| Columna | Acción |
|---------|--------|
| `last_name` | `VARCHAR` NOT NULL |
| `password_hash` | `VARCHAR(255)` NOT NULL |
| `role` | `VARCHAR` NOT NULL DEFAULT `'donor'` + CHECK (`donor`|`bank`|`admin`) |
| `active` | `TINYINT(1)` NOT NULL DEFAULT 1 |
| `updated_at` | `TIMESTAMP` DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |
| `first_name`, `email`, `created_at` | mantener |

Índices: UNIQUE `email`; índice en `role`.

**Seed:** 3 roles con `password_hash` real; separar `first_name`/`last_name`; documentar contraseña demo; al menos un usuario `active = 0` para pruebas.

### BE

1. `UserRepository`: columnas nuevas; `findByEmail`; nunca exponer `password_hash`.
2. `UserController` create/update: `first_name`, `last_name`, `email`, `password`; hash; `role = donor` en registro público.
3. Email duplicado → 409 (`23000` / MySQL `1062`).
4. `POST /api/auth/login`: email + password → `password_verify` + `active` → sesión (`id`, `first_name`, `last_name`, `email`, `role`).
5. Login fallido / inactivo → 401 genérico.

### FE

1. `register.js` + HTML: enviar `first_name`, `last_name`, `email`, `password` por separado.
2. `login.js`: llamar login; guardar sesión; redirigir según `role` a `/dashboard/{role}/`.
3. Errores de API visibles en ambos formularios.

### Config

1. Confirmar que `nginx-frontend.conf` sigue proxificando `/api/` al backend.
2. Re-provision tras cambio de esquema (`provision.sh` / volumen limpio).
3. Documentar en seed/comentarios la contraseña demo local (sin secretos de producción).

### Listo cuando

- [x] Registro crea fila con hash y `role = donor`
- [x] Login ok con seed; login con password mala → 401
- [x] Usuario `active = 0` no puede entrar
- [x] Email duplicado → 409 en el primer intento
- [x] JSON de usuario no incluye `password_hash`

**Desbloquea:** CU0 login, CU1 registro (cuenta).

---

## ✅ P0b — Confirmación de correo

**Objetivo:** al crear una cuenta, el usuario confirma el correo; el enlace lo autentica la primera vez.

### DB

1. En `users`: `email_confirmed` `TINYINT(1)` NOT NULL DEFAULT `0`; opcional `email_confirmed_at`.
2. Tabla `email_verification_tokens`: `user_id` FK, `token_hash` UNIQUE, `expires_at`, `used_at`, `created_at`.
3. Distinguir: `active = 0` = deshabilitada (admin); `email_confirmed = 0` = pendiente. Login exige `active = 1` **y** `email_confirmed = 1`.

**Seed:** demos con `email_confirmed = 1`. Sin seed de tokens.

### BE

1. Registro: `email_confirmed = 0`; generar token (hash + 24 h); enviar email con enlace; 201 sin sesión automática.
2. Confirmar correo: primer uso válido → confirmar + devolver payload de sesión (como login). Reuso → error sin sesión.
3. Reenviar confirmación (respuesta genérica).
4. Login: sin confirmar → 403 específico; credenciales malas → 401.
5. No exponer tokens/`token_hash` en JSON.

### FE

1. Tras registro: **no** redirect a `/login/`; mensaje de “correo de confirmación enviado” en la misma página.
2. `confirm-email/`: consumir token → sesión → redirect al dashboard por `role`.
3. Login: mostrar 403 de correo no confirmado (+ CTA reenviar opcional).

### Config

1. Servicio de correo local (**Mailhog**) en `docker-compose.yml`.
2. Variables en `.env.example`: host SMTP, puerto, `APP_URL` / base del enlace de confirmación, from.
3. Pasar esas vars al contenedor `backend`; actualizar `DOCKER.md` / `README.md`.
4. Nginx: servir `frontend/pages/confirm-email/`.

### Listo cuando

- [x] Registro deja `email_confirmed = 0` y genera token (+ envío o log del enlace)
- [x] Tras el form de registro **no** hay redirect a `/login/`; se muestra el mensaje de correo enviado
- [x] Primer clic del enlace confirma, inicia sesión y redirige al panel; segundo uso falla sin loguear
- [x] Login sin confirmar → 403; confirmada → ok
- [x] Seed demo sigue pudiendo iniciar sesión
- [x] Reenvío invalida el token anterior
- [x] En local se puede ver/abrir el correo de prueba vía Config documentada

**Desbloquea:** registro con propiedad del correo verificada.

---

## ✅ P0c — Sesión de usuario y auth guard

**Objetivo:** sesión de usuario real (no solo `sessionStorage`) y proteger rutas internas `/dashboard/*`.

### DB

Sin cambios de esquema en P0c (sesión vía cookie de servidor). Si más adelante se necesitan refresh tokens persistidos, documentarlo en una fase posterior.

### BE

1. Tras login y confirm-email: emitir sesión de servidor (cookie HttpOnly de sesión PHP).
2. `GET /api/auth/me`: devolver el usuario de la sesión activa o 401.
3. `POST /api/auth/logout`: invalidar sesión/cookie.
4. Middleware de auth reutilizable (`AuthMiddleware`) para endpoints protegidos.
5. Login/confirm-email establecen la misma forma de sesión.

### FE

1. Sesión válida = servidor (`/api/auth/me`); cache local del perfil es opcional.
2. `frontend/js/auth-guard.js` en todas las páginas `/dashboard/*`:
   - Sin sesión → redirect a `/login/`
   - Con sesión pero `role` distinto al dashboard visitado → redirect al dashboard correcto
3. Logout en topbar (llama a `/api/auth/logout` y limpia cache local).
4. `login.js` y `confirm-email.js` con `credentials` / cookies en `fetch`.

### Config

1. Documentar cookie: `SameSite`, `Secure` (local vs production) en `.env.example` / `DOCKER.md` si hace falta.
2. Proxy Nginx reenvía cookies al backend (`/api/`).
3. El FE estático no oculta el HTML; el guard es UX + la API exige sesión. No bloquear `/dashboard/*` solo en Nginx.

### Listo cuando

- [x] Login/confirm crean sesión verificable con `GET /api/auth/me`
- [x] Logout cierra la sesión
- [x] Visitar `/dashboard/*` sin sesión redirige a `/login/`
- [x] Usuario `donor` no permanece en `/dashboard/admin` (y análogo por rol)
- [x] Sin sesión no se puede “usar” la app como autenticado vía API protegida

**Desbloquea:** paneles internos seguros antes de P1+.

---

## ✅ P1 — Shell UI del donante

**Objetivo:** dejar el panel donante navegable, coherente con [DESIGN.md](./DESIGN.md) y listo para cablear APIs en P4+. Los datos de negocio pueden seguir siendo mock/placeholder.

**Alcance de páginas (ya hay maquetas; cerrar gaps):**

| Ruta | Propósito en P1 |
|------|-----------------|
| `/dashboard/donor/` | Home: saludo, próxima cita (mock), atajos |
| `/dashboard/donor/appointments/` | Listado/agenda (UI; datos mock) |
| `/dashboard/donor/banks/` | Listado de centros (UI; datos mock) |
| `/dashboard/donor/profile/` | Formulario de perfil (UI; sin persistir aún — API en P4) |

### DB

Sin cambios. El seed ya trae donante demo + perfil + citas; no se requieren tablas nuevas.

### BE

Sin cambios de dominio. Opcional: confirmar que `/api/auth/me` basta para rellenar nombre/rol en topbar (ya usado por `auth-guard.js`).

### FE

1. Unificar shell: sidebar, topbar, logout, `data-required-role="donor"`, `auth-guard.js` en **todas** las subpáginas.
2. Navegación sin enlaces rotos; estado activo del ítem de menú correcto por ruta.
3. Sustituir o etiquetar claramente datos hardcodeados como “demo / pendiente de API” (evitar que parezcan reales).
4. Responsive: sidebar → menú móvil según DESIGN.md.
5. Placeholders visibles para secciones futuras: elegibilidad, impacto, logros, notificaciones (pueden ser cards “Próximamente” o secciones vacías; no inventar APIs).
6. Login/confirm deben redirigir donantes a `/dashboard/donor/`.

### Config

1. Verificar que Nginx sirve `/dashboard/donor/**` (try_files).
2. Actualizar referencias en docs si aún mencionan `/panel/donor`.
3. Sin servicios nuevos.

### Listo cuando

- [x] Donante demo entra y navega home → citas → bancos → perfil sin 404
- [x] Guard + logout funcionan en todas las subpáginas
- [x] UI alineada a DESIGN.md (tokens, tipografía, estados vacíos)
- [x] No hay dependencia de APIs de dominio aún no implementadas (fallos silenciosos / mocks etiquetados)

**Desbloquea:** superficie FE del CU1 (donante) lista para P4/P5/P9.

---

## ✅ P2 — Shell UI del banco

**Objetivo:** mismo criterio que P1 para el rol `bank`.

**Alcance de páginas:**

| Ruta | Propósito en P2 |
|------|-----------------|
| `/dashboard/bank/` | Home operativo (KPIs mock, alertas mock) |
| `/dashboard/bank/inventory/` | Grid por tipo de sangre (UI; niveles visuales mock) |
| `/dashboard/bank/appointments/` | Citas del centro (UI mock) |
| `/dashboard/bank/donors/` | Compatibilidad / donantes (UI mock) |
| `/dashboard/bank/settings/` | Ajustes del centro (UI mock) |

### DB

Sin cambios. Seed ya incluye centro, `bank_profiles`, inventario mixto (O- crítico), citas y alerta demo.

### BE

Sin cambios de dominio. El usuario `bank` del seed debe poder autenticarse; el centro ligado se usará en P5/P6.

### FE

1. Shell completo con `data-required-role="bank"` + auth-guard en todas las subpáginas.
2. Navegación íntegra; topbar muestra usuario banco.
3. Inventario mock con semáforo (verde/amarillo/rojo) según umbrales del plan — documentar que en P6/P8 vendrán de API/políticas.
4. Etiquetar mocks; estados vacíos para solicitudes/alertas si la sección aún no tiene página propia (la cola real llega en P7).
5. Redirect de login `bank` → `/dashboard/bank/`.

### Config

1. Verificar rutas `/dashboard/bank/**` en Nginx.
2. Docs: cuenta demo `banco@test.com` / contraseña seed.
3. Sin servicios nuevos.

### Listo cuando

- [x] Banco demo navega todas las subpáginas sin 404
- [x] Guard de rol impide a `donor` quedarse en `/dashboard/bank/`
- [x] Semáforo de inventario visible (aunque sea mock)
- [x] Shell listo para cablear inventario (P6), citas (P5) y solicitudes (P7)

**Desbloquea:** superficie FE del panel banco.

---

## ✅ P3 — Shell UI del admin

**Objetivo:** shell del panel `admin` alineado a README (usuarios, bancos, reportes placeholder, config, auditoría placeholder).

**Alcance de páginas:**

| Ruta | Propósito en P3 |
|------|-----------------|
| `/dashboard/admin/` | Home con KPIs mock del ecosistema |
| `/dashboard/admin/banks/` | Gestión de centros (UI; CRUD real en fases posteriores / P4 lectura) |
| `/dashboard/admin/donors/` | Gestión de donantes (UI mock) |
| Config / auditoría / reportes | Enlace o página stub “Próximamente” si no existe aún (políticas + auditoría reales en P8) |

### DB

Sin cambios.

### BE

Sin cambios de dominio. Opcional menor: endpoint admin de listado de usuarios puede esperar a una sub-fase o a P8; en P3 basta la maqueta.

### FE

1. Shell con `data-required-role="admin"` + auth-guard en todas las subpáginas.
2. Navegación sin enlaces muertos (`href="#"` → stub o deshabilitado con tooltip).
3. Tablas mock de bancos/donantes con filtros UI (sin API).
4. Redirect login `admin` → `/dashboard/admin/`.
5. Dejar anclas claras para P8 (políticas, auditoría, notificaciones).

### Config

1. Verificar `/dashboard/admin/**` en Nginx.
2. Docs: cuenta `admin@test.com`.
3. Sin servicios nuevos.

### Listo cuando

- [x] Admin demo navega home → bancos → donantes sin 404
- [x] Guard de rol correcto
- [x] Stubs de configuración/auditoría no rompen la navegación
- [x] Shell listo para cablear gestión real y P8

**Desbloquea:** superficie FE del panel admin.

---

## ✅ P4 — Perfil de donante y centros

**Objetivo:** separar datos clínicos del donante de la cuenta; listar/consultar centros de donación vía API (el esquema ya existe).

### DB

**Sin cambios de esquema.** Verificado en `01_init.sql` / seed:

1. `donor_profiles`: `user_id` PK/FK, `blood_type`, `birth_date`, `phone`, `province`, `canton`, `address`, `medical_history`, `eligible`, `last_donation_at`, prefs `notify_*`.
2. `donation_centers`: `code`, `name`, dirección/geo, contacto, horarios, `active`, etc.
3. `bank_profiles`: `user_id`, `center_id`.
4. **No** existe `users.blood_type`.
5. Registro de donante: fila en `donor_profiles` en la misma transacción (`UserRepository::create`); `ensureForUser` cubre edge cases.

**Seed:** ≥1 centro activo; perfil donante con `blood_type`; usuario `bank` ligado. (Ya en `02_seed.sql`.)

### BE

| Método | Ruta | Notas |
|--------|------|-------|
| `GET` | `/api/donor/profile` | Propio; requiere sesión `donor` |
| `PUT` | `/api/donor/profile` | Actualizar campos clínicos / contacto / prefs |
| `GET` | `/api/centers` | Listado de centros activos (donor/bank/admin) |
| `GET` | `/api/centers/{id}` | Detalle |

- Repositories: `DonorProfileRepository`, `DonationCenterRepository`.
- No leer/escribir `blood_type` en `users`.
- Admin: lectura con `?all=1`; **escritura de centros aplazada**.

### FE

1. `donor/profile/`: cargar/guardar vía API.
2. `donor/banks/`: listar centros desde API.
3. Home donante: tipo de sangre / elegibilidad + centros activos.
4. Admin `banks/`: lectura desde API; toggles/editar son demo local (escritura aplazada).

### Config

1. **Sin cambios** de volúmenes (sin migración).
2. Queries de verificación en `database/README.md`.
3. Endpoints documentados en `backend/README.md`.

### Listo cuando

- [x] No existe `users.blood_type`
- [x] Todo donante registrado tiene fila en `donor_profiles`
- [x] GET/PUT perfil donante funcionan (API + FE)
- [x] Centros se listan desde la API y se ven en FE donante
- [x] Stack Docker consistente

**Desbloquea:** perfil médico + listado de centros (CU1 perfil).

---

## ✅ P5 — Citas y donaciones

**Objetivo:** agendar donaciones, gestionar estados y materializar historial al completar.

### DB

**Sin cambios de esquema.** `appointments`, `donations`, `blood_units` + seed (citas en varios estados y ≥1 donación).

Reglas aplicadas en app + transacción SQL:

1. Al completar cita → `donations` + `blood_units` + `donor_profiles.last_donation_at` (y `eligible = 0`).
2. Intervalo: `donation_policies.donor_interval_days` (default 56).
3. `donations.appointment_id` UNIQUE respetado.

### BE

| Método | Ruta | Rol |
|--------|------|-----|
| `GET` | `/api/donor/appointments` | donor (propias) |
| `POST` | `/api/donor/appointments` | donor (agendar) |
| `PATCH` | `/api/donor/appointments/{id}` | donor (cancelar) |
| `GET` | `/api/bank/appointments` | bank (su centro) |
| `POST` | `/api/bank/appointments/{id}/complete` | bank/admin |
| `GET` | `/api/donor/donations` | donor (historial) |

### FE

1. `donor/appointments/`: listar, agendar (modal), cancelar + historial de donaciones.
2. Home donante: próxima cita desde API.
3. `bank/appointments/`: listado del centro + Completar.
4. Mocks de citas retirados donde hay API.

### Config

1. **Sin cambios** de volúmenes.
2. Flujo demo documentado en `database/README.md` / `backend/README.md`.

### Listo cuando

- [x] Donante agenda cita (FE → BE → DB)
- [x] Completar cita crea `donations` (y unidad) y actualiza perfil
- [x] Historial visible para el donante
- [x] Seed reproducible en Docker

**Desbloquea:** agenda + historial (CU1 citas).

---

## ✅ P6 — Inventario del banco

**Objetivo:** stock en vivo y libro de movimientos consumibles por API/UI.

### DB

**Ya existe:** `inventory`, `inventory_movements`, `blood_units`.

**Reglas:**

1. Toda variación de stock = transacción (movimiento append-only + update `inventory` [+ estado de unidad]).
2. Nivel saludable/moderado/crítico = **calculado** (hardcode umbrales del plan hasta P8; no guardar color en BD).
3. Seed: inventario mixto con **≥1 tipo en crítico** (`O-` < 50) — ya presente.

Ajustes solo si faltan índices/constraints detectados al implementar.

### BE

| Método | Ruta | Rol |
|--------|------|-----|
| `GET` | `/api/bank/inventory` | bank (su centro) / admin (por `center_id`) |
| `GET` | `/api/bank/inventory/movements` | historial |
| `POST` | `/api/bank/inventory/receipts` | recepción manual o desde donación |
| `POST` | `/api/bank/inventory/adjustments` | ajuste/descarte (bank/admin) |

- Al completar donación (P5), idealmente enganchar recepción (`receipt`) aquí o en el mismo servicio de dominio.
- Devolver en GET el `level`: `healthy` \| `moderate` \| `critical` por tipo.

### FE

1. `bank/inventory/`: datos reales + semáforo.
2. Home banco: resumen de críticos desde API.
3. (Opcional) vista simple de últimos movimientos.

### Config

1. Verificar seed crítico en phpMyAdmin/`mysql` CLI; doc en `database/README.md`.
2. Compose: `Sin cambios`.
3. Revisar charset/timezone ya configurados (no reescritura).

### Listo cuando

- [x] Consulta de stock por tipo (API + UI banco)
- [x] Recepción/actualización deja movimiento y cambia `inventory`
- [x] Seed muestra al menos un tipo crítico en UI

**Desbloquea:** panel banco (inventario en vivo); base para CU2/CU3.

---

## ✅ P7 — Solicitudes médicas y alertas

**Objetivo:** CU2 (alerta crítica) y CU3 (solicitud hospitalaria).

### DB

**Ya existe:** `medical_institutions`, `requests`, `alerts` (+ seed: 1 institución, 1 solicitud `pending` crítica, 1 alerta `active` sobre O-).

**Flujos a implementar en app:**

1. Solicitud → asignar centro → verificar stock → asignar unidades + movimiento `assignment` → actualizar estados (`requests`, `blood_units`).
2. Alerta al cruzar umbral de stock; resolver al recuperar stock (o acción manual).
3. Donantes compatibles vía `donor_profiles` (`blood_type` + `eligible`); **envío de notificaciones a usuarios → P8**.

Ajustes de seed solo para que el demo CU2/CU3 sea reproducible de un solo login banco.

### BE

| Método | Ruta | Rol |
|--------|------|-----|
| `GET` | `/api/bank/requests` | cola del centro |
| `POST` | `/api/bank/requests/{id}/assign` | asignar unidades (transacción) |
| `GET` | `/api/bank/alerts` | alertas del centro |
| `POST` | `/api/bank/alerts/{id}/resolve` | resolver |
| `GET` | `/api/bank/donors/compatible` | query `blood_type` |

- Reglas de stock en la misma transacción que el movimiento.
- Crear/activar alerta automáticamente cuando el stock quede crítico tras un movimiento (síncrono; sin cron salvo que se documente).

### FE

1. Banco: cola de solicitudes + detalle + acción asignar.
2. Banco: listado/indicador de alertas críticas (home + donors compatibles).
3. Admin: visibilidad de solicitudes/alertas (lectura) si encaja en el shell; si no, banco primero.

### Config

1. Re-provision solo si cambia seed.
2. Scheduler: `Sin cambios` si las alertas son síncronas al inventario.
3. Actualizar docs de demo CU2/CU3 (pasos con cuentas seed).

### Listo cuando

- [x] Cola de solicitudes operable (FE + BE + DB)
- [x] Asignar unidades deja trazabilidad (movimiento + estado unidad + request)
- [x] Alerta crítica visible para el tipo en stock bajo
- [x] Listado de donantes compatibles usable en banco

**Desbloquea:** CU2 y CU3 (sin push/notificaciones in-app aún).

---

## ✅ P8 — Notificaciones, auditoría y políticas

**Objetivo:** cerrar paneles admin/donante/banco con config de negocio, avisos in-app y rastro de auditoría.

### DB

**Ya existe:** `donation_policies`, `notifications`, `audit_log` (+ seed de umbrales globales, 1 notificación demo).

Trabajo DB típico: ampliar seed de notificaciones/auditoría si hace falta para demos; no rediseñar tablas.

### BE

1. **Políticas:** leer umbrales (`inventory_*`, `donor_interval_days`) desde `donation_policies`; dejar de hardcodear en servicios de inventario/elegibilidad.
2. **Notificaciones:** emitir en alta relevante, cita próxima (si se dispara), alerta crítica → donantes compatibles, solicitud atendida.
3. Endpoints:

| Método | Ruta | Rol |
|--------|------|-----|
| `GET` | `/api/notifications` | usuario autenticado (propias) |
| `POST` | `/api/notifications/{id}/read` | marcar leída |
| `GET`/`PUT` | `/api/admin/policies` | admin |
| `GET` | `/api/admin/audit-log` | admin |

4. Escribir `audit_log` en acciones admin sensibles (activar usuario, cambiar políticas, asignar solicitud, etc.).

### FE

1. Centro de notificaciones (donante y, si aplica, banco): campana/listado + marcar leída.
2. Admin: UI mínima de políticas + consulta de auditoría.
3. Inventario/alertas deben reflejar umbrales leídos de políticas (mensaje o tooltip opcional).

### Config

1. Variables de políticas por defecto solo como seed (no secretos en Compose).
2. Retención/volumen de logs de app: revisar `Logger` y volúmenes Docker si hace falta persistir.
3. Documentar endpoints admin y cómo inspeccionar seed de políticas.

### Listo cuando

- [x] Umbrales salen de `donation_policies`
- [x] Alerta crítica genera notificaciones a donantes compatibles
- [x] Acciones admin relevantes quedan en `audit_log`
- [x] UI de notificaciones usable en al menos un rol
- [x] Admin puede ver/editar políticas básicas

**Desbloquea:** CU2 completo (aviso a donantes) + panel admin de gobierno.

---

## ✅ P9 — Logros (gamificación)

**Objetivo:** insignias del panel donante al completar donaciones.

### DB

**Ya existe:** `achievements`, `donor_achievements` (+ catálogo `first_donation`, `hero_5`, `legend_10` y desbloqueo demo).

Solo ajustar seed/criterios si la evaluación en BE lo requiere.

### BE

1. Evaluar logros al completar donación (enganche en el servicio de P5).
2. `GET /api/donor/achievements` — catálogo + progreso/desbloqueos del donante autenticado.
3. Idempotencia: no duplicar `donor_achievements` por el mismo `achievement_id`.

### FE

1. Sección “Logros” en panel donante (home o subpágina): insignias bloqueadas/desbloqueadas + progreso.
2. Cablear al API; quitar mock.

### Config

1. Re-provision solo si cambia catálogo.
2. `Sin cambios` de servicios.
3. Nota breve en docs: cómo desbloquear el primer logro con el flujo demo (completar cita).

### Listo cuando

- [x] Seed con catálogo básico
- [x] Completar donaciones desbloquea al menos un logro visible en API y FE
- [x] Donante demo ve su logro `first_donation` del seed

**Desbloquea:** sección “Logros” del panel donante (README).

---

## ✅ P10 — Admin home + gestión de usuarios

**Objetivo:** KPIs reales en el home admin y gestión persistente de cuentas (activar/desactivar, rol), con auditoría.

**Roles:** `admin` (único; opera sobre cuentas de cualquier rol, pero la UI/API de esta fase es del panel admin).

### DB

**Sin cambios** de esquema: `users.active`, `users.role`, `audit_log`.

Seed: donante inactivo `donante_inactivo@test.com` (`active = 0`, correo confirmado; no puede iniciar sesión).

### BE

| Método | Ruta | Rol / notas |
|--------|------|-------------|
| `GET` | `/api/admin/dashboard` | admin. `banks` = `COUNT(donation_centers)`, `donors` = usuarios `donor`, alertas activas, solicitudes `pending` |
| `GET` | `/api/admin/users` | admin. Filtros `role`, `active` (`0`/`1`), `q` (nombre/email). JOIN a `donor_profiles` |
| `PATCH` | `/api/admin/users/{id}` | admin. Solo `active` y/o `role`. No deja el sistema sin un admin activo. Audita `user.activate` / `user.deactivate` / `user.role_change` |
| `POST` | `/api/users` | Público: alta de donante (registro) |
| `GET`/`PUT`/`DELETE` | `/api/users`… | Sesión **admin** (legado; el panel usa las rutas `/admin/users`) |

`AdminDashboardController` y `AdminUserController` están registrados en el contenedor DI de `public/index.php`.

### FE

1. Home admin: KPIs desde `GET /api/admin/dashboard`; actividad reciente desde `GET /api/admin/audit-log` (últimas 5 filas + enlace a `/audit/`). Políticas enlazan a `/settings/`; reportes y campana siguen “Próximamente” (P15 / P12).
2. `/dashboard/admin/donors/`: `adminApi.listUsers({ role: 'donor' })` + toggle `PATCH /api/admin/users/{id}` **solo `active`**. “Nuevo donante” sigue deshabilitado (alta = registro público). Cambio de rol (`donor` → `bank`/`admin`) **no está en UI**; se cierra en P16 (incluye vincular centro).
3. `adminApi.listUsers` / `patchUser` en `frontend/js/api.js`.

### Config

1. Endpoints y flujo de prueba en `backend/README.md` / `database/README.md`.
2. `Sin cambios` de Compose/Nginx. Re-provision para cargar el donante inactivo del seed.

### Listo cuando

- [x] Home admin muestra conteos en vivo (`GET /api/admin/dashboard`; centros, no usuarios `bank`)
- [x] Listado de donantes viene de `GET /api/admin/users`
- [x] Activar/desactivar persiste, exige admin y deja `audit_log`
- [x] `GET`/`PUT`/`DELETE /api/users` no funcionan sin sesión admin; el alta pública queda en `POST /api/users`
- [x] Seed con donante `active = 0` para el filtro

**Desbloquea:** gestión de usuarios del panel admin (README) + P11 (centros) con shell admin coherente.

---

## ✅ P11 — Escritura de centros + settings de banco

**Objetivo:** create/edit/activar centros (deferido desde P4) y persistir settings del banco.

**Roles:** `admin` + `bank`  
- `admin`: create/edit/activar cualquier centro (`/dashboard/admin/banks/`).  
- `bank`: persistir settings solo de *su* centro (`/dashboard/bank/settings/`).

### DB

**Sin cambios** de esquema: `donation_centers`, `bank_profiles`.

Seed: `BK-001` activo (Hospital Regional) y `BK-002` inactivo (Hospital Max Peralta) para demos de toggle.

### BE

1. `POST /api/centers` y `PUT /api/centers/{id}` — nombre, dirección, contacto, `active`, etc.
2. Permisos: **admin** create/edit/activar cualquiera; **bank** solo actualizar campos permitidos de *su* centro (settings).
3. Auditar: `center.create`, `center.update`, `center.activate` / `center.deactivate`.
4. `GET /api/bank/center` — centro ligado al usuario banco (`bank_profiles`).

| Método | Ruta | Rol |
|--------|------|-----|
| `POST` | `/api/centers` | admin |
| `PUT` | `/api/centers/{id}` | admin (completo); bank (solo su centro, campos limitados) |
| `GET` | `/api/bank/center` | bank |

### FE

1. `/dashboard/admin/banks/`: lista `GET /api/centers?all=1`; toggle y “Editar” / “Nuevo banco” persisten contra API.
2. `/dashboard/bank/settings/`: carga `GET /api/bank/center` y guarda `PUT /api/centers/{id}` (campos de su centro).
3. Mensajes de error/éxito con alert sticky donde aplique.

### Config

1. Documentar create/edit/activar centro y prueba Docker (admin + banco).
2. `Sin cambios` de servicios salvo docs.

### Listo cuando

- [x] Admin puede crear/editar/activar un centro y verlo en listados (`?all=1`)
- [x] Toggle activo en FE admin persiste (ya no es demo local)
- [x] Banco guarda settings de su centro
- [x] Cambios sensibles aparecen en `audit_log`

**Desbloquea:** “Bancos registrados” del README + settings de banco reales.

---

## P12 — Notificaciones UX completa

**Objetivo:** campana usable en todos los shells y despacho según prefs (`notify_appointments`, `notify_nearby`), además de `shortage_alert`.

**Roles:** `donor` + `bank` + `admin` (campana en los tres shells; prefs de despacho principalmente del perfil `donor`).

### DB

**Sin cambios** de tablas. Ampliar seed de `notifications` con ejemplos por tipo/rol si hace falta demo.

### BE

1. Despachar respetando prefs del perfil donante:
   - `notify_appointments` — cita próxima / completada (y cancelada si aplica).
   - `notify_nearby` — alerta de stock del centro preferido/cercano.
   - Mantener `shortage_alert` → compatibles (`notify_blood_match` u existente).
2. Feedback al banco/admin cuando corresponda (p. ej. solicitud asignada) si el receptor tiene cuenta.
3. Reutilizar `NotificationDispatchService` + endpoints P8 (`GET /api/notifications`, `POST .../read`).

### FE

1. Montar campana: `auth-guard.js` ya carga `notifications-ui.js` si existe `#notif-bell`. Hoy solo el **home donante** la tiene. Falta el markup en admin, banco y subpáginas donante.
2. Quitar botones “Notificaciones — Próximamente” (casi todos los dashboards).
3. Preferencias de perfil siguen siendo la fuente de verdad para opt-out. Hoy solo se respeta `notify_blood_match` en escasez (`NotificationDispatchService`).

### Config

1. Checklist de prueba de campana por rol en docs.
2. `Sin cambios` de Compose.

### Listo cuando

- [ ] Campana funciona en donor (todas las páginas del shell), bank y admin
- [ ] Prefs `notify_appointments` / `notify_nearby` afectan el despacho
- [ ] Marcar leída sigue funcionando vía API existente

**Desbloquea:** centro de notificaciones del README en todos los paneles.

---

## P13 — Elegibilidad + impacto donante

**Objetivo:** reemplazar cards “Próximamente” del home donante con elegibilidad derivada de políticas e impacto calculado.

**Roles:** `donor` (único; panel `/dashboard/donor/`).

### DB

**Sin cambios** de tablas. Reglas desde `donation_policies.donor_interval_days` + `donor_profiles.last_donation_at` / `eligible`.

### BE

1. Al leer perfil (o endpoint dedicado): derivar elegibilidad; restaurar `eligible = 1` cuando haya pasado el intervalo (no solo bloquear al agendar).
2. Impacto: servicio que agregue donaciones del donante y calcule métricas en respuesta (p. ej. unidades donadas + “vidas estimadas” por fórmula fija en código — **no** persistir el derivado).
3. Exponer en `GET /api/donor/profile` (campos derivados) y/o `GET /api/donor/impact`.

### FE

1. En `frontend/pages/dashboard/donor/index.html` (+ `home.js`): cards Elegibilidad e Impacto siguen en “Próximamente”. El home sí muestra un badge de `profile.eligible` (flag en BD, no derivado del intervalo).
2. Copiar clara: fecha estimada de próxima elegibilidad si aún no puede donar. El agendar cita **sí** valida `donor_interval_days` contra `last_donation_at`; el flag `eligible` se pone en `0` al completar y **no se restaura** al vencer el intervalo.

### Config

1. Documentar fórmula de impacto e intervalo en `backend/README.md` o nota breve en README.
2. `Sin cambios` de servicios.

### Listo cuando

- [ ] Donante ve estado de elegibilidad coherente con políticas + última donación
- [ ] Tras el intervalo, `eligible` vuelve a permitir donar sin intervención manual
- [ ] Card de impacto muestra unidades (y vidas estimadas) desde API, sin tabla nueva

**Desbloquea:** “Elegibilidad” y “Mi Impacto” del panel donante (README).

---

## P14 — Ciclo de solicitudes + no-show + trazabilidad básica

**Objetivo:** usar los estados del esquema (`in_transit`, `completed`, `cancelled`, `no_show`) y exponer trazabilidad mínima de `blood_units`.

**Roles:** `bank` + `admin`  
- `bank`: UI principal (crear solicitud, transiciones, `no_show`, trazabilidad).  
- `admin`: mismas APIs operativas / lectura multi-centro opcional (sin portal hospital).

### DB

**Sin cambios** de estados (ya en CHECK/convención). Ajustar seed: al menos una solicitud en estado distinto de `pending`/`assigned` si ayuda la demo.

### BE

1. `POST /api/bank/requests` (admin/banco) — alta de solicitud (institución, tipo, unidades, prioridad).
2. Transiciones: `assigned → in_transit → completed`; `cancelled` desde estados abiertos.
3. Citas banco: acción `no_show` (`PATCH`/`POST` sobre appointment).
4. Trazabilidad: `GET /api/bank/blood-units` y/o `GET /api/bank/blood-units/{id}` — unit → donation → appointment/donor → request (si asignada).
5. Auditar asignaciones/cambios de estado relevantes.

### FE

1. Panel banco: hoy solo lista + **Asignar** (`pending → assigned`). Crear solicitud, transiciones y `no_show` no existen en UI ni API.
2. Citas banco: solo botón Completar. El KPI de `no_show` se muestra pero no hay acción.
3. Vista simple de trazabilidad (detalle de unidad o tabla filtrable). `BloodUnitRepository` solo cubre lock/assign, no listado de traza.
4. Opcional: admin lectura multi-centro de cola (reutilizar APIs con `center_id` o list-all).

### Config

1. Documentar ciclo de vida de solicitudes y cómo probar no-show + traza con seed.
2. `Sin cambios` de Compose.

### Listo cuando

- [ ] Se puede crear una solicitud desde API/FE (no solo seed)
- [ ] Estados `in_transit` / `completed` / `cancelled` son alcanzables
- [ ] Banco puede marcar `no_show`
- [ ] Se puede seguir una unidad desde donación hasta asignación/solicitud

**Desbloquea:** cola médica completa + “Reporte de trazabilidad” básico (README).

---

## P15 — Reportes admin

**Objetivo:** estadísticas nacionales/regionales del home admin (hoy “Próximamente”) con agregaciones SQL.

**Roles:** `admin` (único; panel `/dashboard/admin/`).

### DB

**Sin cambios** de esquema. Agregaciones sobre `donations`, `blood_units`, `donation_centers`, `requests`.

### BE

1. `GET /api/admin/reports/summary` — payload JSON con:
   - donaciones por mes (últimos N meses),
   - distribución por tipo de sangre,
   - volumen/efectividad por centro (p. ej. donaciones completadas, solicitudes atendidas).
2. Solo rol `admin`; sin PII innecesaria en el reporte.

| Método | Ruta | Rol |
|--------|------|-----|
| `GET` | `/api/admin/reports/summary` | admin |

### FE

1. Página o sección `/dashboard/admin/reports/` (o reemplazar card “Próximamente” del home) con gráficos/tablas simples alimentados por API.
2. Preferir CSS/HTML + datos; librería de charts solo si ya hay dependencia o es imprescindible.

### Config

1. Documentar cómo probar reportes con el seed demo.
2. `Sin cambios` de servicios.

### Listo cuando

- [ ] Endpoint de summary responde con series usables
- [ ] Admin ve reportes en UI (no placeholder)
- [ ] Números coherentes con datos del seed tras provision

**Desbloquea:** “Reportes” del panel de administración (README).

---

## P16 — Admin: cambio de rol de cuentas

**Objetivo:** que el admin pueda promover una cuenta registrada como donante a banco (u otro rol), vinculándola a un centro cuando corresponda, desde el panel.

**Contexto (hoy):** el registro público (`POST /api/users`) **siempre** crea `role = donor` + `donor_profiles`. No hay alta pública de banco. P10 dejó `PATCH /api/admin/users/{id}` con campo `role` y auditoría `user.role_change`, pero:

1. El FE de `/dashboard/admin/donors/` solo lista `role=donor` y solo persiste `active`.
2. El PATCH **no** escribe `bank_profiles`. Un usuario con `role = bank` sin fila en `bank_profiles` no puede usar inventario, citas, solicitudes ni settings (`findCenterIdByUserId` → 404 “No hay un centro asociado”).
3. No hay página de “usuarios” de todos los roles; Bancos gestiona **centros**, no cuentas `bank`.

**Roles:** `admin` (único operador). La cuenta destino pasa a `donor` | `bank` | `admin`.

**Flujo canónico:** usuario se registra → confirma correo → admin abre Donantes → “Cambiar rol” → `bank` + centro → la cuenta entra al panel `/dashboard/bank/` en el siguiente `GET /api/auth/me` (la sesión guarda solo `user_id`; el rol se lee de BD).

### DB

**Sin cambios** de esquema: `users.role`, `bank_profiles` (`user_id`, `center_id`), `donor_profiles`, `audit_log`.

Reglas de integridad en app (transacción):

1. `donor` → `bank`: exigir `center_id` de un centro existente; **upsert** `bank_profiles`. Conservar `donor_profiles` (historial clínico; no borrar).
2. `bank` → `donor`: `role = donor`; **borrar** `bank_profiles` (si no, un re-promote usaría un centro obsoleto). Asegurar `donor_profiles` (`ensureForUser` si falta).
3. Cualquier rol → `admin`: no exige centro. Si deja de ser `bank`, borrar `bank_profiles`.
4. `bank` → `bank` con otro `center_id`: actualizar `bank_profiles.center_id` (reasignar centro).
5. Seguir sin dejar el sistema sin un admin activo (ya en P10).

Seed: **sin cambios** obligatorios (el demo ya tiene un `bank` ligado). Opcional: no hace falta un usuario extra si el flujo de prueba es registrar uno nuevo.

### BE

Extender `PATCH /api/admin/users/{id}` (no hace falta ruta nueva):

| Campo | Cuándo |
|-------|--------|
| `role` | `donor` \| `bank` \| `admin` |
| `center_id` | **obligatorio** si el rol resultante es `bank`; ignorado si no |
| `active` | igual que P10 |

1. Validar `center_id` (existe; preferible centro `active = 1` al promover).
2. Transacción: `users.role` + upsert/delete `bank_profiles` + `ensureForUser` si queda `donor`.
3. Auditar `user.role_change` con `from`, `to`, `center_id` si aplica. Si solo cambia el centro de un banco, auditar igual (p. ej. `to: bank` + `center_id` nuevo) o `user.center_assign`.
4. Ampliar `BankProfileRepository`: `upsert(user_id, center_id)`, `deleteByUserId`.
5. `GET /api/admin/users`: incluir `center_id` / nombre del centro cuando el usuario es `bank` (JOIN `bank_profiles` + `donation_centers`) para que el FE muestre el vínculo.

Respuestas 422 claras: rol inválido, `center_id` faltante al promover a banco, centro inexistente, último admin.

### FE

1. En `/dashboard/admin/donors/` (flujo principal: las cuentas nuevas viven ahí):
   - Acción por fila **Cambiar rol** (además de Ver y el toggle activo).
   - Modal: selector de rol (`donor` / `bank` / `admin`); si `bank`, selector de centro (`GET /api/centers?all=1`).
   - Submit → `adminApi.patchUser(id, { role, center_id })`. Al pasar a `bank`/`admin`, quitar la fila de la tabla de donantes y feedback de éxito.
2. Listado usable de cuentas **banco** (para reasignar centro o devolver a donante):
   - Opción A (preferida, menos páginas): sección o filtro en Donantes “Todas las cuentas” / filtro por rol, **o**
   - Opción B: bloque “Cuentas del centro” en `/dashboard/admin/banks/` (detalle del centro).
   - Elegir una; no duplicar CRUD de centros (eso es P11).
3. No permitir auto-degradarse si es el último admin (el API ya rechaza; mostrar el mensaje).
4. Alert sticky según DESIGN.md.

### Config

1. Documentar el flujo demo en `backend/README.md` / `database/README.md`: registrar donante → confirmar → admin cambia a `bank` + centro seed → login → `/dashboard/bank/`.
2. `Sin cambios` de Compose/Nginx. Sin re-provision salvo que se toque seed.

### Listo cuando

- [ ] Admin puede cambiar `donor` → `bank` eligiendo un centro, desde el panel
- [ ] Tras el cambio, esa cuenta opera el panel banco (hay `bank_profiles`)
- [ ] Admin puede devolver `bank` → `donor` (se elimina `bank_profiles`; sigue existiendo `donor_profiles`)
- [ ] El PATCH sin `center_id` al promover a banco responde 422 (no deja bancos huérfanos)
- [ ] El cambio queda en `audit_log` (`user.role_change`)
- [ ] Sigue sin poder quedarse el sistema sin un admin activo

**Desbloquea:** onboarding de personal de banco sin alta pública distinta (el registro sigue siendo donante; el admin asigna el rol).

---

## Mapa fase → entrega → pilares

| Fase | Entrega | Roles | Casos | DB | BE | FE | Config |
|------|---------|-------|-------|----|----|----|--------|
| ✅ P0 | Cuentas + login/registro | donor, bank, admin | CU0, CU1 (cuenta) | ✅ | ✅ | ✅ | ✅ |
| ✅ P0b | Confirmación de correo | donor (alta) | CU1 (cuenta verificada) | ✅ | ✅ | ✅ | ✅ |
| ✅ P0c | Sesión servidor + auth guard `/dashboard/*` | donor, bank, admin | CU0 (sesión) | ✅ | ✅ | ✅ | ✅ |
| ✅ P1 | Shell UI donante (`/dashboard/donor/**`) | donor | CU1 (superficie) | ✅ | ✅ | ✅ | ✅ |
| ✅ P2 | Shell UI banco (`/dashboard/bank/**`) | bank | Panel banco (superficie) | ✅ | ✅ | ✅ | ✅ |
| ✅ P3 | Shell UI admin (`/dashboard/admin/**`) | admin | Panel admin (superficie) | ✅ | ✅ | ✅ | ✅ |
| ✅ P4 | Perfil donante + centros (API + FE) | donor | CU1 (perfil) | ✅ | ✅ | ✅ | ✅ |
| ✅ P5 | Citas + donaciones | donor, bank | CU1 (agenda/historial) | ✅ | ✅ | ✅ | ✅ |
| ✅ P6 | Inventario + movimientos | bank | Base CU2/CU3 | ✅ | ✅ | ✅ | ✅ |
| ✅ P7 | Solicitudes + alertas + compatibles | bank (admin API) | CU2, CU3 | ✅ | ✅ | ✅ | ✅ |
| ✅ P8 | Notificaciones + políticas + auditoría | donor, bank, admin | CU2 completo + admin | ✅ | ✅ | ✅ | ✅ |
| ✅ P9 | Logros / gamificación | donor | Panel donante (logros) | ✅ | ✅ | ✅ | ✅ |
| ✅ P10 | Admin home KPIs + gestión usuarios | admin | Panel admin (gobierno) | ✅ | ✅ | ✅ | ✅ |
| ✅ P11 | Escritura centros + settings banco | admin, bank | Admin bancos / bank settings | ✅ | ✅ | ✅ | ✅ |
| P12 | Notificaciones UX + prefs | donor, bank, admin | Todos los paneles | — | — | — | — |
| P13 | Elegibilidad + impacto donante | donor | CU1 (polish) | — | — | — | — |
| P14 | Ciclo solicitudes + no-show + traza | bank, admin | CU2/CU3 + trazabilidad | — | — | — | — |
| P15 | Reportes admin | admin | Panel admin (analítica) | — | — | — | — |
| P16 | Admin cambio de rol (+ vincular centro) | admin | Gobierno de cuentas (promote a banco) | — | — | — | — |

Al cerrar una fase, marcar ✅ en el título y en las celdas de pilares del mapa.

**Lectura del mapa:** P1–P3 son FE-first (DB/BE a menudo `Sin cambios`). P4–P9 asumen esquema ya provisionado y concentran BE+FE; DB solo si hay ajuste de seed/constraints. P10 cierra gobierno admin de cuentas (activar/desactivar). P11 cierra escritura de centros y settings de banco. P12–P16 son backlog: polish de notificaciones/donante, ciclo de solicitudes, reportes y **cambio de rol con vínculo a centro**.

---

## Relaciones (referencia al implementar FKs)

```
users 1──* email_verification_tokens
users 1──0..1 donor_profiles
users 1──0..1 bank_profiles ──> donation_centers
donation_centers 1──* appointments *──1 users(donor)
appointments 0..1──1 donations
donations 1──* blood_units
donation_centers 1──* inventory
donation_centers 1──* inventory_movements
medical_institutions 1──* requests *──0..1 donation_centers
alerts → donation_centers (+ request opcional)
notifications → users
audit_log → users
achievements ←→ donor_achievements → users
donation_policies (global si center_id NULL, o por center)
```

Orden en `database/01_init.sql`: DROP hijos → padres; CREATE padres → hijos.  
Detalle de columnas: [database/ERD.md](./database/ERD.md).

---

## Reglas al implementar

1. MySQL solo en `database/` (`01_init.sql`, `02_seed.sql`, futuros `03_*.sql` solo si hace falta migraciones incrementales).
2. `DROP`/`CREATE` + seeds idempotentes (`INSERT IGNORE` / upserts) para `provision.sh`.
3. InnoDB, utf8mb4, FKs en tablas nuevas.
4. Estados e identificadores en inglés (VARCHAR + CHECK o validación en app) = mismos valores en API JSON. Textos/UI al usuario pueden seguir en español.
5. No persistir derivados de UI (color de stock, “vidas salvadas” si es fórmula).
6. PII/médicos: no loguear en claro; solo hash de passwords y de tokens de verificación.
7. Inventario siempre en transacción con su movimiento.
8. Seed de demo: 3 roles + 2 centros (1 inactivo) + stock mixto (1 tipo crítico) + 1 solicitud pendiente + políticas + logros — mantener coherente al tocar `02_seed.sql`.
9. Misma fase = mismos nombres en SQL, repositorio, JSON y (si aplica) labels FE.
10. Cada fase cierra con los 4 pilares revisados; Config incluye docs de cómo probar en Docker.
11. Rutas internas FE: `/dashboard/{donor,bank,admin}/…`. No reintroducir `/panel/`.
12. Endpoints de dominio protegidos con `AuthMiddleware` + chequeo de rol.
13. Formularios → API (UX): seguir [DESIGN.md](./DESIGN.md) → *Formularios que envían datos al servidor*.
    - Sin spinner en el botón; feedback con alert.
    - Edición: submit `disabled` hasta que haya cambios (dirty); disabled en gris.
    - Alert en sticky bar a ancho completo cuando exista `page-sticky-bar`.
    - Éxito/info ~7 s con fade; error persistente.
    - Referencia: `frontend/pages/dashboard/donor/profile/`.

---

## Fuera de alcance (no implementar en P0–P15)

Queda **fuera** incluso del backlog P11–P16:

- HL7/FHIR, OAuth de terceros, compliance HIPAA/GDPR completo, sync realtime (usar polling REST).
- App móvil nativa, push FCM/APNs (notificaciones in-app: P8 + ampliación UX en P12).
- Portal completo para hospitales / rol `hospital` / personal médico como rol distinto de `bank` (en P14 las solicitudes se crean por admin/banco; no hay dashboard de institución).
- Roles adicionales (`nurse`, `doctor`, etc.): el sistema sigue con `donor` | `bank` | `admin`.

---

## Referencias

| Recurso | Ruta |
|---------|------|
| Plan (este archivo) | `plan.md` |
| Funcionalidades producto | `README.md` |
| Sistema de diseño | `DESIGN.md` |
| ERD | `database/ERD.md` |
| FE auth | `frontend/pages/{login,register,confirm-email}/` |
| FE dashboards | `frontend/pages/dashboard/{donor,bank,admin}/` |
| Auth guard / API client | `frontend/js/auth-guard.js`, `frontend/js/api.js` |
| DB / provision | `database/README.md`, `database/01_init.sql`, `database/02_seed.sql` |
| Docker | `DOCKER.md`, `README.md` (sección Cómo Empezar) |
| Compose / Nginx / Apache | `docker-compose.yml`, `nginx-frontend.conf`, `apache-api.conf` |
