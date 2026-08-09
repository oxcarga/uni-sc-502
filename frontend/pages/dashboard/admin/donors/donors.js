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

table?.addEventListener('change', (event) => {
  const toggle = event.target.closest('.donor-active-toggle');
  if (!toggle) return;

  const row = toggle.closest('tr');
  if (!row) return;

  const name = row.querySelector('.fw-semibold')?.textContent?.trim() ?? 'Donante';
  const active = toggle.checked;
  setRowActive(row, active);
  refreshKpis();
  showStatus(
    active
      ? `Demo local: la cuenta de ${name} quedó marcada como activa (no se guardó en el servidor).`
      : `Demo local: la cuenta de ${name} quedó marcada como inactiva (no se guardó en el servidor).`,
    'info'
  );
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

refreshKpis();
