/**
 * Campana de notificaciones in-app (cualquier dashboard con #notif-bell).
 */
const { notificationsApi } = await import(`./api.js?t=${Date.now()}`);

const bellBtn = document.getElementById('notif-bell');
const badgeEl = document.getElementById('notif-badge');
const panelEl = document.getElementById('notif-panel');
const listEl = document.getElementById('notif-list');

if (!bellBtn || !panelEl || !listEl) {
  // Página sin campana cableada
} else {
  bellBtn.disabled = false;
  bellBtn.removeAttribute('title');
  bellBtn.setAttribute('aria-label', 'Notificaciones');
  bellBtn.setAttribute('aria-expanded', 'false');

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;');
  }

  function formatWhen(value) {
    const date = new Date(String(value ?? '').replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return '';
    return date.toLocaleString('es-CR', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  }

  function setBadge(count) {
    if (!badgeEl) return;
    if (count > 0) {
      badgeEl.textContent = count > 9 ? '9+' : String(count);
      badgeEl.classList.remove('d-none');
    } else {
      badgeEl.classList.add('d-none');
    }
  }

  function renderList(items) {
    if (!items.length) {
      listEl.innerHTML =
        '<p class="text-muted mb-0 px-3 py-3" style="font-size: 0.875rem">No hay notificaciones.</p>';
      return;
    }
    listEl.innerHTML = items
      .map(
        (item) => `
      <button type="button" class="notif-item ${item.unread ? 'notif-item--unread' : ''}" data-id="${item.id}">
        <div class="notif-item__title">${escapeHtml(item.title)}</div>
        <div class="notif-item__body">${escapeHtml(item.body || '')}</div>
        <div class="notif-item__meta">${escapeHtml(formatWhen(item.created_at))}</div>
      </button>`
      )
      .join('');
  }

  async function loadNotifications() {
    try {
      const payload = await notificationsApi.list(20);
      const list = Array.isArray(payload?.data?.notifications) ? payload.data.notifications : [];
      setBadge(Number(payload?.data?.unread_count ?? 0));
      renderList(list);
      document.dispatchEvent(
        new CustomEvent('notifications:loaded', { detail: payload?.data })
      );
    } catch {
      listEl.innerHTML =
        '<p class="text-muted mb-0 px-3 py-3" style="font-size: 0.875rem">No se pudieron cargar.</p>';
    }
  }

  function closePanel() {
    panelEl.classList.remove('show');
    bellBtn.setAttribute('aria-expanded', 'false');
  }

  function togglePanel(event) {
    event.preventDefault();
    event.stopPropagation();
    const willOpen = !panelEl.classList.contains('show');
    panelEl.classList.toggle('show', willOpen);
    bellBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    if (willOpen) loadNotifications();
  }

  bellBtn.addEventListener('click', togglePanel);
  document.addEventListener('click', (event) => {
    if (!panelEl.contains(event.target) && event.target !== bellBtn) {
      closePanel();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closePanel();
  });

  listEl.addEventListener('click', async (event) => {
    const item = event.target.closest('.notif-item');
    if (!item) return;
    const id = Number(item.dataset.id);
    if (!id || !item.classList.contains('notif-item--unread')) return;
    try {
      await notificationsApi.markRead(id);
      await loadNotifications();
    } catch {
      // silencioso
    }
  });

  await loadNotifications();
}
