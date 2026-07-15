# Plan de implementación — Base de datos Pulso Solidario

> Documento de **acción** para agentes y desarrolladores. Cada fase dice qué construir, en qué archivos, y cuándo darla por terminada.
> Operación Docker/provision: [README.md](./README.md).

**Motor:** MySQL 8.0 · BD `pulso_solidario`  
**Convención:** tablas/columnas y claves JSON en español. Rutas HTTP pueden seguir en inglés (`/api/users`) hasta renombrarlas a propósito.  
**Última actualización:** 2026-07-14

---

## Cómo usar este plan

1. Implementar **una fase a la vez**, en orden (P0 → P6).
2. En cada fase tocar **SQL + seed + backend (+ frontend si aplica)** en el mismo cambio.
3. Tras cambios de esquema: re-provisionar (`provision.sh` o `docker compose down -v && up -d`).
4. Al cerrar una fase, marcar su checklist y pasar a la siguiente.
5. Constantes de dominio (no negociar en cada PR):

| Concepto | Valores |
|----------|---------|
| Roles | `donante`, `banco`, `admin` |
| Tipos de sangre | `O+`, `O-`, `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-` |
| Umbrales inventario | saludable `>100`, moderado `50–100`, crítico `<50` (por tipo y centro) |

---

## Punto de partida (baseline)

Ya existe y se parte de aquí:

| Pieza | Contenido |
|-------|-----------|
| `01_init.sql` | Tabla `usuarios` (`id`, `nombre`, `email`, `tipo_sangre`, `creado_el`, …) |
| `02_seed.sql` | 4 usuarios del equipo (sin auth completa) |
| Backend | CRUD `/api/users` (`UserRepository`, `UserController`) |
| Frontend | `login/` (stub) y `registro/` (POST create parcial) |

La siguiente fase es **P1**.

---

## P0 — Auth y cuentas (login + registro + roles)

**Objetivo:** poder registrar un donante con contraseña, iniciar sesión y distinguir roles.

### SQL (`01_init.sql`)

Ampliar `usuarios` a:

| Columna | Acción |
|---------|--------|
| `apellido` | `VARCHAR` NOT NULL |
| `password_hash` | `VARCHAR(255)` NOT NULL |
| `rol` | `VARCHAR` NOT NULL DEFAULT `'donante'` + CHECK (`donante`\|`banco`\|`admin`) |
| `activo` | `BOOLEAN`/`TINYINT(1)` NOT NULL DEFAULT 1 |
| `actualizado_el` | `TIMESTAMP` DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |
| `nombre`, `email`, `creado_el` | mantener |
| `tipo_sangre` | **mantener por ahora** (se mueve en P1) |

Índices: UNIQUE `email`; índice en `rol`.

### Seed (`02_seed.sql`)

- Insertar usuarios de los 3 roles con `password_hash` real (generar con `password_hash('…', PASSWORD_DEFAULT)` en PHP y pegar el hash).
- Separar `nombre` / `apellido`.
- Documentar en comentario del seed la contraseña de demo (solo local).

### Backend

1. Actualizar `UserRepository`: columnas nuevas en SELECT/INSERT/UPDATE; método `findByEmail`.
2. Actualizar `UserController` create/update: aceptar `nombre`, `apellido`, `email`, `password`, `tipo_sangre` opcional; hashear con `password_hash()`; guardar `rol = donante` en registro público; nunca devolver `password_hash` en JSON.
3. Detectar email duplicado con `errorInfo` (`23000` / MySQL `1062`) → 409.
4. Añadir ruta/controlador de **login** (`POST /api/auth/login` o similar): email + password → `password_verify` + `activo` → respuesta con `id`, `nombre`, `apellido`, `email`, `rol` (+ cookie/sesión o token simple).
5. Login fallido / inactivo → 401 con mensaje genérico.

### Frontend

1. `registro.js` + HTML: enviar `nombre`, `apellido`, `email`, `password` (dejar de concatenar en un solo `nombre`).
2. `login.js`: llamar al endpoint de login; guardar sesión; redirigir según `rol` (aunque el panel destino aún sea placeholder).
3. Mostrar errores de API en ambos formularios (patrón ya usado en registro).

### Listo cuando

- [x] Registro crea fila con hash y `rol = donante`
- [x] Login ok con seed; login con password mala → 401
- [x] Usuario `activo = 0` no puede entrar
- [x] Email duplicado → 409 en el primer intento
- [x] JSON de usuario no incluye `password_hash`

**Desbloquea:** CU0 login, CU1 registro (cuenta).

---

## P1 — Perfil de donante y centros

**Objetivo:** separar datos clínicos del donante de la cuenta; listar centros de donación.

### SQL

1. Crear `perfiles_donante`:
   - `usuario_id` PK/FK → `usuarios`
   - `tipo_sangre` NULL
   - `fecha_nacimiento` NULL
   - `antecedentes_medicos` TEXT NULL
   - `elegible` BOOLEAN DEFAULT false
   - `ultima_donacion_en` NULL
2. Crear `centros_donacion`: `nombre`, `direccion`, `region`, `lat`, `lng`, `contacto`, `activo`.
3. Opcional: `perfiles_banco` (`usuario_id`, `centro_id`) para usuarios `rol = banco`.
4. **Quitar** `tipo_sangre` de `usuarios`.
5. En registro (app): al crear usuario `donante`, insertar fila vacía en `perfiles_donante` (misma transacción).

### Seed

- ≥1 centro activo.
- Perfiles para donantes del seed (con al menos un `tipo_sangre` asignado).
- Un usuario `banco` ligado a un centro si existe `perfiles_banco`.

### Backend / frontend

- Endpoints de perfil donante (GET/PUT propio) y listado de centros.
- UI registro o “completar perfil”: guardar tipo de sangre en `perfiles_donante`.
- Dejar de leer/escribir `tipo_sangre` en `usuarios`.

### Listo cuando

- [ ] No existe `usuarios.tipo_sangre`
- [ ] Todo donante tiene fila en `perfiles_donante`
- [ ] Se listan centros desde la API

**Desbloquea:** perfil médico, mapa/listado de centros.

---

## P2 — Citas y donaciones

**Objetivo:** agendar donaciones y guardar historial.

### SQL

**`citas`:** `donante_id`, `centro_id`, `fecha_hora`, `estado` (`pendiente`\|`confirmada`\|`completada`\|`cancelada`\|`no_asistio`), timestamps.

**`donaciones`:** `donante_id`, `centro_id`, `cita_id` NULL, `tipo_sangre`, `unidades` DEFAULT 1, `fecha`, `certificado_codigo` NULL.

Al completar una cita → crear `donaciones` y actualizar `perfiles_donante.ultima_donacion_en` (y recalcular `elegible` si ya hay reglas simples).

### Seed

- Citas de ejemplo en distintos estados.
- ≥1 donación completada ligada a un donante del seed.

### Backend / frontend

- CRUD/agenda de citas para donante.
- Historial de donaciones (panel donante).
- Acción “completar cita” (banco o admin) que materializa la donación.

### Listo cuando

- [ ] Donante agenda cita en un centro
- [ ] Completar cita crea `donaciones` y actualiza perfil

**Desbloquea:** agenda + historial (base de CU1 paso citas).

---

## P3 — Inventario del banco

**Objetivo:** stock en vivo y libro de movimientos.

### SQL

**`inventario`:** `centro_id`, `tipo_sangre`, `unidades`, UNIQUE(`centro_id`, `tipo_sangre`).

**`movimientos_inventario`:** solo-append — `centro_id`, `tipo` (`recepcion`\|`asignacion`\|`ajuste`\|`descarte`), `cantidad`, `unidad_id` NULL, `solicitud_id` NULL, `usuario_id`, `detalle`, `creado_el`.

**`unidades_sangre`** (recomendado ya en P3): `codigo` UNIQUE, `donacion_id`, `centro_id`, `tipo_sangre`, `estado` (`disponible`\|`reservada`\|`asignada`\|`transfundida`\|`descartada`), `institucion_destino_id` NULL, fechas.

**Regla de escritura:** toda variación de stock = transacción única: movimiento + update `inventario` (+ cambio de estado de unidad si aplica).  
Estado saludable/moderado/crítico = **calculado** con umbrales (hardcode P3 o `politicas_donacion` en P5); no guardar color en BD.

### Seed

- Inventario mixto para un centro; **al menos un tipo en crítico** (`<50`) para demos.

### Backend / frontend

- API inventario por centro + registrar recepción (p. ej. tras donación).
- Panel banco: tarjetas por tipo de sangre.

### Listo cuando

- [ ] Se consulta stock por tipo
- [ ] Una recepción actualiza inventario y deja movimiento
- [ ] Seed muestra al menos un tipo crítico

**Desbloquea:** panel banco (inventario en vivo).

---

## P4 — Solicitudes médicas y alertas

**Objetivo:** CU2 (alerta crítica) y CU3 (solicitud hospitalaria).

### SQL

**`instituciones_medicas`:** nombre, región, contacto, `activo`.

**`solicitudes`:** `institucion_id`, `centro_id` NULL, `tipo_sangre`, `unidades_solicitadas`, `prioridad` (`baja`\|`media`\|`alta`\|`critica`), `estado` (`pendiente`\|`en_proceso`\|`parcial`\|`completada`\|`rechazada`\|`cancelada`), timestamps.

**`alertas`:** `centro_id`, `tipo_sangre`, `nivel` (`moderada`\|`critica`), `mensaje`, `activa`, `solicitud_id` NULL, `creada_en`, `resuelta_en`.

### Flujos a implementar

1. **Solicitud:** crear → banco asigna centro → verificar stock → asignar unidades (`unidades_sangre` + movimiento `asignacion` + bajar `inventario`) → actualizar estado solicitud.
2. **Alerta:** si stock < umbral (al actualizar inventario o job simple) → crear/activar `alerta`; al recuperar stock → `activa = false` / `resuelta_en`.
3. Búsqueda de donantes compatibles: `perfiles_donante.tipo_sangre` + `elegible` (notificaciones en P5).

### Seed

- 1 institución, 1 solicitud `pendiente`, 1 alerta `activa` sobre el tipo crítico del seed P3.

### Listo cuando

- [ ] Cola de solicitudes operable
- [ ] Asignar unidades deja trazabilidad (movimiento + estado unidad)
- [ ] Alerta crítica visible para el tipo en stock bajo

**Desbloquea:** CU2 y CU3 (sin notificaciones push aún).

---

## P5 — Notificaciones, auditoría y políticas

**Objetivo:** cerrar paneles admin/donante con config y avisos.

### SQL

**`politicas_donacion`:** clave/valor o filas tipadas (intervalo mínimo días, umbrales crítico/moderado, edad mínima, etc.). Dejar de hardcodear umbrales en código.

**`notificaciones`:** `usuario_id`, `tipo`, `titulo`, `mensaje`, `leida`, `referencia_tipo`/`referencia_id`, `creada_en`.

**`auditoria`:** solo-append — `usuario_id`, `accion`, `entidad`, `entidad_id`, `datos_antes`/`datos_despues` (JSON text), `creado_el`.

### Implementar

- Emitir notificación en: alta de usuario, cita próxima, alerta crítica, solicitud atendida.
- Admin: listar/cambiar políticas; ver auditoría de acciones sensibles (login opcional, cambios de rol, asignaciones).
- Donante/banco: centro de notificaciones (marcar leída).

### Listo cuando

- [ ] Umbrales salen de `politicas_donacion`
- [ ] Alerta crítica genera notificaciones a donantes compatibles
- [ ] Acciones admin relevantes quedan en `auditoria`

**Desbloquea:** pulido de paneles + CU2 con aviso a donantes.

---

## P6 — Logros (gamificación)

**Objetivo:** insignias del panel donante.

### SQL

**`logros`:** catálogo (`codigo`, `nombre`, `descripcion`, criterio).  
**`donante_logros`:** `usuario_id`, `logro_id`, `progreso`, `desbloqueado_en`.

Evaluar al completar `donaciones` (p. ej. 1ª donación, 5 donaciones, tipo Oro/Platino según reglas del producto).

### Listo cuando

- [ ] Seed con catálogo básico
- [ ] Completar donaciones desbloquea al menos un logro visible en API

**Desbloquea:** sección “Logros” del panel donante.

---

## Mapa fase → caso de uso

| Fase | Entrega principal | Casos |
|------|-------------------|--------|
| P0 | Cuentas + login/registro | CU0, CU1 (cuenta) |
| P1 | Perfil donante + centros | CU1 (perfil) |
| P2 | Citas + donaciones | CU1 (agenda/historial) |
| P3 | Inventario + movimientos | Base CU2/CU3 |
| P4 | Solicitudes + alertas | CU2, CU3 |
| P5 | Notificaciones + políticas + auditoría | CU2 completo + admin |
| P6 | Logros | Panel donante |

---

## Relaciones (referencia al implementar FKs)

```
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

Orden en `01_init.sql`: DROP hijos → padres; CREATE padres → hijos.

---

## Reglas al implementar

1. Solo MySQL en esta carpeta (`01_init.sql`, `02_seed.sql`, futuros `03_*.sql`).
2. `DROP`/`CREATE` + seeds idempotentes (`INSERT IGNORE` / upserts) para `provision.sh`.
3. InnoDB, utf8mb4, FKs en tablas nuevas.
4. Estados en español (VARCHAR + CHECK o validación en app) = mismos valores en API JSON.
5. No persistir derivados de UI (color de stock, “vidas salvadas” si es fórmula).
6. PII/médicos: no loguear en claro; solo hash de passwords.
7. Inventario siempre en transacción con su movimiento.
8. Seed de demo: 3 roles + 1 centro + stock mixto (1 tipo crítico) + 1 solicitud pendiente cuando existan esas tablas.
9. Misma fase = mismos nombres de columna en SQL, repositorio y JSON.

---

## Fuera de alcance (no implementar en estas fases)

- HL7/FHIR, OAuth de terceros, compliance HIPAA/GDPR completo, sync realtime (usar polling REST).

---

## Referencias de producto / UI

- `proyecto/README.md` — funcionalidades
- `proyecto/estaticos/pages/{donor,admin,bank}.html` — pantallas objetivo
- `proyecto/src/frontend/{login,registro}/` — auth en vivo
- `proyecto/src/database/README.md` — Docker / provision
