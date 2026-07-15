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
**Convención:** tablas/columnas y claves JSON en español. Rutas HTTP pueden seguir en inglés (`/api/users`) hasta renombrarlas a propósito.  
**Última actualización:** 2026-07-15

Operación Docker/provision: [DOCKER.md](./DOCKER.md) · [database/README.md](./database/README.md).

---

## Cómo usar este plan

1. Implementar **una fase a la vez**, en orden (P0 → P0b → P1 → … → P6).
2. En cada fase cubrir los **4 pilares** (DB, BE, FE, Config). Si un pilar no aplica, dejar la subsección con `Sin cambios` y una línea de por qué.
3. Tras cambios de esquema: re-provisionar (`database/provision.sh` o `docker compose down -v && up -d`).
4. Al cerrar una fase: marcar checklist, poner ✅ al inicio del título `## Px` y en el mapa, y pasar a la siguiente.
5. Constantes de dominio (no negociar en cada PR):

| Concepto | Valores |
|----------|---------|
| Roles | `donante`, `banco`, `admin` |
| Tipos de sangre | `O+`, `O-`, `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-` |
| Umbrales inventario | saludable `>100`, moderado `50–100`, crítico `<50` (por tipo y centro) |
| Confirmación de correo | token de un solo uso, caduca en 24 h; sin confirmar no hay login |

---

## Punto de partida (baseline)

Estado previo a P0 (referencia histórica):

| Pilar | Contenido |
|-------|-----------|
| DB | `usuarios` básico; seed de 4 correos del equipo |
| BE | CRUD `/api/users` |
| FE | `login/` stub y `registro/` parcial |
| Config | Compose (backend/frontend/db/phpmyadmin), Nginx proxy `/api` → backend |

La siguiente fase es **P1**.

---

## ✅ P0 — Auth y cuentas (login + registro + roles)

**Objetivo:** poder registrar un donante con contraseña, iniciar sesión y distinguir roles.

### DB

Ampliar `usuarios` en `01_init.sql`:

| Columna | Acción |
|---------|--------|
| `apellido` | `VARCHAR` NOT NULL |
| `password_hash` | `VARCHAR(255)` NOT NULL |
| `rol` | `VARCHAR` NOT NULL DEFAULT `'donante'` + CHECK (`donante`\|`banco`\|`admin`) |
| `activo` | `TINYINT(1)` NOT NULL DEFAULT 1 |
| `actualizado_el` | `TIMESTAMP` DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |
| `nombre`, `email`, `creado_el` | mantener |
| `tipo_sangre` | **mantener por ahora** (se mueve en P1) |

Índices: UNIQUE `email`; índice en `rol`.

**Seed:** 3 roles con `password_hash` real; separar `nombre`/`apellido`; documentar contraseña demo; al menos un usuario `activo = 0` para pruebas.

### BE

1. `UserRepository`: columnas nuevas; `findByEmail`; nunca exponer `password_hash`.
2. `UserController` create/update: `nombre`, `apellido`, `email`, `password`, `tipo_sangre` opcional; hash; `rol = donante` en registro público.
3. Email duplicado → 409 (`23000` / MySQL `1062`).
4. `POST /api/auth/login`: email + password → `password_verify` + `activo` → sesión (`id`, `nombre`, `apellido`, `email`, `rol`).
5. Login fallido / inactivo → 401 genérico.

### FE

1. `registro.js` + HTML: enviar `nombre`, `apellido`, `email`, `password` por separado.
2. `login.js`: llamar login; guardar sesión; redirigir según `rol` (paneles placeholder ok).
3. Errores de API visibles en ambos formularios.
4. Placeholders `frontend/panel/{donante,banco,admin}/`.

### Config

1. Confirmar que `nginx-frontend.conf` sigue proxificando `/api/` al backend.
2. Re-provision tras cambio de esquema (`provision.sh` / volumen limpio).
3. Documentar en seed/comentarios la contraseña demo local (sin secretos de producción).

### Listo cuando

- [x] Registro crea fila con hash y `rol = donante`
- [x] Login ok con seed; login con password mala → 401
- [x] Usuario `activo = 0` no puede entrar
- [x] Email duplicado → 409 en el primer intento
- [x] JSON de usuario no incluye `password_hash`

**Desbloquea:** CU0 login, CU1 registro (cuenta).

---

## ✅ P0b — Confirmación de correo

**Objetivo:** al crear una cuenta, el usuario confirma el correo; el enlace lo autentica la primera vez.

### DB

1. En `usuarios`: `correo_confirmado` `TINYINT(1)` NOT NULL DEFAULT `0`; opcional `correo_confirmado_el`.
2. Tabla `tokens_verificacion_correo`: `usuario_id` FK, `token_hash` UNIQUE, `expira_el`, `usado_el`, `creado_el`.
3. Distinguir: `activo = 0` = deshabilitada (admin); `correo_confirmado = 0` = pendiente. Login exige `activo = 1` **y** `correo_confirmado = 1`.

**Seed:** demos con `correo_confirmado = 1`. Sin seed de tokens.

### BE

1. Registro: `correo_confirmado = 0`; generar token (hash + 24 h); enviar email con enlace; 201 sin sesión automática.
2. Confirmar correo: primer uso válido → confirmar + devolver payload de sesión (como login). Reuso → error sin sesión.
3. Reenviar confirmación (respuesta genérica).
4. Login: sin confirmar → 403 específico; credenciales malas → 401.
5. No exponer tokens/`token_hash` en JSON.

### FE

1. Tras registro: **no** redirect a `/login/`; mensaje de “correo de confirmación enviado” en la misma página.
2. `confirmar-correo/`: consumir token → `saveSession` → redirect al panel por `rol`.
3. Login: mostrar 403 de correo no confirmado (+ CTA reenviar opcional).

### Config

1. Añadir servicio de correo local (p. ej. **Mailhog** o similar) en `docker-compose.yml`, o documentar alternativa (log del enlace en logs del backend).
2. Variables en `.env.example`: host SMTP, puerto, `APP_URL` / base del enlace de confirmación, from.
3. Pasar esas vars al contenedor `backend`; actualizar `DOCKER.md` / `QUICKSTART.md` (cómo ver el mail en local, URL típica Mailhog).
4. Nginx: servir `frontend/confirmar-correo/` (try_files ya cubre rutas estáticas; verificar).

### Listo cuando

- [x] Registro deja `correo_confirmado = 0` y genera token (+ envío o log del enlace)
- [x] Tras el form de registro **no** hay redirect a `/login/`; se muestra el mensaje de correo enviado
- [x] Primer clic del enlace confirma, inicia sesión y redirige al panel; segundo uso falla sin loguear
- [x] Login sin confirmar → 403; confirmada → ok
- [x] Seed demo sigue pudiendo iniciar sesión
- [x] Reenvío invalida el token anterior
- [x] En local se puede ver/abrir el correo de prueba vía Config documentada

**Desbloquea:** registro con propiedad del correo verificada.

---

## P1 — Perfil de donante y centros

**Objetivo:** separar datos clínicos del donante de la cuenta; listar centros de donación.

### DB

1. `perfiles_donante`: `usuario_id` PK/FK, `tipo_sangre`, `fecha_nacimiento`, `antecedentes_medicos`, `elegible`, `ultima_donacion_en`.
2. `centros_donacion`: `nombre`, `direccion`, `region`, `lat`, `lng`, `contacto`, `activo`.
3. Opcional: `perfiles_banco` (`usuario_id`, `centro_id`).
4. **Quitar** `tipo_sangre` de `usuarios`.
5. Al registrar donante: insertar fila vacía en `perfiles_donante` (misma transacción).

**Seed:** ≥1 centro activo; perfiles donante con al menos un `tipo_sangre`; usuario `banco` ligado si hay `perfiles_banco`.

### BE

- Endpoints perfil donante (GET/PUT propio) y listado de centros.
- Dejar de leer/escribir `tipo_sangre` en `usuarios`.
- Registro crea perfil vacío en la misma transacción.

### FE

- UI “completar perfil” o campos en registro para tipo de sangre → `perfiles_donante`.
- Listado/mapa básico de centros (puede ser lista simple primero).
- Panel donante: sección perfil (aunque sea mínima).

### Config

1. Re-provision obligatorio (DROP columnas / tablas nuevas).
2. Si el mapa usa tiles externos, documentar CSP/permisos en Nginx solo si hace falta; si no, `Sin cambios` de red.
3. Actualizar `database/README.md` con tablas nuevas en ejemplos de verificación.

### Listo cuando

- [ ] No existe `usuarios.tipo_sangre`
- [ ] Todo donante tiene fila en `perfiles_donante`
- [ ] Se listan centros desde la API y se ven en FE
- [ ] Stack Docker vuelve a quedar consistente tras re-provision

**Desbloquea:** perfil médico, mapa/listado de centros.

---

## P2 — Citas y donaciones

**Objetivo:** agendar donaciones y guardar historial.

### DB

**`citas`:** `donante_id`, `centro_id`, `fecha_hora`, `estado` (`pendiente`\|`confirmada`\|`completada`\|`cancelada`\|`no_asistio`), timestamps.

**`donaciones`:** `donante_id`, `centro_id`, `cita_id` NULL, `tipo_sangre`, `unidades` DEFAULT 1, `fecha`, `certificado_codigo` NULL.

Al completar cita → crear `donaciones` + actualizar `perfiles_donante.ultima_donacion_en` (y `elegible` si hay regla simple).

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
- [ ] Completar cita crea `donaciones` y actualiza perfil
- [ ] Seed reproducible en Docker

**Desbloquea:** agenda + historial (base CU1 citas).

---

## P3 — Inventario del banco

**Objetivo:** stock en vivo y libro de movimientos.

### DB

**`inventario`:** `centro_id`, `tipo_sangre`, `unidades`, UNIQUE(`centro_id`, `tipo_sangre`).

**`movimientos_inventario`:** append-only — `centro_id`, `tipo` (`recepcion`\|`asignacion`\|`ajuste`\|`descarte`), `cantidad`, FKs opcionales, `usuario_id`, `detalle`, `creado_el`.

**`unidades_sangre`** (recomendado ya en P3): `codigo` UNIQUE, `donacion_id`, `centro_id`, `tipo_sangre`, `estado`, fechas.

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

**`instituciones_medicas`**, **`solicitudes`**, **`alertas`** (campos y estados en español según diseño previo del plan).

**Flujos:**

1. Solicitud → asignar centro → stock → asignar unidades + movimiento `asignacion` → estado.
2. Alerta al cruzar umbral; resolver al recuperar stock.
3. Donantes compatibles vía perfil (`tipo_sangre` + `elegible`); notificaciones en P5.

**Seed:** 1 institución, 1 solicitud `pendiente`, 1 alerta `activa` sobre el tipo crítico de P3.

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

**`politicas_donacion`**, **`notificaciones`**, **`auditoria`** (append-only).

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

- [ ] Umbrales salen de `politicas_donacion`
- [ ] Alerta crítica genera notificaciones a donantes compatibles
- [ ] Acciones admin relevantes quedan en `auditoria`
- [ ] UI de notificaciones usable en al menos un rol

**Desbloquea:** paneles + CU2 con aviso a donantes.

---

## P6 — Logros (gamificación)

**Objetivo:** insignias del panel donante.

### DB

**`logros`** (catálogo) + **`donante_logros`** (`usuario_id`, `logro_id`, `progreso`, `desbloqueado_en`).

Evaluar al completar `donaciones`.

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
usuarios 1──* tokens_verificacion_correo
usuarios 1──1 perfiles_donante
usuarios 1──1 perfiles_banco ──> centros_donacion
centros_donacion 1──* citas *──1 usuarios(donante)
citas 0..1──1 donaciones
donaciones 1──* unidades_sangre
centros_donacion 1──* inventario
centros_donacion 1──* movimientos_inventario
instituciones_medicas 1──* solicitudes *──0..1 centros_donacion
alertas → centros (+ solicitud opcional)
notificaciones → usuarios
auditoria → usuarios
logros ←→ donante_logros → usuarios
politicas_donacion (global o por centro)
```

Orden en `database/01_init.sql`: DROP hijos → padres; CREATE padres → hijos.

---

## Reglas al implementar

1. MySQL solo en `database/` (`01_init.sql`, `02_seed.sql`, futuros `03_*.sql`).
2. `DROP`/`CREATE` + seeds idempotentes (`INSERT IGNORE` / upserts) para `provision.sh`.
3. InnoDB, utf8mb4, FKs en tablas nuevas.
4. Estados en español (VARCHAR + CHECK o validación en app) = mismos valores en API JSON.
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
| FE auth en vivo | `proyecto/src/frontend/{login,registro}/` |
| FE confirmación (P0b) | `proyecto/src/frontend/confirmar-correo/` |
| DB / provision | `proyecto/src/database/README.md` |
| Docker | `proyecto/src/DOCKER.md`, `QUICKSTART.md` |
| Compose / Nginx / Apache | `docker-compose.yml`, `nginx-frontend.conf`, `apache-api.conf` |
