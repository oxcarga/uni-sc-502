const { adminApi } = await import(`/js/api.js?t=${Date.now()}`);

const tableBody = document.querySelector('#audit-table tbody');
const countEl = document.getElementById('audit-count');

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function formatWhen(value) {
  const date = new Date(String(value ?? '').replace(' ', 'T'));
  if (Number.isNaN(date.getTime())) return '—';
  return date.toLocaleString('es-CR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function actorName(row) {
  const name = `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim();
  return name || row.email || (row.user_id ? `Usuario #${row.user_id}` : 'Sistema');
}

try {
  const payload = await adminApi.listAuditLog(100);
  const list = Array.isArray(payload?.data?.entries) ? payload.data.entries : [];
  if (countEl) countEl.textContent = `${list.length} registros`;
  if (!tableBody) {
    // no-op
  } else if (!list.length) {
    tableBody.innerHTML =
      '<tr><td colspan="5" class="text-muted text-center py-4">Sin registros aún. Cambia políticas o asigna una solicitud.</td></tr>';
  } else {
    tableBody.innerHTML = list
      .map(
        (row) => `
      <tr>
        <td class="text-muted" style="font-size: 0.875rem">${escapeHtml(formatWhen(row.created_at))}</td>
        <td>
          <div class="fw-semibold">${escapeHtml(actorName(row))}</div>
          <div class="text-muted" style="font-size: 0.75rem">${escapeHtml(row.email || '')}</div>
        </td>
        <td class="fw-semibold">${escapeHtml(row.action)}</td>
        <td class="text-muted" style="font-size: 0.875rem">
          ${escapeHtml(row.entity_type || '—')}${row.entity_id != null ? ` #${escapeHtml(row.entity_id)}` : ''}
        </td>
        <td class="text-muted" style="font-size: 0.8125rem; max-width: 18rem; word-break: break-word">
          ${escapeHtml(row.detail || '—')}
        </td>
      </tr>`
      )
      .join('');
  }
} catch (error) {
  if (tableBody) {
    tableBody.innerHTML = `<tr><td colspan="5" class="text-danger text-center py-4">${escapeHtml(error?.message || 'Error al cargar')}</td></tr>`;
  }
}
