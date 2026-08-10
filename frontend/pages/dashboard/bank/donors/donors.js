const { bankApi } = await import(`/js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('donors-status');
const form = document.getElementById('compat-form');
const bloodTypeEl = document.getElementById('compat-blood-type');
const eligibleEl = document.getElementById('compat-eligible-only');
const tableBody = document.querySelector('#compatible-donors-table tbody');
const countEl = document.getElementById('donors-count');
const alertHintEl = document.getElementById('compat-alert-hint');

const BLOOD_TYPES = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];

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

function formatLastDonation(value) {
  if (!value) return 'Sin registro';
  const date = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleDateString('es-CR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function fillBloodSelect(selected = 'O-') {
  if (!bloodTypeEl) return;
  bloodTypeEl.innerHTML = BLOOD_TYPES.map(
    (type) => `<option value="${type}" ${type === selected ? 'selected' : ''}>${type}</option>`
  ).join('');
}

function renderDonors(list) {
  if (!tableBody) return;
  if (countEl) countEl.textContent = `${list.length} donantes`;
  if (!list.length) {
    tableBody.innerHTML =
      '<tr><td colspan="5" class="text-muted text-center py-4">No hay donantes compatibles con ese filtro.</td></tr>';
    return;
  }

  tableBody.innerHTML = list
    .map((item) => {
      const name = `${item.first_name ?? ''} ${item.last_name ?? ''}`.trim() || 'Donante';
      const phone = item.phone || '—';
      const mailto = item.email ? `mailto:${item.email}` : '#';
      return `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(name)}</div>
          <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(item.email || '')}</div>
        </td>
        <td><span class="blood-badge blood-badge--teal">${escapeHtml(item.blood_type)}</span></td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(formatLastDonation(item.last_donation_at))}</td>
        <td>
          <span class="badge-soft ${item.eligible ? 'badge-soft--green' : 'badge-soft--amber'}">
            <span class="dot"></span>${item.eligible ? 'Elegible' : 'En espera'}
          </span>
        </td>
        <td class="text-end">
          <a class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold" href="${escapeHtml(mailto)}"
            title="${escapeHtml(phone)}">Contactar</a>
        </td>
      </tr>`;
    })
    .join('');
}

async function loadDonors() {
  const bloodType = bloodTypeEl?.value || 'O-';
  const eligibleOnly = Boolean(eligibleEl?.checked);
  try {
    const payload = await bankApi.listCompatibleDonors({
      blood_type: bloodType,
      eligible: eligibleOnly,
    });
    const list = Array.isArray(payload?.data?.donors) ? payload.data.donors : [];
    renderDonors(list);
  } catch (error) {
    showStatus(error?.message || 'No se pudieron cargar los donantes.', 'danger');
  }
}

async function loadAlertHint() {
  if (!alertHintEl) return;
  try {
    const payload = await bankApi.listAlerts({ status: 'active' });
    const alerts = Array.isArray(payload?.data?.alerts) ? payload.data.alerts : [];
    if (!alerts.length) {
      alertHintEl.textContent = 'Sin alertas activas. Elige un tipo o usa el filtro.';
      return;
    }
    const types = [...new Set(alerts.map((a) => a.blood_type))];
    alertHintEl.innerHTML = `Alertas activas: <strong>${escapeHtml(types.join(', '))}</strong>. Puedes filtrar por esos tipos.`;
    if (bloodTypeEl && types.includes(bloodTypeEl.value) === false && types[0]) {
      // keep user choice; only suggest via hint
    }
  } catch {
    alertHintEl.textContent = '';
  }
}

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  await loadDonors();
});

const params = new URLSearchParams(window.location.search);
const preset = params.get('blood_type');
fillBloodSelect(BLOOD_TYPES.includes(preset) ? preset : 'O-');
await loadAlertHint();
await loadDonors();
