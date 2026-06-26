# Frontend — Pulso Solidario (Vite)

Interfaz web del proyecto, construida con **Vite** y JavaScript vanilla. Se comunica con el backend PHP (Slim) mediante el prefijo `/api/`.

## Inicio rápido

### Con Docker (recomendado)

Desde `src/`:

```bash
docker-compose up -d
```

- **Frontend (Vite):** http://localhost:3000
- **API:** http://localhost:3001/api/
- **phpMyAdmin:** http://localhost:3002

### Sin Docker (solo frontend)

Requiere Node.js 20+. Con el backend corriendo en el puerto 3001:

```bash
cd frontend
npm install
npm run dev
```

Abre http://localhost:3000. Las peticiones a `/api/*` se redirigen al backend vía proxy de Vite.

## Scripts

| Comando | Descripción |
|---------|-------------|
| `npm run dev` | Servidor de desarrollo con HMR |
| `npm run build` | Genera la carpeta `dist/` para producción |
| `npm run preview` | Previsualiza el build de producción |

## Estructura del proyecto

```
frontend/
├── index.html              # Entrada HTML (Vite)
├── vite.config.js          # Configuración de Vite y proxy /api
├── package.json
├── public/                 # Assets estáticos servidos tal cual (favicon, etc.)
└── src/
    ├── main.js             # Punto de entrada de la aplicación
    ├── router/             # Enrutador ligero (History API)
    ├── pages/              # Vistas por pantalla (home, login, donor…)
    ├── components/         # Piezas UI reutilizables
    ├── services/           # Cliente HTTP hacia /api
    ├── styles/             # Tokens de diseño y estilos globales
    ├── utils/              # Helpers (DOM, formateo, etc.)
    └── assets/             # Imágenes e imports procesados por Vite
```

## Llamadas a la API

Usa el módulo `src/services/api.js`:

```javascript
import { usersApi } from './services/api.js';

const users = await usersApi.list();
```

En desarrollo, Vite hace proxy de `/api` hacia el contenedor `backend` (Docker) o `localhost:3001` (local). No hace falta URL absoluta.

## Añadir una nueva página

1. Crea `src/pages/mi-pagina.js` con una función `renderMiPagina()` que devuelva un nodo DOM.
2. Regístrala en `src/router/index.js`:

```javascript
import { renderMiPagina } from '../pages/mi-pagina.js';

const routes = {
  '/': renderHome,
  '/mi-pagina': renderMiPagina,
};
```

3. Navega con `router.navigate('/mi-pagina')` o un enlace `<a href="/mi-pagina">`.

## Maquetas de referencia

Las maquetas HTML estáticas del diseño viven en `../../estaticos/`. Úsalas como referencia visual al migrar pantallas a Vite.

## Notas

- El frontend **no usa PHP**; todo el UI es JavaScript + CSS.
- La carpeta `public/` de Vite es distinta de la antigua carpeta vacía eliminada: aquí van favicon, robots.txt, etc.
- Para producción, ejecuta `npm run build` y sirve el contenido de `dist/`.
