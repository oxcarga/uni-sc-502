# Plan de implementación — Pulso Solidario

> Documento de **acción** para agentes y desarrolladores. Cubre la app completa, no solo la base de datos.
> Cada fase debe avanzar los **cuatro pilares** en el mismo cambio (aunque alguno sea “sin cambios” documentado).

**Pilares (siempre en este orden dentro de cada fase):**

| Pilar | Ámbito | Rutas típicas |
|-------|--------|----------------|
| **DB** | Esquema MySQL + seeds | `database/01_init.sql`, `02_seed.sql`, `provision.sh` |
| **BE** | API PHP (Slim) | `backend/src/{Controllers,Repositories,Routes}/`, `backend/public/index.php` |
| **FE** | UI estática + JS | `frontend/` |
| **Config** | Docker, Nginx, Apache, env, docs de arranque | `docker-compose.yml`, `Dockerfile`, `nginx-frontend.conf`, `apache-api.conf`, `.env.example`, `DOCKER.md`, `docker-entrypoint.sh` |

**Motor:** MySQL 8.0 · BD `pulso_solidario`  
**Convención:** tablas/columnas, claves JSON, roles y rutas HTTP en inglés (p. ej. `users`, `first_name`, `/api/auth/confirm-email`).  
**Última actualización:** 2026-07-15

Operación Docker/provision: [DOCKER.md](./DOCKER.md) · [database/README.md](./database/README.md).

---

## Cómo usar este plan

1. Implementar **una fase a la vez**, en orden (P0 → P0b → P0c → P1 → … → P6).
2. En cada fase cubrir los **4 pilares** (DB, BE, FE, Config). Si un pilar no aplica, dejar la subsección con `Sin cambios` y una línea de por qué.
3. Tras cambios de esquema: re-provisionar (`database/provision.sh` o `docker compose down -v && up -d`).
4. Al cerrar una fase: marcar checklist, poner ✅ al inicio del título `## Px` y en el mapa, y pasar a la siguiente.
5. Constantes de dominio (no negociar en cada PR):

| Concepto | Valores |
|----------|---------|
| Roles | `donor`, `bank`, `admin` |
| Tipos de sangre | `O+`, `O-`, `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-` |
| Umbrales inventario | saludable `>100`, moderado `50–100`, crítico `<50` (por tipo y centro) |
| Confirmación de correo | token de un solo uso, caduca en 24 h; sin confirmar no hay login |

---

## Punto de partida (baseline)

Estado previo a P0 (referencia histórica):

| Pilar | Contenido |
|-------|-----------|
| DB | `users` básico; seed de 4 correos del equipo |
| BE | CRUD `/api/users` |
| FE | `login/` stub y `registro/` parcial |
| Config | Compose (backend/frontend/db/phpmyadmin), Nginx proxy `/api` → backend |

La siguiente fase es **P1**.

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
| `blood_type` | **mantener por ahora** (se mueve en P1) |

Índices: UNIQUE `email`; índice en `role`.

**Seed:** 3 roles con `password_hash` real; separar `first_name`/`last_name`; documentar contraseña demo; al menos un usuario `active = 0` para pruebas.

### BE

1. `UserRepository`: columnas nuevas; `findByEmail`; nunca exponer `password_hash`.
2. `UserController` create/update: `first_name`, `last_name`, `email`, `password`, `blood_type` opcional; hash; `role = donor` en registro público.
3. Email duplicado → 409 (`23000` / MySQL `1062`).
4. `POST /api/auth/login`: email + password → `password_verify` + `active` → sesión (`id`, `first_name`, `last_name`, `email`, `role`).
5. Login fallido / inactivo → 401 genérico.

### FE

1. `register.js` + HTML: enviar `first_name`, `last_name`, `email`, `password` por separado.
2. `login.js`: llamar login; guardar sesión; redirigir según `role` (paneles placeholder ok).
3. Errores de API visibles en ambos formularios.
4. Placeholders `frontend/panel/{donor,bank,admin}/`.

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
2. `confirm-email/`: consumir token → `saveSession` → redirect al panel por `role`.
3. Login: mostrar 403 de correo no confirmado (+ CTA reenviar opcional).

### Config

1. Añadir servicio de correo local (p. ej. **Mailhog** o similar) en `docker-compose.yml`, o documentar alternativa (log del enlace en logs del backend).
2. Variables en `.env.example`: host SMTP, puerto, `APP_URL` / base del enlace de confirmación, from.
3. Pasar esas vars al contenedor `backend`; actualizar `DOCKER.md` / `QUICKSTART.md` (cómo ver el mail en local, URL típica Mailhog).
4. Nginx: servir `frontend/confirm-email/` (try_files ya cubre rutas estáticas; verificar).

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

**Objetivo:** tener sesión de usuario real (no solo `sessionStorage` en el cliente) y proteger rutas internas `/panel/*`.

**Hueco actual:** tras login/confirm el FE guarda un objeto en `sessionStorage` (`pulso_session`), pero no hay sesión de servidor ni cookie/token verificable. Cualquiera puede abrir `/panel/donor|bank|admin/` sin autenticarse.

### DB

Sin cambios de esquema en P0c (sesión vía cookie de servidor o token firmado, sin tabla `sessions`). Si más adelante se necesitan refresh tokens persistidos, documentarlo en una fase posterior.

### BE

1. Tras login y confirm-email exitosos: emitir sesión de servidor (preferido: cookie HttpOnly de sesión PHP; alternativa: token firmado simple).
2. `GET /api/auth/me`: devolver el usuario de la sesión activa o 401.
3. `POST /api/auth/logout`: invalidar sesión/cookie.
4. Introducir middleware/patrón de auth reutilizable para endpoints protegidos (en P0c al menos para `/auth/me`; el resto de APIs de panel lo usarán desde P1).
5. Login/confirm-email deben establecer la misma forma de sesión (no solo devolver JSON de usuario).

### FE

1. Dejar de tratar `sessionStorage` como única fuente de verdad: la sesión válida es la del servidor (`/api/auth/me`); cache local del perfil es opcional.
2. Módulo guard (p. ej. `frontend/js/auth-guard.js`) cargado en todas las páginas `/panel/*`:
   - Sin sesión → redirect a `/login/`
   - Con sesión pero `role` distinto al panel visitado → redirect al panel correcto (o mensaje 403 UX)
3. Logout visible en los placeholders de panel (llama a `/api/auth/logout` y limpia cache local).
4. Alinear `login.js` y `confirm-email.js` con la nueva sesión (credentials/cookies en `fetch` si aplica).

### Config

1. Documentar cookie: `SameSite`, `Secure` (local vs production) en `.env.example` / `DOCKER.md` si hace falta.
2. Asegurar que el proxy Nginx reenvía cookies al backend (`/api/`).
3. Nota: el FE estático no puede ocultar el HTML; el guard es UX + la API exige sesión. No bloquear `/panel/*` solo en Nginx.

### Listo cuando

- [x] Login/confirm crean sesión verificable con `GET /api/auth/me`
- [x] Logout cierra la sesión
- [x] Visitar `/panel/*` sin sesión redirige a `/login/`
- [x] Usuario `donor` no permanece en `/panel/admin` (y análogo por rol)
- [x] Sin sesión no se puede “usar” la app como autenticado vía API protegida

**Desbloquea:** paneles internos seguros antes de P1+.

---

## P1 — Perfil de donante y centros

**Objetivo:** separar datos clínicos del donante de la cuenta; listar centros de donación.

### DB

1. `donor_profiles`: `user_id` PK/FK, `blood_type`, `birth_date`, `medical_history`, `eligible`, `last_donation_at`.
2. `donation_centers`: `name`, `address`, `region`, `lat`, `lng`, `contact`, `active`.
3. Opcional: `bank_profiles` (`user_id`, `center_id`).
4. **Quitar** `blood_type` de `users`.
5. Al registrar donante: insertar fila vacía en `donor_profiles` (misma transacción).

**Seed:** ≥1 centro activo; perfiles donante con al menos un `blood_type`; usuario `bank` ligado si hay `bank_profiles`.

### BE

- Endpoints perfil donante (GET/PUT propio) y listado de centros.
- Dejar de leer/escribir `blood_type` en `users`.
- Registro crea perfil vacío en la misma transacción.

### FE

- UI “completar perfil” o campos en registro para tipo de sangre → `donor_profiles`.
- Listado/mapa básico de centros (puede ser lista simple primero).
- Panel donante: sección perfil (aunque sea mínima).

### Config

1. Re-provision obligatorio (DROP columnas / tablas nuevas).
2. Si el mapa usa tiles externos, documentar CSP/permisos en Nginx solo si hace falta; si no, `Sin cambios` de red.
3. Actualizar `database/README.md` con tablas nuevas en ejemplos de verificación.

### Listo cuando

- [ ] No existe `users.blood_type`
- [ ] Todo donante tiene fila en `donor_profiles`
- [ ] Se listan centros desde la API y se ven en FE
- [ ] Stack Docker vuelve a quedar consistente tras re-provision

**Desbloquea:** perfil médico, mapa/listado de centros.

---

## P2 — Citas y donaciones

**Objetivo:** agendar donaciones y guardar historial.

### DB

**`appointments`:** `donor_id`, `center_id`, `scheduled_at`, `status` (`pending`\|`confirmed`\|`completed`\|`cancelled`\|`no_show`), timestamps.

**`donations`:** `donor_id`, `center_id`, `appointment_id` NULL, `blood_type`, `units` DEFAULT 1, `donated_at`, `certificate_code` NULL.

Al completar cita → crear `donations` + actualizar `donor_profiles.last_donation_at` (y `elegible` si hay regla simple).

**Seed:** citas en distintos estados; ≥1 donación completada.

### BE

- CRUD/agenda de citas para donante.
- Historial de donaciones.
- Acción “completar cita” (banco/admin) que materializa la donación.

### FE

- Panel donante: agendar cita + historial.
- Panel banco: listado de citas del centro y acción completar.

### Config

1. Re-provision tras nuevas tablas.
2. Revisar timeouts/body size de Apache/Nginx solo si se suben adjuntos (certificados); si no, `Sin cambios`.
3. Documentar flujo demo (seed) en `DOCKER.md` o README corto de la fase.

### Listo cuando

- [ ] Donante agenda cita en un centro (FE → BE → DB)
- [ ] Completar cita crea `donations` y actualiza perfil
- [ ] Seed reproducible en Docker

**Desbloquea:** agenda + historial (base CU1 citas).

---

## P3 — Inventario del banco

**Objetivo:** stock en vivo y libro de movimientos.

### DB

**`inventory`:** `center_id`, `blood_type`, `units`, UNIQUE(`center_id`, `blood_type`).

**`inventory_movements`:** append-only — `center_id`, `type` (`receipt`\|`assignment`\|`adjustment`\|`discard`), `quantity`, FKs opcionales, `user_id`, `detail`, `created_at`.

**`blood_units`** (recomendado ya en P3): `code` UNIQUE, `donation_id`, `center_id`, `blood_type`, `status`, fechas.

**Regla:** toda variación de stock = una transacción (movimiento + update inventario [+ estado unidad]).  
Saludable/moderado/crítico = **calculado** (hardcode P3 o políticas en P5); no guardar color en BD.

**Seed:** inventario mixto; **≥1 tipo en crítico** (`<50`).

### BE

- API inventario por centro + recepción (p. ej. tras donación).
- Cálculo de nivel de stock para el FE.

### FE

- Panel banco: tarjetas/listado por tipo de sangre con nivel visual.

### Config

1. Re-provision + verificar seed crítico en phpMyAdmin o `mysql` CLI (doc en `database/README.md`).
2. Sin servicios nuevos salvo que se añada worker; si no, `Sin cambios` de Compose.
3. Asegurar que el backend tiene timezone/DB charset utf8mb4 ya configurados (revisión, no reescritura).

### Listo cuando

- [ ] Consulta de stock por tipo (API + UI banco)
- [ ] Recepción actualiza inventario y deja movimiento
- [ ] Seed muestra al menos un tipo crítico

**Desbloquea:** panel banco (inventario en vivo).

---

## P4 — Solicitudes médicas y alertas

**Objetivo:** CU2 (alerta crítica) y CU3 (solicitud hospitalaria).

### DB

**`medical_institutions`**, **`requests`**, **`alerts`** (campos y estados en inglés en JSON/BD).

**Flujos:**

1. Solicitud → asignar centro → stock → asignar unidades + movimiento `assignment` → estado.
2. Alerta al cruzar umbral; resolver al recuperar stock.
3. Donantes compatibles vía perfil (`blood_type` + `eligible`); notificaciones en P5.

**Seed:** 1 institución, 1 solicitud `pending`, 1 alerta `active` sobre el tipo crítico de P3.

### BE

- Endpoints cola de solicitudes, asignación, listado/activación de alertas.
- Reglas de stock en transacción con movimientos.

### FE

- Panel banco/admin: cola de solicitudes + detalle de alertas.
- Indicador visual de alerta crítica para el tipo en bajo stock.

### Config

1. Re-provision con seed de demo CU2/CU3.
2. Si hay job/cron de alertas: documentar en Compose/entrypoint o script; si es síncrono al actualizar inventario, `Sin cambios` de scheduler.
3. Actualizar puertos/URLs de demo en docs si se añaden páginas nuevas.

### Listo cuando

- [ ] Cola de solicitudes operable (FE + BE + DB)
- [ ] Asignar unidades deja trazabilidad (movimiento + estado unidad)
- [ ] Alerta crítica visible para el tipo en stock bajo

**Desbloquea:** CU2 y CU3 (sin push aún).

---

## P5 — Notificaciones, auditoría y políticas

**Objetivo:** cerrar paneles admin/donante con config de negocio y avisos.

### DB

**`donation_policies`**, **`notifications`**, **`audit_log`** (append-only).

Dejar de hardcodear umbrales en código de app.

### BE

- Emitir notificación en: alta, cita próxima, alerta crítica, solicitud atendida.
- Admin: CRUD/listado de políticas; consulta de auditoría.
- Marcar notificación leída.

### FE

- Centro de notificaciones (donante/banco).
- Panel admin: políticas + auditoría (UI mínima usable).

### Config

1. Variables de políticas por defecto solo como seed (no secretos en Compose).
2. Retención/volumen de logs de app: revisar `Logger` y volúmenes Docker si hace falta persistir.
3. Documentar endpoints admin y cómo cargar seed de políticas.

### Listo cuando

- [ ] Umbrales salen de `donation_policies`
- [ ] Alerta crítica genera notificaciones a donantes compatibles
- [ ] Acciones admin relevantes quedan en `audit_log`
- [ ] UI de notificaciones usable en al menos un rol

**Desbloquea:** paneles + CU2 con aviso a donantes.

---

## P6 — Logros (gamificación)

**Objetivo:** insignias del panel donante.

### DB

**`achievements`** (catálogo) + **`donor_achievements`** (`user_id`, `achievement_id`, `progress`, `unlocked_at`).

Evaluar al completar `donations`.

**Seed:** catálogo básico (1ª donación, N donaciones, etc.).

### BE

- Evaluación al completar donación; endpoint(s) de logros del donante autenticado.

### FE

- Sección “Logros” en panel donante (lista/insignias).

### Config

1. Re-provision con catálogo.
2. `Sin cambios` de servicios salvo assets estáticos nuevos en `frontend/`.
3. Nota breve en docs de demo: cómo desbloquear el primer logro con el seed.

### Listo cuando

- [ ] Seed con catálogo básico
- [ ] Completar donaciones desbloquea al menos un logro visible en API y FE

**Desbloquea:** sección “Logros” del panel donante.

---

## Mapa fase → entrega → pilares

| Fase | Entrega | Casos | DB | BE | FE | Config |
|------|---------|-------|----|----|----|--------|
| ✅ P0 | Cuentas + login/registro | CU0, CU1 (cuenta) | ✅ | ✅ | ✅ | ✅ |
| ✅ P0b | Confirmación de correo | CU1 (cuenta verificada) | ✅ | ✅ | ✅ | ✅ |
| ✅ P0c | Sesión servidor + auth guard `/panel/*` | CU0 (sesión) | ✅ | ✅ | ✅ | ✅ |
| P1 | Perfil donante + centros | CU1 (perfil) | ☐ | ☐ | ☐ | ☐ |
| P2 | Citas + donaciones | CU1 (agenda/historial) | ☐ | ☐ | ☐ | ☐ |
| P3 | Inventario + movimientos | Base CU2/CU3 | ☐ | ☐ | ☐ | ☐ |
| P4 | Solicitudes + alertas | CU2, CU3 | ☐ | ☐ | ☐ | ☐ |
| P5 | Notificaciones + políticas + auditoría | CU2 completo + admin | ☐ | ☐ | ☐ | ☐ |
| P6 | Logros | Panel donante | ☐ | ☐ | ☐ | ☐ |

Al cerrar una fase, marcar ✅ en el título y en las celdas de pilares del mapa.

---

## Relaciones (referencia al implementar FKs)

```
users 1──* email_verification_tokens
users 1──1 donor_profiles
users 1──1 bank_profiles ──> donation_centers
donation_centers 1──* appointments *──1 users(donor)
appointments 0..1──1 donations
donations 1──* blood_units
donation_centers 1──* inventory
donation_centers 1──* inventory_movements
medical_institutions 1──* requests *──0..1 donation_centers
alerts → centros (+ solicitud opcional)
notifications → users
audit_log → users
achievements ←→ donor_achievements → users
donation_policies (global o por center)
```

Orden en `database/01_init.sql`: DROP hijos → padres; CREATE padres → hijos.

---

## Reglas al implementar

1. MySQL solo en `database/` (`01_init.sql`, `02_seed.sql`, futuros `03_*.sql`).
2. `DROP`/`CREATE` + seeds idempotentes (`INSERT IGNORE` / upserts) para `provision.sh`.
3. InnoDB, utf8mb4, FKs en tablas nuevas.
4. Estados e identificadores en inglés (VARCHAR + CHECK o validación en app) = mismos valores en API JSON. Textos/UI al usuario pueden seguir en español.
5. No persistir derivados de UI (color de stock, “vidas salvadas” si es fórmula).
6. PII/médicos: no loguear en claro; solo hash de passwords y de tokens de verificación.
7. Inventario siempre en transacción con su movimiento.
8. Seed de demo: 3 roles + 1 centro + stock mixto (1 tipo crítico) + 1 solicitud pendiente cuando existan esas tablas.
9. Misma fase = mismos nombres en SQL, repositorio, JSON y (si aplica) labels FE.
10. Cada fase cierra con los 4 pilares revisados; Config incluye docs de cómo probar en Docker.

---

## Fuera de alcance (no implementar en estas fases)

- HL7/FHIR, OAuth de terceros, compliance HIPAA/GDPR completo, sync realtime (usar polling REST).

---

## Referencias

| Recurso | Ruta |
|---------|------|
| Plan (este archivo) | `proyecto/src/plan.md` |
| Funcionalidades producto | `proyecto/README.md` |
| Pantallas objetivo (estáticos) | `proyecto/estaticos/pages/{donor,admin,bank}.html` |
| FE auth en vivo | `proyecto/src/frontend/{login,register}/` |
| FE confirmación (P0b) | `proyecto/src/frontend/confirm-email/` |
| DB / provision | `proyecto/src/database/README.md` |
| Docker | `proyecto/src/DOCKER.md`, `QUICKSTART.md` |
| Compose / Nginx / Apache | `docker-compose.yml`, `nginx-frontend.conf`, `apache-api.conf` |
