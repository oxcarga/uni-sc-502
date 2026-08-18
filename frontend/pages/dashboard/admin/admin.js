import { adminApi } from '/js/api.js';

const ACTION_LABELS = {
  'user.create': 'Creó una cuenta',
  'user.activate': 'Activó una cuenta',
  'user.deactivate': 'Desactivó una cuenta',
  'user.role_change': 'Cambió un rol',
  'policy.update': 'Actualizó políticas',
  'request.assign': 'Asignó una solicitud',
};

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function formatWhen(value) {
  const date = new Date(String(value ?? '').replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleString('es-CR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function actorName(row) {
  const name = `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim();
  return name || row.email || (row.user_id ? `Usuario #${row.user_id}` : 'Sistema');
}

function actionLabel(action) {
  return ACTION_LABELS[action] || action || '—';
}

async function loadKpis() {
  const result = await adminApi.getDashboard();
  const data = result.data ?? {};

  document.querySelector('#kpi-banks').textContent = data.banks ?? 0;
  document.querySelector('#kpi-donors').textContent = data.donors ?? 0;
  document.querySelector('#kpi-alerts').textContent = data.active_alerts ?? 0;
  document.querySelector('#kpi-requests').textContent = data.pending_requests ?? 0;
}

async function loadRecentAudit() {
  const body = document.getElementById('home-audit-body');
  const countEl = document.getElementById('home-audit-count');
  if (!body) return;

  const payload = await adminApi.listAuditLog(5);
  const list = Array.isArray(payload?.data?.entries) ? payload.data.entries : [];

  if (countEl) {
    countEl.textContent = list.length ? `${list.length} recientes` : 'Sin datos';
  }

  if (!list.length) {
    body.innerHTML =
      '<tr><td colspan="3" class="text-muted text-center py-3">Sin registros aún. Cambia políticas, asigna una solicitud o activa/desactiva un donante.</td></tr>';
    return;
  }

  body.innerHTML = list
    .map(
      (row) => `
      <tr>
        <td class="text-muted" style="font-size: 0.8125rem">${escapeHtml(formatWhen(row.created_at))}</td>
        <td>
          <div class="fw-semibold" style="font-size: 0.875rem">${escapeHtml(actorName(row))}</div>
          <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(row.email || '')}</div>
        </td>
        <td class="fw-semibold" style="font-size: 0.875rem">${escapeHtml(actionLabel(row.action))}</td>
      </tr>`
    )
    .join('');
}

async function loadDashboard() {
  const results = await Promise.allSettled([loadKpis(), loadRecentAudit()]);
  const kpiFailed = results[0].status === 'rejected';
  const auditFailed = results[1].status === 'rejected';

  if (kpiFailed) {
    console.error('Error al cargar KPIs admin:', results[0].reason);
    const status = document.querySelector('#dashboard-status');
    if (status) {
      status.textContent = 'No se pudo cargar el resumen. Recarga la página o revisa la sesión.';
      status.classList.remove('d-none');
    }
  }

  if (auditFailed) {
    console.error('Error al cargar auditoría admin:', results[1].reason);
    const body = document.getElementById('home-audit-body');
    if (body) {
      body.innerHTML =
        '<tr><td colspan="3" class="text-muted text-center py-3">No se pudo cargar la actividad reciente.</td></tr>';
    }
    const countEl = document.getElementById('home-audit-count');
    if (countEl) countEl.textContent = 'Error';
  }
}

loadDashboard();
