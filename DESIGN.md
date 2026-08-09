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
