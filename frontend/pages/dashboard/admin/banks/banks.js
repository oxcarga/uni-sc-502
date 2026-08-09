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

function showStatus(message, type = 'info') {
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
  statusEl.classList.add(`alert-${type}`);
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
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

function populateRegionFilter(centers) {
  if (!filterRegion) return;
  const regions = [...new Set(centers.map((c) => c.region || c.province).filter(Boolean))].sort();
  const current = filterRegion.value;
  filterRegion.innerHTML =
    '<option value="">Todas</option>' +
    regions.map((region) => `<option value="${escapeHtml(region)}">${escapeHtml(region)}</option>`).join('');
  if (regions.includes(current)) filterRegion.value = current;
}

function renderCenters(centers) {
  if (!tableBody) return;

  if (centers.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" class="text-muted text-center py-4">No hay centros en la base de datos.</td>
      </tr>`;
    refreshKpis();
    return;
  }

  tableBody.innerHTML = centers
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
            <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold" disabled
              title="Escritura de centros aplazada">Editar</button>
          </div>
        </td>
      </tr>`;
    })
    .join('');

  populateRegionFilter(centers);
  applyFilters();
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
    `Demo local: ${name} marcado como ${active ? 'activo' : 'inactivo'} (no se guardó; escritura aplazada).`,
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

try {
  const payload = await centersApi.list({ all: true });
  const centers = Array.isArray(payload?.data) ? payload.data : [];
  renderCenters(centers);
  showStatus(
    `Listado desde GET /api/centers?all=1 · ${centers.length} centro(s). La escritura (crear/editar) queda aplazada.`,
    'info'
  );
} catch (error) {
  showStatus(error?.message || 'No se pudieron cargar los centros.', 'danger');
}
