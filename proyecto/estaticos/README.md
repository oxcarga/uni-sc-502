# Pulso Solidario — Interfaz (HTML/CSS)

Maquetas estáticas de la plataforma **Pulso Solidario**, construidas con HTML5 semántico y **Bootstrap 5** (vía CDN) más una hoja de estilos propia (`assets/css/custom.css`) que aplica la marca y los componentes a medida definidos en el sistema de diseño ([DESIGN.md](DESIGN.md)). No requieren ningún paso de compilación.

## Cómo verlo

Las páginas cargan Bootstrap y las fuentes desde un CDN, así que necesitas conexión a internet. Abre `index.html` directamente en el navegador (doble clic) o sirve la carpeta con cualquier servidor estático, por ejemplo:

```bash
python3 -m http.server 8000
# luego visita http://localhost:8000
```

El índice (`index.html`) enlaza las tres pantallas dentro de `pages/`.

## Pantallas

| Archivo | Pantalla | Referencia |
| --- | --- | --- |
| `pages/donor.html` | Panel del Donante | `screenshots/screen-donor.png` |
| `pages/admin.html` | Panel de Administración General | `screenshots/screen-admin.png` |
| `pages/bank.html` | Administración de Banco de Sangre | `screenshots/screen-bank.png` |

## Estructura

```text
proyecto/
  index.html              Punto central que enlaza las 3 pantallas
  pages/                  Las tres maquetas
    donor.html            Panel del Donante
    admin.html            Panel de Administración General
    bank.html             Administración de Banco de Sangre
  assets/
    css/custom.css        Personalización de marca y componentes sobre Bootstrap 5
    img/                  SVGs decorativos (mapa mundial, mapa de calles)
  screenshots/            Imágenes de referencia del diseño
  DESIGN.md               Lenguaje de diseño (color, tipografía, tokens)
```

## Sistema de diseño

- **Base**: Bootstrap 5.3 (grid, utilidades, componentes) cargado desde CDN.
- **Marca** (`assets/css/custom.css`): variables CSS (`:root`) con la paleta de `DESIGN.md` (teal `#00685f`, rosa `#b90538`, tinta `#0b1c30`), personalización de botones/insignias/tarjetas de Bootstrap y componentes propios (barra lateral, barra superior, mosaicos de inventario, feed de actividad, tarjetas de indicadores, mapa, logros, etc.).
- **Tipografía**: Manrope (titulares) + Inter (cuerpo), vía Google Fonts.
- **Diseño responsivo**: escritorio primero; las rejillas de Bootstrap colapsan a una sola columna en móvil y la barra lateral pasa a horizontal.

## Accesibilidad

- Landmarks semánticos (`<aside>`, `<nav>`, `<main>`, `<header>`), `aria-current="page"` en la navegación activa, `aria-label` en botones de icono, `aria-hidden` en SVGs decorativos y `alt` en imágenes.
- Respeto a `prefers-reduced-motion` para la animación del indicador de pulso.
