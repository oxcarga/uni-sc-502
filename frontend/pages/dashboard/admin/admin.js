import { adminApi } from '/js/api.js';

async function loadDashboard() {
  try {
    const result = await adminApi.getDashboard();
    const data = result.data ?? {};

    document.querySelector('#kpi-banks').textContent = data.banks ?? 0;
    document.querySelector('#kpi-donors').textContent = data.donors ?? 0;
    document.querySelector('#kpi-alerts').textContent = data.active_alerts ?? 0;
    document.querySelector('#kpi-requests').textContent = data.pending_requests ?? 0;
  } catch (error) {
    console.error('Error al cargar dashboard admin:', error);
    const status = document.querySelector('#dashboard-status');
    if (status) {
      status.textContent = 'No se pudo cargar el resumen. Recarga la página o revisa la sesión.';
      status.classList.remove('d-none');
    }
  }
}

loadDashboard();
