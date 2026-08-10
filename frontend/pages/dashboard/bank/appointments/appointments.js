const { bankApi } = await import(`/js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('appointments-status');
const tableBody = document.querySelector('#bank-appointments-table tbody');
const kpiPending = document.getElementById('kpi-pending');
const kpiConfirmed = document.getElementById('kpi-confirmed');
const kpiCompleted = document.getElementById('kpi-completed');
const kpiNoShow = document.getElementById('kpi-noshow');
const apptCount = document.getElementById('appt-count');

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;

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

function parseDate(value) {
  const date = new Date(String(value ?? '').replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? null : date;
}

function formatDate(value) {
  const date = parseDate(value);
  if (!date) return '—';
  return date.toLocaleDateString('es-CR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatTime(value) {
  const date = parseDate(value);
  if (!date) return '—';
  return date.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' });
}

function donorName(item) {
  return `${item.donor_first_name ?? ''} ${item.donor_last_name ?? ''}`.trim() || item.donor_email || 'Donante';
}

function renderKpis(list) {
  if (kpiPending) kpiPending.textContent = String(list.filter((i) => i.status === 'pending').length);
  if (kpiConfirmed) kpiConfirmed.textContent = String(list.filter((i) => i.status === 'confirmed').length);
  if (kpiCompleted) kpiCompleted.textContent = String(list.filter((i) => i.status === 'completed').length);
  if (kpiNoShow) kpiNoShow.textContent = String(list.filter((i) => i.status === 'no_show').length);
  if (apptCount) apptCount.textContent = `${list.length} citas`;
}

function renderTable(list) {
  if (!tableBody) return;
  if (list.length === 0) {
    tableBody.innerHTML =
      '<tr><td colspan="6" class="text-muted text-center py-4">No hay citas en este centro.</td></tr>';
    return;
  }

  tableBody.innerHTML = list
    .map((item) => {
      const canComplete = item.status === 'pending' || item.status === 'confirmed';
      const blood = item.donor_blood_type || '—';
      return `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(donorName(item))}</div>
          <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(item.code || `ID ${item.id}`)}</div>
        </td>
        <td><span class="blood-badge blood-badge--teal">${escapeHtml(blood)}</span></td>
        <td class="fw-semibold" style="font-size: 0.875rem">${escapeHtml(formatDate(item.scheduled_at))}</td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(formatTime(item.scheduled_at))}</td>
        <td>
          <span class="badge-soft ${STATUS_BADGE[item.status] || 'badge-soft--slate'}">
            <span class="dot"></span>${escapeHtml(STATUS_LABELS[item.status] ?? item.status)}
          </span>
        </td>
        <td class="text-end">
          ${
            canComplete
              ? `<button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold btn-complete-appt" data-id="${item.id}">Completar</button>`
              : '<span class="text-muted" style="font-size: 0.8125rem">—</span>'
          }
        </td>
      </tr>`;
    })
    .join('');
}

async function loadAppointments() {
  try {
    const payload = await bankApi.listAppointments();
    const list = Array.isArray(payload?.data?.appointments) ? payload.data.appointments : [];
    renderKpis(list);
    renderTable(list);
  } catch (error) {
    showStatus(error?.message || 'No se pudieron cargar las citas.', 'danger');
  }
}

tableBody?.addEventListener('click', async (event) => {
  const btn = event.target.closest('.btn-complete-appt');
  if (!btn) return;
  const id = Number(btn.dataset.id);
  if (!id) return;
  if (!window.confirm('¿Marcar esta cita como completada y registrar la donación?')) return;

  btn.disabled = true;
  try {
    await bankApi.completeAppointment(id);
    showStatus('Donación registrada. La cita quedó completada.', 'success');
    await loadAppointments();
  } catch (error) {
    showStatus(error?.message || 'No se pudo completar la cita.', 'danger');
    btn.disabled = false;
  }
});

await loadAppointments();
