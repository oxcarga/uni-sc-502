import { usersApi } from '/js/api.js';

const statusEl = document.getElementById('donors-status');
const table = document.getElementById('admin-donors-table');
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

function formatDate(value) {
  if (!value) return '—';

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return date.toLocaleDateString('es-CR');
}

function renderDonors(donors) {
  const tbody = table?.querySelector('tbody');

  if (!tbody) return;

  tbody.innerHTML = '';

  donors.forEach((donor) => {
    const fullName = `${donor.first_name ?? ''} ${donor.last_name ?? ''}`.trim();
    const bloodType = donor.blood_type ?? '—';
    const phone = donor.phone ?? '—';
    const eligible = donor.eligible === true || donor.eligible === 1 || donor.eligible === '1';
    const active = Boolean(donor.active);
    const lastDonation = formatDate(donor.last_donation_at);

    const row = document.createElement('tr');

    row.dataset.id = String(donor.id);
    row.dataset.firstName = donor.first_name ?? '';
    row.dataset.lastName = donor.last_name ?? '';
    row.dataset.active = active ? '1' : '0';
    row.dataset.eligible = eligible ? '1' : '0';
    row.dataset.blood = bloodType;
    row.dataset.name = fullName.toLowerCase();
    row.dataset.email = String(donor.email ?? '').toLowerCase();

    row.innerHTML = `
      <td>
        <div class="fw-semibold">${fullName || '—'}</div>
        <div class="text-muted" style="font-size: 0.75rem">${donor.email ?? '—'}</div>
      </td>

      <td>
        <span class="blood-badge">${bloodType}</span>
      </td>

      <td>
        <div style="font-size: 0.875rem">${phone}</div>
        <div class="text-muted" style="font-size: 0.75rem">${donor.email ?? '—'}</div>
      </td>

      <td class="fw-semibold" style="font-size: 0.875rem">
        ${lastDonation}
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
              aria-label="Activar cuenta ${fullName}"
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

  refreshKpis();
}

async function loadDonors() {
  try {
    showStatus('Cargando donantes...', 'info');

    const result = await usersApi.list();
    const users = Array.isArray(result.data) ? result.data : [];
    const donors = users.filter((user) => user.role === 'donor');

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

function showStatus(message, type = 'info') {
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
  statusEl.classList.add(`alert-${type}`);
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
  const rows = [...(table?.querySelectorAll('tbody tr') ?? [])];
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

  table?.querySelectorAll('tbody tr').forEach((row) => {
    const name = row.dataset.name ?? '';
    const email = row.dataset.email ?? '';
    const rowBlood = row.dataset.blood ?? '';
    const active = row.dataset.active === '1';
    const isEligible = row.dataset.eligible === '1';

    const matchesSearch = !q || name.includes(q) || email.includes(q);
    const matchesBlood = !blood || rowBlood === blood;
    const matchesAccount =
      !account || (account === 'active' && active) || (account === 'inactive' && !active);
    const matchesEligible =
      !eligible || (eligible === 'yes' && isEligible) || (eligible === 'no' && !isEligible);

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

table?.addEventListener('change', async (event) => {
  const toggle = event.target.closest('.donor-active-toggle');
  if (!toggle) return;

  const row = toggle.closest('tr');
  if (!row) return;

  const id = row.dataset.id;
  const firstName = row.dataset.firstName ?? '';
  const lastName = row.dataset.lastName ?? '';
  const email = row.dataset.email ?? '';
  const name = `${firstName} ${lastName}`.trim() || 'Donante';

  const newActive = toggle.checked;
  const previousActive = row.dataset.active === '1';

  toggle.disabled = true;

  try {
    await usersApi.update(id, {
      first_name: firstName,
      last_name: lastName,
      email,
      active: newActive,
    });

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
      `No se pudo actualizar la cuenta de ${name}.`,
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
