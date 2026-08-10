const { bankApi } = await import(`/js/api.js?t=${Date.now()}`);

const gridEl = document.getElementById('home-inventory-grid');
const criticalListEl = document.getElementById('home-critical-list');
const apptBody = document.querySelector('#home-appointments-table tbody');
const invBadge = document.getElementById('home-inv-badge');
const apptBadge = document.getElementById('home-appt-badge');
const requestsBody = document.querySelector('#home-requests-table tbody');
const requestsBadge = document.getElementById('home-requests-badge');
const alertsBody = document.getElementById('home-alerts-list');
const homeStatusEl = document.getElementById('home-status');

const LEVEL_META = {
  healthy: { card: '', status: 'inv-status--ok', label: 'Saludable' },
  moderate: { card: 'inv-card--warn', status: 'inv-status--warn', label: 'Moderado' },
  critical: { card: 'inv-card--critical', status: 'inv-status--crit', label: 'Crítico' },
};

const STATUS_LABELS = {
  pending: 'Pendiente',
  confirmed: 'Confirmada',
  completed: 'Completada',
  cancelled: 'Cancelada',
  no_show: 'No asistió',
  assigned: 'Asignada',
  in_transit: 'En tránsito',
};

const STATUS_BADGE = {
  pending: 'badge-soft--amber',
  confirmed: 'badge-soft--teal',
  completed: 'badge-soft--green',
  cancelled: 'badge-soft--slate',
  no_show: 'badge-soft--rose',
  assigned: 'badge-soft--teal',
  in_transit: 'badge-soft--amber',
};

const PRIORITY_LABELS = {
  low: 'Baja',
  normal: 'Normal',
  critical: 'Crítica',
};

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function clearStatusTimers() {
  if (statusHideTimer) clearTimeout(statusHideTimer);
  if (statusFadeTimer) clearTimeout(statusFadeTimer);
  statusHideTimer = null;
  statusFadeTimer = null;
}

function hideStatus() {
  if (!homeStatusEl) return;
  clearStatusTimers();
  homeStatusEl.classList.add('is-fading');
  statusFadeTimer = setTimeout(() => {
    homeStatusEl.classList.add('d-none');
    homeStatusEl.classList.remove('is-fading');
    homeStatusEl.textContent = '';
  }, STATUS_FADE_MS);
}

function showStatus(message, type = 'info') {
  if (!homeStatusEl) return;
  clearStatusTimers();
  homeStatusEl.textContent = message;
  homeStatusEl.classList.remove('d-none', 'is-fading', 'alert-success', 'alert-danger', 'alert-info');
  homeStatusEl.classList.add(`alert-${type}`);
  if (type !== 'danger') {
    statusHideTimer = setTimeout(hideStatus, STATUS_AUTO_HIDE_MS);
  }
}

function barWidth(units, healthyMin = 101) {
  const ref = Math.max(Number(healthyMin) || 101, 1);
  return Math.min(100, Math.round((Number(units) / ref) * 100));
}

function parseDate(value) {
  const date = new Date(String(value ?? '').replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? null : date;
}

function formatTime(value) {
  const date = parseDate(value);
  if (!date) return '—';
  return date.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' });
}

function formatDate(value) {
  const date = parseDate(value);
  if (!date) return '—';
  return date.toLocaleDateString('es-CR', { day: 'numeric', month: 'short' });
}

function donorName(item) {
  return `${item.donor_first_name ?? ''} ${item.donor_last_name ?? ''}`.trim() || item.donor_email || 'Donante';
}

function renderInventory(items, healthyMin) {
  if (!gridEl) return;
  const preview = items.filter((item) => item.level === 'critical' || item.level === 'moderate').slice(0, 4);
  const list = preview.length ? preview : items.slice(0, 4);

  if (invBadge) {
    const crit = items.filter((i) => i.level === 'critical').length;
    invBadge.textContent = crit ? `${crit} críticos` : 'En línea';
  }

  gridEl.innerHTML = list
    .map((item) => {
      const level = LEVEL_META[item.level] || LEVEL_META.moderate;
      const units = Number(item.units ?? 0);
      const pulse =
        item.level === 'critical'
          ? '<span class="pulse-dot" aria-hidden="true"></span>'
          : '';
      return `
      <div class="col-sm-6 col-xl-3">
        <div class="inv-card ${level.card}">
          <div class="d-flex align-items-start justify-content-between">
            <span class="inv-card__type">${escapeHtml(item.blood_type)}</span>
            ${pulse}
          </div>
          <div class="inv-bar"><span style="width: ${barWidth(units, healthyMin)}%"></span></div>
          <span class="inv-card__count">${escapeHtml(units)} Unidades</span>
          <span class="inv-card__status ${level.status}">(${escapeHtml(level.label)})</span>
        </div>
      </div>`;
    })
    .join('');

  if (criticalListEl) {
    const critical = items.filter((item) => item.level === 'critical');
    if (!critical.length) {
      criticalListEl.innerHTML =
        '<p class="text-muted mb-0" style="font-size: 0.875rem">Sin tipos bajo el umbral crítico.</p>';
      return;
    }
    criticalListEl.innerHTML = critical
      .map(
        (item) => `
      <div class="shortage-row mb-3">
        <div class="shortage-info">
          <div class="name">${escapeHtml(item.blood_type)}</div>
          <div class="place">${escapeHtml(item.units)} unidades · crítico</div>
        </div>
        <a class="priority-tag text-decoration-none" href="/dashboard/bank/donors/?blood_type=${encodeURIComponent(item.blood_type)}">Ver donantes</a>
      </div>`
      )
      .join('');
  }
}

function renderAppointments(list) {
  if (!apptBody) return;
  const open = list
    .filter((item) => item.status === 'pending' || item.status === 'confirmed')
    .slice(0, 5);
  const rows = open.length ? open : list.slice(0, 5);

  if (apptBadge) {
    apptBadge.textContent = `${list.filter((i) => i.status === 'pending' || i.status === 'confirmed').length} abiertas`;
  }

  if (!rows.length) {
    apptBody.innerHTML =
      '<tr><td colspan="4" class="text-muted text-center py-3">No hay citas recientes.</td></tr>';
    return;
  }

  apptBody.innerHTML = rows
    .map((item) => {
      const blood = item.donor_blood_type || '—';
      return `
      <tr>
        <td class="fw-semibold">${escapeHtml(donorName(item))}</td>
        <td><span class="blood-badge blood-badge--teal">${escapeHtml(blood)}</span></td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(formatDate(item.scheduled_at))} · ${escapeHtml(formatTime(item.scheduled_at))}</td>
        <td>
          <span class="badge-soft ${STATUS_BADGE[item.status] || 'badge-soft--slate'}">
            <span class="dot"></span>${escapeHtml(STATUS_LABELS[item.status] ?? item.status)}
          </span>
        </td>
      </tr>`;
    })
    .join('');
}

function renderRequests(list) {
  if (!requestsBody) return;
  const pending = list.filter((item) => item.status === 'pending');
  if (requestsBadge) {
    requestsBadge.textContent = `${pending.length} pendientes`;
  }

  const rows = (pending.length ? pending : list).slice(0, 6);
  if (!rows.length) {
    requestsBody.innerHTML =
      '<tr><td colspan="5" class="text-muted text-center py-3">No hay solicitudes.</td></tr>';
    return;
  }

  requestsBody.innerHTML = rows
    .map((item) => {
      const canAssign = item.status === 'pending';
      return `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(item.code || `ID ${item.id}`)}</div>
          <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(item.institution_name || '')}</div>
        </td>
        <td><span class="blood-badge blood-badge--rose">${escapeHtml(item.blood_type)}</span></td>
        <td class="fw-semibold">${escapeHtml(item.quantity)}</td>
        <td>
          <span class="badge-soft ${item.priority === 'critical' ? 'badge-soft--rose' : 'badge-soft--amber'}">
            <span class="dot"></span>${escapeHtml(PRIORITY_LABELS[item.priority] ?? item.priority)}
          </span>
          <div class="text-muted mt-1" style="font-size: 0.75rem">${escapeHtml(STATUS_LABELS[item.status] ?? item.status)}</div>
        </td>
        <td class="text-end">
          ${
            canAssign
              ? `<button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold btn-assign-request" data-id="${item.id}">Asignar</button>`
              : '<span class="text-muted" style="font-size: 0.8125rem">—</span>'
          }
        </td>
      </tr>`;
    })
    .join('');
}

function renderAlerts(list) {
  if (!alertsBody) return;
  const active = list.filter((item) => item.status === 'active');
  if (!active.length) {
    alertsBody.innerHTML =
      '<p class="text-muted mb-0" style="font-size: 0.875rem">Sin alertas activas.</p>';
    return;
  }

  alertsBody.innerHTML = active
    .map((item) => {
      const title = item.message?.trim()
        ? item.message
        : `Stock crítico de ${item.blood_type}`;
      return `
    <div class="shortage-row mb-3">
      <div class="shortage-info">
        <div class="name">${escapeHtml(title)}</div>
        <div class="place">${
          item.request_code
            ? `${escapeHtml(item.blood_type)} · Solicitud ${escapeHtml(item.request_code)}`
            : `${escapeHtml(item.blood_type)} · Inventario`
        }</div>
      </div>
      <div class="shortage-actions">
        <a class="btn btn-outline-secondary btn-sm rounded-3" href="/dashboard/bank/donors/?blood_type=${encodeURIComponent(item.blood_type)}">Donantes</a>
        <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold btn-resolve-alert" data-id="${item.id}"
          title="Cierra la alerta sin modificar el inventario">Descartar</button>
      </div>
    </div>`;
    })
    .join('');
}

async function loadHome() {
  try {
    const [invPayload, apptPayload, reqPayload, alertPayload] = await Promise.all([
      bankApi.getInventory(),
      bankApi.listAppointments(),
      bankApi.listRequests(),
      bankApi.listAlerts({ status: 'active' }),
    ]);
    const items = Array.isArray(invPayload?.data?.items) ? invPayload.data.items : [];
    const healthyMin = Number(invPayload?.data?.thresholds?.healthy_min ?? 101);
    renderInventory(items, healthyMin);
    renderAppointments(
      Array.isArray(apptPayload?.data?.appointments) ? apptPayload.data.appointments : []
    );
    renderRequests(Array.isArray(reqPayload?.data?.requests) ? reqPayload.data.requests : []);
    renderAlerts(Array.isArray(alertPayload?.data?.alerts) ? alertPayload.data.alerts : []);
  } catch {
    if (gridEl) {
      gridEl.innerHTML =
        '<div class="col-12"><p class="text-muted mb-0">No se pudo cargar el resumen.</p></div>';
    }
  }
}

document.addEventListener('click', async (event) => {
  const assignBtn = event.target.closest('.btn-assign-request');
  if (assignBtn) {
    const id = Number(assignBtn.dataset.id);
    if (!id) return;
    if (!window.confirm('¿Asignar unidades del inventario a esta solicitud?')) return;
    assignBtn.disabled = true;
    try {
      await bankApi.assignRequest(id);
      showStatus('Solicitud asignada. Se actualizó inventario y unidades.', 'success');
      await loadHome();
    } catch (error) {
      showStatus(error?.message || 'No se pudo asignar la solicitud.', 'danger');
      assignBtn.disabled = false;
    }
    return;
  }

  const resolveBtn = event.target.closest('.btn-resolve-alert');
  if (resolveBtn) {
    const id = Number(resolveBtn.dataset.id);
    if (!id) return;
    resolveBtn.disabled = true;
    try {
      await bankApi.resolveAlert(id);
      showStatus('Alerta descartada. El inventario no cambia; el stock crítico sigue abajo si aún está bajo umbral.', 'success');
      await loadHome();
    } catch (error) {
      showStatus(error?.message || 'No se pudo resolver la alerta.', 'danger');
      resolveBtn.disabled = false;
    }
  }
});

await loadHome();
