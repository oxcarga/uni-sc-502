const { bankApi, centersApi, getCachedSession } = await import(`/js/api.js?t=${Date.now()}`);

const form = document.getElementById('bank-settings-form');
const statusEl = document.getElementById('settings-status');
const saveBtn = document.getElementById('settings-save-btn');
const accountEmailEl = document.getElementById('accountEmail');
const accountNameEl = document.getElementById('accountName');
const centerNameEl = document.getElementById('centerName');
const contactPhoneEl = document.getElementById('contactPhone');
const contactEmailEl = document.getElementById('contactEmail');
const addressEl = document.getElementById('address');
const openTimeEl = document.getElementById('openTime');
const closeTimeEl = document.getElementById('closeTime');
const openDaysEl = document.getElementById('openDays');

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;
let savedSnapshot = '';
let centerId = 0;

function toTimeInput(value) {
  if (!value) return '';
  return String(value).slice(0, 5);
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

function currentFormSnapshot() {
  return JSON.stringify({
    name: centerNameEl?.value?.trim() ?? '',
    contact_phone: contactPhoneEl?.value?.trim() ?? '',
    contact_email: contactEmailEl?.value?.trim() ?? '',
    address: addressEl?.value?.trim() ?? '',
    open_time: openTimeEl?.value ?? '',
    close_time: closeTimeEl?.value ?? '',
    open_days: openDaysEl?.value?.trim() ?? '',
  });
}

function updateSaveButtonState() {
  if (!saveBtn) return;
  saveBtn.disabled = !centerId || currentFormSnapshot() === savedSnapshot;
}

function fillForm(center) {
  centerId = Number(center?.id ?? 0);
  if (centerNameEl) centerNameEl.value = center?.name ?? '';
  if (contactPhoneEl) contactPhoneEl.value = center?.contact_phone ?? '';
  if (contactEmailEl) contactEmailEl.value = center?.contact_email ?? '';
  if (addressEl) addressEl.value = center?.address ?? '';
  if (openTimeEl) openTimeEl.value = toTimeInput(center?.open_time);
  if (closeTimeEl) closeTimeEl.value = toTimeInput(center?.close_time);
  if (openDaysEl) openDaysEl.value = center?.open_days ?? '';
  savedSnapshot = currentFormSnapshot();
  updateSaveButtonState();
}

const user = getCachedSession();
if (user) {
  if (accountEmailEl) accountEmailEl.value = user.email ?? '';
  if (accountNameEl) {
    accountNameEl.value = `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
  }
}

form?.addEventListener('input', updateSaveButtonState);
form?.addEventListener('change', updateSaveButtonState);

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!centerId || currentFormSnapshot() === savedSnapshot) return;

  const body = {
    name: centerNameEl?.value?.trim() ?? '',
    contact_phone: contactPhoneEl?.value?.trim() || null,
    contact_email: contactEmailEl?.value?.trim() || null,
    address: addressEl?.value?.trim() ?? '',
    open_time: openTimeEl?.value || null,
    close_time: closeTimeEl?.value || null,
    open_days: openDaysEl?.value?.trim() || null,
  };

  if (saveBtn) saveBtn.disabled = true;

  try {
    const payload = await centersApi.update(centerId, body);
    fillForm(payload?.data);
    showStatus(payload?.message || 'Datos del centro guardados.', 'success');
  } catch (error) {
    showStatus(error?.message || 'No se pudieron guardar los datos del centro.', 'danger');
    updateSaveButtonState();
  }
});

try {
  const payload = await bankApi.getCenter();
  fillForm(payload?.data);
} catch (error) {
  showStatus(error?.message || 'No se pudieron cargar los datos del centro.', 'danger');
  updateSaveButtonState();
}
