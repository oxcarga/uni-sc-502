const { adminApi } = await import(`/js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('donors-status');
const table = document.getElementById('admin-donors-table');
const tbody = table?.querySelector('tbody');
const kpiTotal = document.getElementById('kpi-total');
const kpiActive = document.getElementById('kpi-active');
const kpiInactive = document.getElementById('kpi-inactive');
const kpiEligible = document.getElementById('kpi-eligible');
const donorsCount = document.getElementById('donors-count');
const filterSearch = document.getElementById('filterSearch');
const filterBlood = document.getElementById('filterBlood');
const filterAccount = document.getElementById('filterAccount');
const filterEligible = document.getElementById('filterEligible');
const filterClear = document.getElementById('filterClear');
const detailModalEl = document.getElementById('donorDetailModal');
const btnNewDonor = document.getElementById('btn-new-donor');
const createModalEl = document.getElementById('donorModal');
const form = document.getElementById('donor-form');
const formStatusEl = document.getElementById('donor-form-status');
const formSubmitBtn = document.getElementById('donor-form-submit');
const fieldFirstName = document.getElementById('donor-first-name');
const fieldLastName = document.getElementById('donor-last-name');
const fieldEmail = document.getElementById('donor-email');
const fieldPassword = document.getElementById('donor-password');
const fieldPhone = document.getElementById('donor-phone');
const fieldBlood = document.getElementById('donor-blood');

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;
let donors = [];

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

function formatDate(value) {
  if (!value) return '—';

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return date.toLocaleDateString('es-CR');
}

function isEligible(donor) {
  return donor.eligible === true || donor.eligible === 1 || donor.eligible === '1';
}

function renderDonors(list) {
  if (!tbody) return;

  if (list.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-muted text-center py-4">No hay donantes registrados.</td>
      </tr>`;
    refreshKpis();
    return;
  }

  tbody.innerHTML = '';

  list.forEach((donor) => {
    const fullName = `${donor.first_name ?? ''} ${donor.last_name ?? ''}`.trim();
    const bloodType = donor.blood_type ?? '—';
    const phone = donor.phone ?? '—';
    const eligible = isEligible(donor);
    const active = Boolean(donor.active);
    const lastDonation = formatDate(donor.last_donation_at);
    const email = donor.email ?? '';

    const row = document.createElement('tr');

    row.dataset.id = String(donor.id);
    row.dataset.firstName = donor.first_name ?? '';
    row.dataset.lastName = donor.last_name ?? '';
    row.dataset.active = active ? '1' : '0';
    row.dataset.eligible = eligible ? '1' : '0';
    row.dataset.blood = bloodType;
    row.dataset.name = fullName.toLowerCase();
    row.dataset.email = email.toLowerCase();

    row.innerHTML = `
      <td>
        <div class="fw-semibold">${escapeHtml(fullName || '—')}</div>
        <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(email || '—')}</div>
      </td>

      <td>
        <span class="blood-badge">${escapeHtml(bloodType)}</span>
      </td>

      <td>
        <div style="font-size: 0.875rem">${escapeHtml(phone)}</div>
        <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(email || '—')}</div>
      </td>

      <td class="fw-semibold" style="font-size: 0.875rem">
        ${escapeHtml(lastDonation)}
      </td>

      <td>
        <span class="badge-soft ${eligible ? 'badge-soft--green' : 'badge-soft--slate'}">
          <span class="dot"></span>
          ${eligible ? 'Elegible' : 'No elegible'}
        </span>
      </td>

      <td>
        <span class="badge-soft ${active ? 'badge-soft--teal' : 'badge-soft--slate'} donor-account-status">
          <span class="dot"></span>
          ${active ? 'Activa' : 'Inactiva'}
        </span>
      </td>

      <td class="text-end">
        <div class="d-flex justify-content-end align-items-center gap-2">
          <div class="form-check form-switch m-0">
            <input
              class="form-check-input donor-active-toggle"
              type="checkbox"
              role="switch"
              aria-label="Activar cuenta ${escapeHtml(fullName)}"
              ${active ? 'checked' : ''}
            />
          </div>

          <button
            type="button"
            class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold btn-view-donor">
            Ver
          </button>
        </div>
      </td>
    `;

    tbody.appendChild(row);
  });

  applyFilters();
}

function upsertDonor(donor) {
  const index = donors.findIndex((item) => Number(item.id) === Number(donor.id));
  if (index >= 0) {
    donors[index] = donor;
  } else {
    donors.push(donor);
  }
  donors.sort((a, b) => Number(a.id) - Number(b.id));
  renderDonors(donors);
}

async function loadDonors() {
  try {
    showStatus('Cargando donantes...', 'info');

    const result = await adminApi.listUsers({ role: 'donor' });
    donors = Array.isArray(result.data?.users) ? result.data.users : [];

    renderDonors(donors);

    showStatus(
      `Se cargaron ${donors.length} donantes registrados.`,
      'success'
    );
  } catch (error) {
    console.error('Error al cargar donantes:', error);
    showStatus('No se pudieron cargar los donantes.', 'danger');
  }
}

function setRowActive(row, active) {
  row.dataset.active = active ? '1' : '0';
  const badge = row.querySelector('.donor-account-status');
  if (badge) {
    badge.className = `badge-soft ${active ? 'badge-soft--teal' : 'badge-soft--slate'} donor-account-status`;
    badge.innerHTML = `<span class="dot"></span>${active ? 'Activa' : 'Inactiva'}`;
  }
  const toggle = row.querySelector('.donor-active-toggle');
  if (toggle) toggle.checked = active;
}

function refreshKpis() {
  const rows = [...(table?.querySelectorAll('tbody tr[data-id]') ?? [])];
  const visible = rows.filter((row) => row.style.display !== 'none');
  const active = visible.filter((row) => row.dataset.active === '1').length;
  const eligible = visible.filter((row) => row.dataset.eligible === '1').length;

  if (kpiTotal) kpiTotal.textContent = String(visible.length);
  if (kpiActive) kpiActive.textContent = String(active);
  if (kpiInactive) kpiInactive.textContent = String(visible.length - active);
  if (kpiEligible) kpiEligible.textContent = String(eligible);
  if (donorsCount) donorsCount.textContent = `${visible.length} registros`;
}

function applyFilters() {
  const q = (filterSearch?.value ?? '').trim().toLowerCase();
  const blood = filterBlood?.value ?? '';
  const account = filterAccount?.value ?? '';
  const eligible = filterEligible?.value ?? '';

  table?.querySelectorAll('tbody tr[data-id]').forEach((row) => {
    const name = row.dataset.name ?? '';
    const email = row.dataset.email ?? '';
    const rowBlood = row.dataset.blood ?? '';
    const active = row.dataset.active === '1';
    const isEligibleRow = row.dataset.eligible === '1';

    const matchesSearch = !q || name.includes(q) || email.includes(q);
    const matchesBlood = !blood || rowBlood === blood;
    const matchesAccount =
      !account || (account === 'active' && active) || (account === 'inactive' && !active);
    const matchesEligible =
      !eligible || (eligible === 'yes' && isEligibleRow) || (eligible === 'no' && !isEligibleRow);

    row.style.display =
      matchesSearch && matchesBlood && matchesAccount && matchesEligible ? '' : 'none';
  });

  refreshKpis();
}

function openDonorDetail(row) {
  if (!detailModalEl || !window.bootstrap) return;

  const name = row.querySelector('.fw-semibold')?.textContent?.trim() ?? '—';
  const email = row.dataset.email ?? '—';
  const phone = row.children[2]?.querySelector('div')?.textContent?.trim() ?? '—';
  const blood = row.dataset.blood ?? '—';
  const last = row.children[3]?.textContent?.trim() ?? '—';
  const eligible = row.dataset.eligible === '1' ? 'Elegible' : 'No elegible';
  const account = row.dataset.active === '1' ? 'Activa' : 'Inactiva';

  document.getElementById('detail-name').textContent = name;
  document.getElementById('detail-email').textContent = email;
  document.getElementById('detail-phone').textContent = phone;
  document.getElementById('detail-blood').textContent = blood;
  document.getElementById('detail-last').textContent = last;
  document.getElementById('detail-eligible').textContent = eligible;
  document.getElementById('detail-account').textContent = account;

  window.bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
}

function resetCreateForm() {
  form?.reset();
  showFormStatus('');
  if (formSubmitBtn) formSubmitBtn.disabled = false;
}

function openCreateModal() {
  if (!createModalEl || !window.bootstrap) return;
  resetCreateForm();
  window.bootstrap.Modal.getOrCreateInstance(createModalEl).show();
}

function closeCreateModal() {
  if (!createModalEl || !window.bootstrap) return;
  window.bootstrap.Modal.getOrCreateInstance(createModalEl).hide();
}

function collectCreatePayload() {
  const bloodType = fieldBlood?.value?.trim() ?? '';
  const phone = fieldPhone?.value?.trim() ?? '';

  return {
    first_name: fieldFirstName?.value?.trim() ?? '',
    last_name: fieldLastName?.value?.trim() ?? '',
    email: fieldEmail?.value?.trim() ?? '',
    password: fieldPassword?.value ?? '',
    phone: phone || undefined,
    blood_type: bloodType || undefined,
  };
}

table?.addEventListener('change', async (event) => {
  const toggle = event.target.closest('.donor-active-toggle');
  if (!toggle) return;

  const row = toggle.closest('tr');
  if (!row) return;

  const id = row.dataset.id;
  const firstName = row.dataset.firstName ?? '';
  const lastName = row.dataset.lastName ?? '';
  const name = `${firstName} ${lastName}`.trim() || 'Donante';

  const newActive = toggle.checked;
  const previousActive = row.dataset.active === '1';

  toggle.disabled = true;

  try {
    await adminApi.patchUser(id, {
      active: newActive,
    });

    const local = donors.find((item) => String(item.id) === String(id));
    if (local) local.active = newActive;

    setRowActive(row, newActive);
    refreshKpis();

    showStatus(
      `La cuenta de ${name} fue ${newActive ? 'activada' : 'desactivada'} correctamente.`,
      'success'
    );
  } catch (error) {
    console.error('Error al actualizar donante:', error);

    setRowActive(row, previousActive);
    refreshKpis();

    showStatus(
      error?.message || `No se pudo actualizar la cuenta de ${name}.`,
      'danger'
    );
  } finally {
    toggle.disabled = false;
  }
});

table?.addEventListener('click', (event) => {
  const viewBtn = event.target.closest('.btn-view-donor');
  if (!viewBtn) return;
  const row = viewBtn.closest('tr');
  if (row) openDonorDetail(row);
});

btnNewDonor?.addEventListener('click', openCreateModal);

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (formSubmitBtn) formSubmitBtn.disabled = true;
  showFormStatus('');

  try {
    const payload = await adminApi.createUser(collectCreatePayload());
    const created = payload?.data;
    if (created) upsertDonor(created);
    closeCreateModal();
    showStatus(payload?.message || 'Donante creado.', 'success');
  } catch (error) {
    showFormStatus(error?.message || 'No se pudo crear el donante.');
  } finally {
    if (formSubmitBtn) formSubmitBtn.disabled = false;
  }
});

filterSearch?.addEventListener('input', applyFilters);
filterBlood?.addEventListener('change', applyFilters);
filterAccount?.addEventListener('change', applyFilters);
filterEligible?.addEventListener('change', applyFilters);
filterClear?.addEventListener('click', () => {
  if (filterSearch) filterSearch.value = '';
  if (filterBlood) filterBlood.value = '';
  if (filterAccount) filterAccount.value = '';
  if (filterEligible) filterEligible.value = '';
  applyFilters();
});

loadDonors();
