const { bankApi } = await import(`/js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('inventory-status');
const gridEl = document.getElementById('inventory-grid');
const kpiTotal = document.getElementById('kpi-total-units');
const kpiCritical = document.getElementById('kpi-critical');
const kpiCriticalDetail = document.getElementById('kpi-critical-detail');
const movementsBody = document.querySelector('#movements-table tbody');
const legendEl = document.getElementById('inventory-legend');

const receiptForm = document.getElementById('receipt-form');
const receiptStatusEl = document.getElementById('receipt-status');
const adjustForm = document.getElementById('adjust-form');
const adjustStatusEl = document.getElementById('adjust-status');

const BLOOD_TYPES = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

const LEVEL_META = {
  healthy: { card: '', status: 'inv-status--ok', label: 'Saludable' },
  moderate: { card: 'inv-card--warn', status: 'inv-status--warn', label: 'Moderado' },
  critical: { card: 'inv-card--critical', status: 'inv-status--crit', label: 'Crítico' },
};

const MOVE_LABELS = {
  receipt: 'Recepción',
  assignment: 'Asignación',
  adjustment: 'Ajuste',
  discard: 'Descarte',
};

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;
let healthyMin = 101;

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
  if (!statusEl) return;
  clearStatusTimers();
  statusEl.classList.add('is-fading');
  statusFadeTimer = setTimeout(() => {
    statusEl.classList.add('d-none');
    statusEl.classList.remove('is-fading');
    statusEl.textContent = '';
  }, STATUS_FADE_MS);
}

function showStatus(message, type = 'info') {
  if (!statusEl) return;
  clearStatusTimers();
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'is-fading', 'alert-success', 'alert-danger', 'alert-info');
  statusEl.classList.add(`alert-${type}`);
  if (type !== 'danger') {
    statusHideTimer = setTimeout(hideStatus, STATUS_AUTO_HIDE_MS);
  }
}

function showModalStatus(el, message) {
  if (!el) return;
  el.textContent = message;
  el.classList.remove('d-none');
}

function hideModalStatus(el) {
  if (!el) return;
  el.textContent = '';
  el.classList.add('d-none');
}

function barWidth(units) {
  const ref = Math.max(healthyMin, 1);
  return Math.min(100, Math.round((Number(units) / ref) * 100));
}

function renderLegend(thresholds) {
  if (!legendEl || !thresholds) return;
  const healthy = Number(thresholds.healthy_min ?? 101);
  const moderate = Number(thresholds.moderate_min ?? 50);
  const critical = Number(thresholds.critical_max ?? 49);
  legendEl.innerHTML = `
    <span class="stock-legend__item"><span class="stock-legend__swatch stock-legend__swatch--ok"></span>Saludable ≥${escapeHtml(healthy)}</span>
    <span class="stock-legend__item"><span class="stock-legend__swatch stock-legend__swatch--warn"></span>Moderado ${escapeHtml(moderate)}–${escapeHtml(healthy - 1)}</span>
    <span class="stock-legend__item"><span class="stock-legend__swatch stock-legend__swatch--crit"></span>Crítico ≤${escapeHtml(critical)}</span>
  `;
}

function renderGrid(items) {
  if (!gridEl) return;
  if (!items.length) {
    gridEl.innerHTML =
      '<div class="col-12"><p class="text-muted mb-0">No hay datos de inventario.</p></div>';
    return;
  }

  gridEl.innerHTML = items
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
          <div class="inv-bar"><span style="width: ${barWidth(units)}%"></span></div>
          <span class="inv-card__count">${escapeHtml(units)} Unidades</span>
          <span class="inv-card__status ${level.status}">(${escapeHtml(level.label)})</span>
        </div>
      </div>`;
    })
    .join('');
}

function renderKpis(items) {
  const total = items.reduce((sum, item) => sum + Number(item.units ?? 0), 0);
  const critical = items.filter((item) => item.level === 'critical');
  if (kpiTotal) kpiTotal.textContent = String(total);
  if (kpiCritical) kpiCritical.textContent = String(critical.length);
  if (kpiCriticalDetail) {
    kpiCriticalDetail.textContent = critical.length
      ? critical.map((item) => item.blood_type).join(', ')
      : 'Ninguno bajo el umbral';
  }
}

function formatDateTime(value) {
  const date = new Date(String(value ?? '').replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleString('es-CR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function renderMovements(list) {
  if (!movementsBody) return;
  if (!list.length) {
    movementsBody.innerHTML =
      '<tr><td colspan="5" class="text-muted text-center py-4">Sin movimientos aún.</td></tr>';
    return;
  }

  movementsBody.innerHTML = list
    .map((row) => {
      const who = `${row.user_first_name ?? ''} ${row.user_last_name ?? ''}`.trim() || '—';
      const detail = row.detail ? `<div class="text-muted" style="font-size: 0.75rem">${escapeHtml(row.detail)}</div>` : '';
      return `
      <tr>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(formatDateTime(row.created_at))}</td>
        <td class="fw-semibold">${escapeHtml(MOVE_LABELS[row.type] ?? row.type)}</td>
        <td><span class="blood-badge blood-badge--teal">${escapeHtml(row.blood_type)}</span></td>
        <td class="fw-semibold">${escapeHtml(row.quantity)}</td>
        <td>
          <div style="font-size: 0.875rem">${escapeHtml(who)}</div>
          ${detail}
        </td>
      </tr>`;
    })
    .join('');
}

function fillBloodSelects() {
  for (const id of ['receipt-blood-type', 'adjust-blood-type']) {
    const el = document.getElementById(id);
    if (!el) continue;
    el.innerHTML = BLOOD_TYPES.map(
      (type) => `<option value="${type}">${type}</option>`
    ).join('');
  }
}

async function loadAll() {
  try {
    const [invPayload, movPayload] = await Promise.all([
      bankApi.getInventory(),
      bankApi.listInventoryMovements(40),
    ]);
    const items = Array.isArray(invPayload?.data?.items) ? invPayload.data.items : [];
    const thresholds = invPayload?.data?.thresholds;
    if (thresholds?.healthy_min) healthyMin = Number(thresholds.healthy_min);
    renderLegend(thresholds);
    renderGrid(items);
    renderKpis(items);
    const movements = Array.isArray(movPayload?.data?.movements) ? movPayload.data.movements : [];
    renderMovements(movements);
  } catch (error) {
    showStatus(error?.message || 'No se pudo cargar el inventario.', 'danger');
  }
}

receiptForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  hideModalStatus(receiptStatusEl);
  const bloodType = document.getElementById('receipt-blood-type')?.value;
  const quantity = Number(document.getElementById('receipt-quantity')?.value ?? 0);
  const detail = document.getElementById('receipt-detail')?.value?.trim() || undefined;
  const submitBtn = receiptForm.querySelector('[type="submit"]');
  if (submitBtn) submitBtn.disabled = true;
  try {
    await bankApi.createInventoryReceipt({ blood_type: bloodType, quantity, detail });
    bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal')).hide();
    receiptForm.reset();
    fillBloodSelects();
    showStatus('Recepción registrada.', 'success');
    await loadAll();
  } catch (error) {
    showModalStatus(receiptStatusEl, error?.message || 'No se pudo registrar la recepción.');
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
});

adjustForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  hideModalStatus(adjustStatusEl);
  const bloodType = document.getElementById('adjust-blood-type')?.value;
  const quantity = Number(document.getElementById('adjust-quantity')?.value ?? 0);
  const mode = document.getElementById('adjust-mode')?.value;
  const detail = document.getElementById('adjust-detail')?.value?.trim() || undefined;
  const submitBtn = adjustForm.querySelector('[type="submit"]');
  if (submitBtn) submitBtn.disabled = true;
  try {
    await bankApi.createInventoryAdjustment({ blood_type: bloodType, quantity, mode, detail });
    bootstrap.Modal.getOrCreateInstance(document.getElementById('adjustModal')).hide();
    adjustForm.reset();
    fillBloodSelects();
    showStatus('Ajuste registrado.', 'success');
    await loadAll();
  } catch (error) {
    showModalStatus(adjustStatusEl, error?.message || 'No se pudo registrar el ajuste.');
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
});

fillBloodSelects();
await loadAll();
