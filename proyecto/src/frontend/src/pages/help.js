import { createElement } from '../utils/dom.js';

export function renderHelp() {
  const now = new Date().toLocaleString('es-CR');

  return createElement('main', { className: 'page' }, [
    createElement('h1', { className: 'page__title', textContent: '🩸 Pulso Solidario' }),
    createElement('p', {
      className: 'page__lead',
      textContent: 'Sistema de gestión de donaciones de sangre',
    }),
    createElement('ul', { className: 'status-list' }, [
      createElement('li', {}, [
        createElement('span', { className: 'status-dot status-dot--ok' }),
        createElement('span', {}, ['Frontend (Vite): ', createElement('strong', { textContent: 'activo' })]),
      ]),
      createElement('li', {}, [
        createElement('span', { className: 'status-dot status-dot--ok' }),
        createElement('span', {}, [
          'Backend API: disponible en ',
          createElement('code', { textContent: '/api/' }),
        ]),
      ]),
      createElement('li', {}, [
        createElement('span', { className: 'status-dot status-dot--ok' }),
        createElement('span', {}, [
          'Base de datos: MySQL en puerto ',
          createElement('code', { textContent: '3306' }),
        ]),
      ]),
    ]),
    createElement('div', { className: 'info-box' }, [
      createElement('strong', { textContent: 'Estructura del frontend' }),
      createElement('ul', {}, [
        createElement('li', {}, [createElement('code', { textContent: 'src/pages/' }), ' — vistas por pantalla']),
        createElement('li', {}, [createElement('code', { textContent: 'src/components/' }), ' — piezas reutilizables']),
        createElement('li', {}, [createElement('code', { textContent: 'src/services/' }), ' — llamadas a la API']),
        createElement('li', {}, [createElement('code', { textContent: 'src/styles/' }), ' — tokens y estilos globales']),
        createElement('li', {}, [createElement('code', { textContent: 'public/' }), ' — assets estáticos (favicon, etc.)']),
      ]),
    ]),
    createElement('div', { className: 'actions' }, [
      createElement('a', { className: 'btn btn--primary', href: '/api/', target: '_blank', textContent: 'Probar API' }),
      createElement('a', {
        className: 'btn btn--outline',
        href: 'http://localhost:3002',
        target: '_blank',
        textContent: 'phpMyAdmin',
      }),
    ]),
    createElement('p', { className: 'page__footer', textContent: `Última actualización: ${now}` }),
  ]);
}
