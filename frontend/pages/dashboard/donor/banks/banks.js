const { centersApi } = await import(`/js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('banks-status');
const countEl = document.getElementById('banks-count');
const nearbyEl = document.getElementById('banks-nearby-list');
const tableBody = document.querySelector('#donor-banks-table tbody');

function showStatus(message, type = 'info') {
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
  statusEl.classList.add(`alert-${type}`);
}

function formatHours(center) {
  const open = center.open_time ? String(center.open_time).slice(0, 5) : null;
  const close = center.close_time ? String(center.close_time).slice(0, 5) : null;
  if (open && close) {
    return `${center.open_days ?? 'Horario'} · ${open}–${close}`;
  }
  return center.open_days || 'Horario no publicado';
}

function renderNearby(centers) {
  if (!nearbyEl) return;
  if (centers.length === 0) {
    nearbyEl.innerHTML = '<p class="text-muted mb-0" style="font-size: 0.875rem">No hay centros activos.</p>';
    return;
  }

  nearbyEl.innerHTML = centers
    .slice(0, 3)
    .map(
      (center) => `
      <div class="shortage-row">
        <div class="shortage-info">
          <div class="name">${escapeHtml(center.name)}</div>
          <div class="place">${escapeHtml([center.province, center.canton].filter(Boolean).join(' · ') || center.address || '—')}</div>
        </div>
        <span class="badge-soft badge-soft--teal">Activo</span>
      </div>`
    )
    .join('');
}

function renderTable(centers) {
  if (!tableBody) return;

  if (centers.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" class="text-muted text-center py-4">No hay centros activos por ahora.</td>
      </tr>`;
    return;
  }

  tableBody.innerHTML = centers
    .map(
      (center) => `
      <tr>
        <td>
          <div class="fw-semibold">${escapeHtml(center.name)}</div>
          <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(center.code || `ID ${center.id}`)}</div>
        </td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(center.address || '—')}</td>
        <td class="fw-semibold" style="font-size: 0.875rem">${escapeHtml(center.region || center.province || '—')}</td>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(center.contact_phone || '—')}</td>
        <td class="text-end">
          <a href="/dashboard/donor/appointments/" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold">Ver citas</a>
        </td>
      </tr>`
    )
    .join('');
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

try {
  const payload = await centersApi.list();
  const centers = Array.isArray(payload?.data) ? payload.data : [];
  if (countEl) countEl.textContent = `${centers.length} centro${centers.length === 1 ? '' : 's'}`;
  renderNearby(centers);
  renderTable(centers);
  showStatus(
    centers.length
      ? `Listado desde la API · ${formatHours(centers[0])}`
      : 'No hay centros activos en el sistema.',
    'info'
  );
} catch (error) {
  showStatus(error?.message || 'No se pudieron cargar los centros.', 'danger');
  if (tableBody) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="5" class="text-muted text-center py-4">Error al cargar centros.</td>
      </tr>`;
  }
}
