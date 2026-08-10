const { donorApi, centersApi } = await import(`/js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('appointments-status');
const tableBody = document.querySelector('#appointments-table tbody');
const donationsBody = document.querySelector('#donations-table tbody');
const nextDateEl = document.getElementById('next-appt-date');
const nextCenterEl = document.getElementById('next-appt-center');
const nextMetaEl = document.getElementById('next-appt-meta');
const nextCancelBtn = document.getElementById('next-appt-cancel');
const kpiUpcoming = document.getElementById('kpi-upcoming');
const kpiCompleted = document.getElementById('kpi-completed');
const kpiCancelled = document.getElementById('kpi-cancelled');
const apptCount = document.getElementById('appt-count');
const donationCount = document.getElementById('donation-count');
const scheduleForm = document.getElementById('schedule-form');
const scheduleCenter = document.getElementById('schedule-center');
const scheduleDatetime = document.getElementById('schedule-datetime');
const scheduleNotes = document.getElementById('schedule-notes');
const scheduleStatusEl = document.getElementById('schedule-status');
const scheduleModalEl = document.getElementById('scheduleModal');

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;
let appointments = [];
let nextAppointmentId = null;

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
  completed: 'badge-soft--slate',
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

function clearScheduleStatus() {
  if (!scheduleStatusEl) return;
  scheduleStatusEl.textContent = '';
  scheduleStatusEl.classList.add('d-none');
}

function showScheduleError(message) {
  if (!scheduleStatusEl) return;
  scheduleStatusEl.textContent = message;
  scheduleStatusEl.classList.remove('d-none', 'alert-success', 'alert-info');
  scheduleStatusEl.classList.add('alert-danger');
}

function parseDate(value) {
  const raw = String(value ?? '').replace(' ', 'T');
  const date = new Date(raw);
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

function formatHeadlineDate(value) {
  const date = parseDate(value);
  if (!date) return '—';
  return date.toLocaleDateString('es-CR', { day: 'numeric', month: 'short', year: 'numeric' });
}

function isOpenStatus(status) {
  return status === 'pending' || status === 'confirmed';
}

function getNextAppointment(list) {
  const now = Date.now();
  return list
    .filter((item) => isOpenStatus(item.status))
    .filter((item) => {
      const date = parseDate(item.scheduled_at);
      return date && date.getTime() >= now;
    })
    .sort((a, b) => parseDate(a.scheduled_at) - parseDate(b.scheduled_at))[0] ?? null;
}

function renderNext(list) {
  const next = getNextAppointment(list);
  nextAppointmentId = next?.id ?? null;

  if (!next) {
    if (nextDateEl) nextDateEl.textContent = 'Sin cita';
    if (nextCenterEl) nextCenterEl.textContent = 'Agenda una cita en un centro activo.';
    if (nextMetaEl) nextMetaEl.textContent = '—';
    if (nextCancelBtn) {
      nextCancelBtn.disabled = true;
      nextCancelBtn.dataset.id = '';
    }
    return;
  }

  if (nextDateEl) nextDateEl.textContent = formatHeadlineDate(next.scheduled_at);
  if (nextCenterEl) nextCenterEl.textContent = next.center_name || 'Centro';
  if (nextMetaEl) {
    nextMetaEl.textContent = `${formatTime(next.scheduled_at)} · ${STATUS_LABELS[next.status] ?? next.status}`;
  }
  if (nextCancelBtn) {
    nextCancelBtn.disabled = false;
    nextCancelBtn.dataset.id = String(next.id);
  }
}

function renderKpis(list) {
  const upcoming = list.filter((item) => isOpenStatus(item.status)).length;
  const completed = list.filter((item) => item.status === 'completed').length;
  const cancelled = list.filter((item) => item.status === 'cancelled' || item.status === 'no_show').length;
  if (kpiUpcoming) kpiUpcoming.textContent = String(upcoming);
  if (kpiCompleted) kpiCompleted.textContent = String(completed);
  if (kpiCancelled) kpiCancelled.textContent = String(cancelled);
  if (apptCount) apptCount.textContent = `${list.length} citas`;
}

function renderTable(list) {
  if (!tableBody) return;
  if (list.length === 0) {
    tableBody.innerHTML =
      '<tr><td colspan="5" class="text-muted text-center py-4">Aún no tienes citas.</td></tr>';
    return;
  }

  tableBody.innerHTML = list
    .map((item) => {
      const canCancel = isOpenStatus(item.status);
      return `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(item.center_name)}</div>
          <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(item.code || `ID ${item.id}`)}</div>
        </td>
        <td class="fw-semibold" style="font-size: 0.875rem">${escapeHtml(formatDate(item.scheduled_at))}</td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(formatTime(item.scheduled_at))}</td>
        <td>
          <span class="badge-soft ${STATUS_BADGE[item.status] || 'badge-soft--slate'}">
            <span class="dot"></span>${escapeHtml(STATUS_LABELS[item.status] ?? item.status)}
          </span>
        </td>
        <td class="text-end">
          ${
            canCancel
              ? `<button type="button" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold btn-cancel-appt" data-id="${item.id}">Cancelar</button>`
              : '<span class="text-muted" style="font-size: 0.8125rem">—</span>'
          }
        </td>
      </tr>`;
    })
    .join('');
}

function renderDonations(list) {
  if (!donationsBody) return;
  if (donationCount) donationCount.textContent = `${list.length} donaciones`;
  if (list.length === 0) {
    donationsBody.innerHTML =
      '<tr><td colspan="4" class="text-muted text-center py-4">Aún no hay donaciones registradas.</td></tr>';
    return;
  }

  donationsBody.innerHTML = list
    .map(
      (item) => `
      <tr>
        <td class="fw-semibold">${escapeHtml(item.center_name)}</td>
        <td><span class="blood-badge blood-badge--teal">${escapeHtml(item.blood_type)}</span></td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(formatDate(item.donated_at))}</td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(item.certificate_code || '—')}</td>
      </tr>`
    )
    .join('');
}

async function cancelAppointment(id) {
  if (!id) return;
  if (!window.confirm('¿Cancelar esta cita?')) return;
  try {
    await donorApi.cancelAppointment(id);
    showStatus('Cita cancelada.', 'success');
    await loadAll();
  } catch (error) {
    showStatus(error?.message || 'No se pudo cancelar la cita.', 'danger');
  }
}

async function loadCenters() {
  if (!scheduleCenter) return;
  try {
    const payload = await centersApi.list();
    const centers = Array.isArray(payload?.data) ? payload.data : [];
    scheduleCenter.innerHTML =
      '<option value="">Seleccionar centro…</option>' +
      centers
        .map(
          (center) =>
            `<option value="${center.id}">${escapeHtml(center.name)}</option>`
        )
        .join('');
  } catch {
    scheduleCenter.innerHTML = '<option value="">No se pudieron cargar centros</option>';
  }
}

async function loadAll() {
  try {
    const [apptPayload, donationPayload] = await Promise.all([
      donorApi.listAppointments(),
      donorApi.listDonations(),
    ]);
    appointments = Array.isArray(apptPayload?.data) ? apptPayload.data : [];
    const donations = Array.isArray(donationPayload?.data) ? donationPayload.data : [];
    renderNext(appointments);
    renderKpis(appointments);
    renderTable(appointments);
    renderDonations(donations);
  } catch (error) {
    showStatus(error?.message || 'No se pudieron cargar las citas.', 'danger');
  }
}

tableBody?.addEventListener('click', (event) => {
  const btn = event.target.closest('.btn-cancel-appt');
  if (!btn) return;
  cancelAppointment(Number(btn.dataset.id));
});

nextCancelBtn?.addEventListener('click', () => {
  cancelAppointment(Number(nextCancelBtn.dataset.id || nextAppointmentId));
});

scheduleModalEl?.addEventListener('show.bs.modal', clearScheduleStatus);

scheduleForm?.addEventListener('submit', async (event) => {
  event.preventDefault();
  clearScheduleStatus();
  const centerId = Number(scheduleCenter?.value || 0);
  const scheduledAt = scheduleDatetime?.value || '';
  const notes = scheduleNotes?.value?.trim() || '';

  try {
    await donorApi.createAppointment({
      center_id: centerId,
      scheduled_at: scheduledAt,
      notes: notes || null,
    });
    clearScheduleStatus();
    scheduleForm.reset();
    const modal = scheduleModalEl ? bootstrap.Modal.getInstance(scheduleModalEl) : null;
    modal?.hide();
    showStatus('Cita agendada.', 'success');
    await loadAll();
  } catch (error) {
    showScheduleError(error?.message || 'No se pudo agendar la cita.');
  }
});

if (scheduleDatetime) {
  const min = new Date();
  min.setMinutes(min.getMinutes() - min.getTimezoneOffset());
  scheduleDatetime.min = min.toISOString().slice(0, 16);
}

await loadCenters();
await loadAll();
