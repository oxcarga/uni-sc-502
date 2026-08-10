const { adminApi } = await import(`/js/api.js?t=${Date.now()}`);

const form = document.getElementById('policies-form');
const statusEl = document.getElementById('policies-status');
const healthyEl = document.getElementById('policy-healthy-min');
const moderateEl = document.getElementById('policy-moderate-min');
const criticalEl = document.getElementById('policy-critical-max');
const intervalEl = document.getElementById('policy-interval-days');
const saveBtn = document.getElementById('policies-save');

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;
let savedSnapshot = '';

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

function snapshot() {
  return JSON.stringify({
    inventory_healthy_min: Number(healthyEl?.value || 0),
    inventory_moderate_min: Number(moderateEl?.value || 0),
    inventory_critical_max: Number(criticalEl?.value || 0),
    donor_interval_days: Number(intervalEl?.value || 0),
  });
}

function syncDirty() {
  if (saveBtn) saveBtn.disabled = snapshot() === savedSnapshot;
}

function fillFromPolicies(policies) {
  const map = Object.fromEntries(
    (policies || []).map((p) => [p.key_name, p.value_text])
  );
  if (healthyEl) healthyEl.value = map.inventory_healthy_min ?? '101';
  if (moderateEl) moderateEl.value = map.inventory_moderate_min ?? '50';
  if (criticalEl) criticalEl.value = map.inventory_critical_max ?? '49';
  if (intervalEl) intervalEl.value = map.donor_interval_days ?? '56';
  savedSnapshot = snapshot();
  syncDirty();
}

form?.addEventListener('input', syncDirty);

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const payload = {
    inventory_healthy_min: Number(healthyEl.value),
    inventory_moderate_min: Number(moderateEl.value),
    inventory_critical_max: Number(criticalEl.value),
    donor_interval_days: Number(intervalEl.value),
  };
  if (saveBtn) saveBtn.disabled = true;
  try {
    const result = await adminApi.updatePolicies(payload);
    fillFromPolicies(result?.data?.policies);
    showStatus(result?.message || 'Políticas guardadas.', 'success');
  } catch (error) {
    showStatus(error?.message || 'No se pudieron guardar las políticas.', 'danger');
    syncDirty();
  }
});

try {
  const payload = await adminApi.getPolicies();
  fillFromPolicies(payload?.data?.policies);
} catch (error) {
  showStatus(error?.message || 'No se pudieron cargar las políticas.', 'danger');
}
