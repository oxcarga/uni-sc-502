const statusEl = document.getElementById('banks-status');
const table = document.getElementById('admin-banks-table');
const kpiTotal = document.getElementById('kpi-total');
const kpiActive = document.getElementById('kpi-active');
const kpiInactive = document.getElementById('kpi-inactive');
const banksCount = document.getElementById('banks-count');
const filterSearch = document.getElementById('filterSearch');
const filterStatus = document.getElementById('filterStatus');
const filterRegion = document.getElementById('filterRegion');
const filterClear = document.getElementById('filterClear');

function showStatus(message, type = 'info') {
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
  statusEl.classList.add(`alert-${type}`);
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
  const rows = [...(table?.querySelectorAll('tbody tr') ?? [])];
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

  table?.querySelectorAll('tbody tr').forEach((row) => {
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

table?.addEventListener('change', (event) => {
  const toggle = event.target.closest('.bank-active-toggle');
  if (!toggle) return;

  const row = toggle.closest('tr');
  if (!row) return;

  const name = row.querySelector('.fw-semibold')?.textContent?.trim() ?? 'Banco';
  const active = toggle.checked;
  setRowActive(row, active);
  refreshKpis();
  showStatus(
    active
      ? `Demo local: ${name} marcado como activo (no se guardó en el servidor).`
      : `Demo local: ${name} marcado como inactivo (no se guardó en el servidor).`,
    'info'
  );
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

refreshKpis();
