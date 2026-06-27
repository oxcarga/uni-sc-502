/**
 * Enrutador ligero basado en History API.
 * Añade nuevas rutas en routes y crea el módulo en src/pages/.
 */

import { renderLogin } from '../pages/login.js';
import { renderHome } from '../pages/home.js';
import { renderHelp } from '../pages/help.js';

const routes = {
  '/': renderLogin,
  '/home': renderHome,
  '/help': renderHelp,
};

function resolveRoute() {
  const path = window.location.pathname.replace(/\/+$/, '') || '/';
  return routes[path] ?? renderLogin;
}

export function initRouter(root) {
  // Renderiza la página actual
  const render = () => {
    const page = resolveRoute();
    // Reemplaza el contenido del root con la nueva página
    root.replaceChildren(page());
  };

  // Escucha los cambios en la URL
  window.addEventListener('popstate', render);
  render();

  return {
    navigate(path) {
      window.history.pushState({}, '', path);
      render();
    },
  };
}
