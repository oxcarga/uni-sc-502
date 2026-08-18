const { centersApi } = await import(`/js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('banks-status');
const table = document.getElementById('admin-banks-table');
const tableBody = table?.querySelector('tbody');
const kpiTotal = document.getElementById('kpi-total');
const kpiActive = document.getElementById('kpi-active');
const kpiInactive = document.getElementById('kpi-inactive');
const banksCount = document.getElementById('banks-count');
const filterSearch = document.getElementById('filterSearch');
const filterStatus = document.getElementById('filterStatus');
const filterRegion = document.getElementById('filterRegion');
const filterClear = document.getElementById('filterClear');
const btnNewBank = document.getElementById('btn-new-bank');
const modalEl = document.getElementById('bankModal');
const form = document.getElementById('bank-form');
const formStatusEl = document.getElementById('bank-form-status');
const formSubmitBtn = document.getElementById('bank-form-submit');
const modalTitle = document.getElementById('bankModalLabel');
const fieldId = document.getElementById('bank-id');
const fieldName = document.getElementById('bank-name');
const fieldCode = document.getElementById('bank-code');
const fieldAddress = document.getElementById('bank-address');
const fieldProvince = document.getElementById('bank-province');
const fieldCanton = document.getElementById('bank-canton');
const fieldRegion = document.getElementById('bank-region');
const fieldContactName = document.getElementById('bank-contact-name');
const fieldContactPhone = document.getElementById('bank-contact-phone');
const fieldContactEmail = document.getElementById('bank-contact-email');
const fieldOpenTime = document.getElementById('bank-open-time');
const fieldCloseTime = document.getElementById('bank-close-time');
const fieldOpenDays = document.getElementById('bank-open-days');
const fieldWalkIns = document.getElementById('bank-walk-ins');
const fieldActive = document.getElementById('bank-active');

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;
let centers = [];
let editSnapshot = '';

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

function showFormStatus(message) {
  if (!formStatusEl) return;
  if (!message) {
    formStatusEl.classList.add('d-none');
    formStatusEl.textContent = '';
    return;
  }
  formStatusEl.textContent = message;
  formStatusEl.classList.remove('d-none');
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function toTimeInput(value) {
  if (!value) return '';
  return String(value).slice(0, 5);
}

function setRowActive(row, active) {
  row.dataset.active = active ? '1' : '0';
  const badge = row.querySelector('.bank-status');
  if (badge) {
    badge.className = `badge-soft ${active ? 'badge-soft--green' : 'badge-soft--slate'} bank-status`;
    badge.innerHTML = `<span class="dot"></span>${active ? 'Activo' : 'Inactivo'}`;
  }
  const toggle = row.querySelector('.bank-active-toggle');
  if (toggle) toggle.checked = active;
}

function refreshKpis() {
  const rows = [...(table?.querySelectorAll('tbody tr[data-bank-id]') ?? [])];
  const visible = rows.filter((row) => row.style.display !== 'none');
  const active = visible.filter((row) => row.dataset.active === '1').length;
  const inactive = visible.length - active;

  if (kpiTotal) kpiTotal.textContent = String(visible.length);
  if (kpiActive) kpiActive.textContent = String(active);
  if (kpiInactive) kpiInactive.textContent = String(inactive);
  if (banksCount) banksCount.textContent = `${visible.length} centros`;
}

function applyFilters() {
  const q = (filterSearch?.value ?? '').trim().toLowerCase();
  const status = filterStatus?.value ?? '';
  const region = filterRegion?.value ?? '';

  table?.querySelectorAll('tbody tr[data-bank-id]').forEach((row) => {
    const name = row.dataset.name ?? '';
    const rowRegion = row.dataset.region ?? '';
    const active = row.dataset.active === '1';

    const matchesSearch = !q || name.includes(q) || rowRegion.toLowerCase().includes(q);
    const matchesStatus =
      !status || (status === 'active' && active) || (status === 'inactive' && !active);
    const matchesRegion = !region || rowRegion === region;

    row.style.display = matchesSearch && matchesStatus && matchesRegion ? '' : 'none';
  });

  refreshKpis();
}

function populateRegionFilter(list) {
  if (!filterRegion) return;
  const regions = [...new Set(list.map((c) => c.region || c.province).filter(Boolean))].sort();
  const current = filterRegion.value;
  filterRegion.innerHTML =
    '<option value="">Todas</option>' +
    regions.map((region) => `<option value="${escapeHtml(region)}">${escapeHtml(region)}</option>`).join('');
  if (regions.includes(current)) filterRegion.value = current;
}

function renderCenters(list) {
  if (!tableBody) return;

  if (list.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" class="text-muted text-center py-4">No hay centros en la base de datos.</td>
      </tr>`;
    refreshKpis();
    return;
  }

  tableBody.innerHTML = list
    .map((center) => {
      const active = Boolean(center.active);
      const region = center.region || center.province || '';
      return `
      <tr data-bank-id="${center.id}" data-active="${active ? '1' : '0'}"
          data-region="${escapeHtml(region)}" data-name="${escapeHtml(String(center.name).toLowerCase())}">
        <td>
          <div class="fw-semibold">${escapeHtml(center.name)}</div>
          <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(center.code || `ID ${center.id}`)} · ${escapeHtml(center.address || '')}</div>
        </td>
        <td class="fw-semibold" style="font-size: 0.875rem">${escapeHtml(region || '—')}</td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(center.contact_phone || '—')}</td>
        <td>
          <span class="badge-soft ${active ? 'badge-soft--green' : 'badge-soft--slate'} bank-status">
            <span class="dot"></span>${active ? 'Activo' : 'Inactivo'}
          </span>
        </td>
        <td class="text-end">
          <div class="d-flex justify-content-end align-items-center gap-2">
            <div class="form-check form-switch m-0">
              <input class="form-check-input bank-active-toggle" type="checkbox" role="switch"
                aria-label="Activar ${escapeHtml(center.name)}" ${active ? 'checked' : ''} />
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold btn-edit-bank">
              Editar
            </button>
          </div>
        </td>
      </tr>`;
    })
    .join('');

  populateRegionFilter(list);
  applyFilters();
}

function upsertCenter(center) {
  const index = centers.findIndex((item) => item.id === center.id);
  if (index >= 0) {
    centers[index] = center;
  } else {
    centers.push(center);
  }
  centers.sort((a, b) => String(a.name).localeCompare(String(b.name), 'es'));
  renderCenters(centers);
}

function formSnapshot() {
  return JSON.stringify({
    name: fieldName?.value?.trim() ?? '',
    code: fieldCode?.value?.trim() ?? '',
    address: fieldAddress?.value?.trim() ?? '',
    province: fieldProvince?.value?.trim() ?? '',
    canton: fieldCanton?.value?.trim() ?? '',
    region: fieldRegion?.value?.trim() ?? '',
    contact_name: fieldContactName?.value?.trim() ?? '',
    contact_phone: fieldContactPhone?.value?.trim() ?? '',
    contact_email: fieldContactEmail?.value?.trim() ?? '',
    open_time: fieldOpenTime?.value ?? '',
    close_time: fieldCloseTime?.value ?? '',
    open_days: fieldOpenDays?.value?.trim() ?? '',
    accept_walk_ins: Boolean(fieldWalkIns?.checked),
    active: Boolean(fieldActive?.checked),
  });
}

function syncModalDirty() {
  if (!formSubmitBtn) return;
  const isCreate = !(fieldId?.value);
  formSubmitBtn.disabled = isCreate ? false : formSnapshot() === editSnapshot;
}

function fillForm(center) {
  if (fieldId) fieldId.value = center?.id ? String(center.id) : '';
  if (fieldName) fieldName.value = center?.name ?? '';
  if (fieldCode) fieldCode.value = center?.code ?? '';
  if (fieldAddress) fieldAddress.value = center?.address ?? '';
  if (fieldProvince) fieldProvince.value = center?.province ?? '';
  if (fieldCanton) fieldCanton.value = center?.canton ?? '';
  if (fieldRegion) fieldRegion.value = center?.region ?? '';
  if (fieldContactName) fieldContactName.value = center?.contact_name ?? '';
  if (fieldContactPhone) fieldContactPhone.value = center?.contact_phone ?? '';
  if (fieldContactEmail) fieldContactEmail.value = center?.contact_email ?? '';
  if (fieldOpenTime) fieldOpenTime.value = toTimeInput(center?.open_time);
  if (fieldCloseTime) fieldCloseTime.value = toTimeInput(center?.close_time);
  if (fieldOpenDays) fieldOpenDays.value = center?.open_days ?? '';
  if (fieldWalkIns) fieldWalkIns.checked = center ? Boolean(center.accept_walk_ins) : true;
  if (fieldActive) fieldActive.checked = center ? Boolean(center.active) : true;
  editSnapshot = formSnapshot();
  syncModalDirty();
}

function collectFormPayload() {
  return {
    name: fieldName?.value?.trim() ?? '',
    code: fieldCode?.value?.trim() || null,
    address: fieldAddress?.value?.trim() ?? '',
    province: fieldProvince?.value?.trim() || null,
    canton: fieldCanton?.value?.trim() || null,
    region: fieldRegion?.value?.trim() || null,
    contact_name: fieldContactName?.value?.trim() || null,
    contact_phone: fieldContactPhone?.value?.trim() || null,
    contact_email: fieldContactEmail?.value?.trim() || null,
    open_time: fieldOpenTime?.value || null,
    close_time: fieldCloseTime?.value || null,
    open_days: fieldOpenDays?.value?.trim() || null,
    accept_walk_ins: Boolean(fieldWalkIns?.checked),
    active: Boolean(fieldActive?.checked),
  };
}

function openModal(center = null) {
  if (!modalEl || !window.bootstrap) return;
  showFormStatus('');
  fillForm(center);
  if (modalTitle) {
    modalTitle.textContent = center ? 'Editar banco' : 'Nuevo banco';
  }
  if (formSubmitBtn) {
    formSubmitBtn.textContent = center ? 'Guardar cambios' : 'Crear banco';
  }
  window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function closeModal() {
  if (!modalEl || !window.bootstrap) return;
  window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
}

table?.addEventListener('change', async (event) => {
  const toggle = event.target.closest('.bank-active-toggle');
  if (!toggle) return;

  const row = toggle.closest('tr');
  if (!row) return;

  const id = Number(row.dataset.bankId);
  const name = row.querySelector('.fw-semibold')?.textContent?.trim() ?? 'Banco';
  const newActive = toggle.checked;
  const previousActive = row.dataset.active === '1';

  toggle.disabled = true;
  try {
    const payload = await centersApi.update(id, { active: newActive });
    const updated = payload?.data;
    if (updated) upsertCenter(updated);
    else {
      setRowActive(row, newActive);
      const local = centers.find((item) => item.id === id);
      if (local) local.active = newActive;
      refreshKpis();
    }
    showStatus(
      `${name} quedó ${newActive ? 'activo' : 'inactivo'}.`,
      'success'
    );
  } catch (error) {
    setRowActive(row, previousActive);
    refreshKpis();
    showStatus(error?.message || `No se pudo actualizar ${name}.`, 'danger');
  } finally {
    toggle.disabled = false;
  }
});

table?.addEventListener('click', (event) => {
  const editBtn = event.target.closest('.btn-edit-bank');
  if (!editBtn) return;
  const row = editBtn.closest('tr');
  const id = Number(row?.dataset.bankId);
  const center = centers.find((item) => item.id === id);
  if (center) openModal(center);
});

btnNewBank?.addEventListener('click', () => openModal(null));

form?.addEventListener('input', syncModalDirty);
form?.addEventListener('change', syncModalDirty);

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const id = fieldId?.value ? Number(fieldId.value) : 0;
  const body = collectFormPayload();
  if (formSubmitBtn) formSubmitBtn.disabled = true;
  showFormStatus('');

  try {
    const payload = id
      ? await centersApi.update(id, body)
      : await centersApi.create(body);
    const saved = payload?.data;
    if (saved) upsertCenter(saved);
    closeModal();
    showStatus(payload?.message || (id ? 'Centro actualizado.' : 'Centro creado.'), 'success');
  } catch (error) {
    showFormStatus(error?.message || 'No se pudo guardar el centro.');
    syncModalDirty();
  }
});

filterSearch?.addEventListener('input', applyFilters);
filterStatus?.addEventListener('change', applyFilters);
filterRegion?.addEventListener('change', applyFilters);
filterClear?.addEventListener('click', () => {
  if (filterSearch) filterSearch.value = '';
  if (filterStatus) filterStatus.value = '';
  if (filterRegion) filterRegion.value = '';
  applyFilters();
});

try {
  const payload = await centersApi.list({ all: true });
  centers = Array.isArray(payload?.data) ? payload.data : [];
  renderCenters(centers);
} catch (error) {
  showStatus(error?.message || 'No se pudieron cargar los centros.', 'danger');
}
