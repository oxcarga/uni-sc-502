import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import './styles/main.css';
import './styles/custom.css';
import { initRouter } from './router/index.js';

const app = document.querySelector('#app');

if (!app) {
  throw new Error('No se encontró el elemento #app');
}

initRouter(app);
