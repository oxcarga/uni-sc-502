# Sistema de Diseño — Pulso Solidario

Sistema de diseño del proyecto: tokens, tipografía, componentes y responsividad.

## Paleta de Colores

| Color | Valor | Uso |
|-------|-------|-----|
| **Primario (Teal)** | `#00685f` | Elemento principal, botones de acción, encabezados |
| **Secundario (Navy)** | `#545f73` | Elementos secundarios, subtítulos, estructuras |
| **Terciario (Rosa)** | `#b90538` | Alertas críticas, acciones urgentes, "Pulso" |
| **Fondo** | `#f8f9ff` | Fondo general de la interfaz |
| **Superficie** | `#ffffff` | Tarjetas, paneles, contenedores |
| **Error** | `#ba1a1a` | Validaciones negativas, errores |

## Tipografía

- **Titulares (Manrope)**: Moderna, equilibrada y confiable
  - Titular XL: 48px (escritorio), 32px (móvil)
  - Titular LG: 32px (escritorio), 24px (móvil)
  - Titular MD: 24px

- **Cuerpo (Inter)**: Legible, sistemática y óptima para datos
  - Cuerpo LG: 18px
  - Cuerpo MD: 16px (estándar)
  - Cuerpo SM: 14px
  - Etiqueta MD: 12px

## Componentes

### Botones
- **Primarios**: Relleno sólido teal con texto blanco
- **Secundarios**: Contorno en teal con fondo transparente
- **Estados**: Normal, al pasar el cursor (oscurecimiento), activo, deshabilitado
- **Bordes redondeados**: `0.5rem` (lg)
- **Disabled**: gris (`#94a3b8`), no el azul por defecto de Bootstrap. Override en `custom.css` (`.btn-primary` → `--bs-btn-disabled-*`).

### Formularios que envían datos al servidor

Patrón de referencia: perfil donante (`frontend/pages/dashboard/donor/profile/`).

#### Botón de envío (guardar / crear / actualizar)
1. **Sin spinner en el botón.** La API local suele responder tan rápido que el blink se ve raro. El feedback va en un alert, no en el botón.
2. En formularios de **edición** (hay un estado ya cargado): el submit arranca **`disabled`** y solo se habilita cuando el usuario **modificó algo** respecto al último estado cargado/guardado (dirty check). Tras un guardado exitoso, vuelve a `disabled`.
3. En formularios de **creación** (login, registro, “nuevo X”): el botón puede quedarse siempre habilitado; no aplica dirty check.
4. Campos no editables (p. ej. correo): usar `disabled` (no solo `readonly`) para que no parezcan editables.
5. Textos del UI orientados al usuario: no mencionar tablas, columnas ni detalles de arquitectura (`donor_profiles`, etc.).

#### Alertas / mensajes al usuario
1. Tras enviar al backend, mostrar el resultado en un **alert** Bootstrap (`alert-success` / `alert-danger` / `alert-info`).
2. En páginas con **`page-sticky-bar`**: el alert vive **dentro** del sticky bar, en una fila a **todo el ancho** (debajo del título/acciones), para que se vea aunque la página esté scrolleada. CSS: `.page-sticky-bar > .alert { flex: 1 0 100%; }`.
3. **Éxito / info**: auto-ocultar a los **~7 s** con fade-out (`opacity` + clase `is-fading`).
4. **Error** (`alert-danger`): **persistente** hasta el siguiente mensaje o acción del usuario. No auto-ocultar.
5. No mostrar mensajes de “cargado desde el servidor” al abrir el formulario; solo feedback de **error de carga**, **error al guardar** o **éxito al guardar**.
6. `aria-live="polite"` en el contenedor del alert.

### Tarjetas
- Fondo blanco con borde 1px en gris muy claro
- Para contenido destacado: efecto de glasmorfismo con desenfoque de fondo
- Relleno interno: 24px

### Campos de Entrada
- Fondo en neutro-50 con borde 1px claro
- Al enfocar: borde teal con resplandor suave
- Radio: `0.5rem`

### Indicador de Pulso
- Componente único: punto animado con brillo en rosa terciario
- Indica datos en vivo o alertas urgentes
- Respeta `prefers-reduced-motion`

### Listados
- Alta densidad sin bordes innecesarios
- Espaciado generoso vertical
- Rayado alternado sutil para legibilidad

## Diseño responsivo

- **Escritorio primero**: Diseñado primero para pantallas grandes
- **Rejilla**: 12 columnas en escritorio, 4 en móvil
- **Barra lateral**: Barra lateral en escritorio → Barra superior horizontal en móvil
- **Puntos de quiebre**:
  - Escritorio: >1024px (12 columnas)
  - Tableta: 768px-1024px (8 columnas)
  - Móvil: <768px (4 columnas)
