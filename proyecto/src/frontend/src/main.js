import './styles/main.css';
import { initRouter } from './router/index.js';

const app = document.querySelector('#app');

if (!app) {
  throw new Error('No se encontró el elemento #app');
}

initRouter(app);
