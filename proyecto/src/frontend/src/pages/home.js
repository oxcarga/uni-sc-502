import { createElement } from '../utils/dom.js';

export function renderHome() {
  const now = new Date().toLocaleString('es-CR');

  return createElement('main', { className: 'page' }, [
    createElement('h1', { className: 'page__title', textContent: '🩸 Pulso Solidario' }),
  ]);
}
