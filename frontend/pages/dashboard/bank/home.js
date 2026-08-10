const { bankApi } = await import(`/js/api.js?t=${Date.now()}`);

const gridEl = document.getElementById('home-inventory-grid');
const criticalListEl = document.getElementById('home-critical-list');
const apptBody = document.querySelector('#home-appointments-table tbody');
const invBadge = document.getElementById('home-inv-badge');
const apptBadge = document.getElementById('home-appt-badge');

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
};

const STATUS_BADGE = {
  pending: 'badge-soft--amber',
  confirmed: 'badge-soft--teal',
  completed: 'badge-soft--green',
  cancelled: 'badge-soft--slate',
  no_show: 'badge-soft--rose',
};

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
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
        <span class="priority-tag">Crítico</span>
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

try {
  const [invPayload, apptPayload] = await Promise.all([
    bankApi.getInventory(),
    bankApi.listAppointments(),
  ]);
  const items = Array.isArray(invPayload?.data?.items) ? invPayload.data.items : [];
  const healthyMin = Number(invPayload?.data?.thresholds?.healthy_min ?? 101);
  renderInventory(items, healthyMin);
  const appointments = Array.isArray(apptPayload?.data?.appointments)
    ? apptPayload.data.appointments
    : [];
  renderAppointments(appointments);
} catch {
  if (gridEl) {
    gridEl.innerHTML =
      '<div class="col-12"><p class="text-muted mb-0">No se pudo cargar el inventario.</p></div>';
  }
  if (apptBody) {
    apptBody.innerHTML =
      '<tr><td colspan="4" class="text-muted text-center py-3">No se pudieron cargar las citas.</td></tr>';
  }
}
