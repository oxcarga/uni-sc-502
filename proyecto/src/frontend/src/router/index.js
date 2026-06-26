/**
 * Enrutador ligero basado en History API.
 * Añade nuevas rutas en routes y crea el módulo en src/pages/.
 */

import { renderHome } from '../pages/home.js';

const routes = {
  '/': renderHome,
};

function resolveRoute() {
  const path = window.location.pathname.replace(/\/+$/, '') || '/';
  return routes[path] ?? renderHome;
}

export function initRouter(root) {
  const render = () => {
    const page = resolveRoute();
    root.replaceChildren(page());
  };

  window.addEventListener('popstate', render);
  render();

  return {
    navigate(path) {
      window.history.pushState({}, '', path);
      render();
    },
  };
}
