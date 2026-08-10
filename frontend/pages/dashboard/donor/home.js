const { donorApi, centersApi, notificationsApi } = await import(`/js/api.js?t=${Date.now()}`);

const bloodEl = document.getElementById('home-blood-type');
const eligibleEl = document.getElementById('home-eligible');
const nearbyEl = document.getElementById('home-nearby-list');
const nextDateEl = document.getElementById('home-next-date');
const nextCenterEl = document.getElementById('home-next-center');
const nextMetaEl = document.getElementById('home-next-meta');
const homeNotifList = document.getElementById('home-notif-list');
const homeNotifCount = document.getElementById('home-notif-count');
const homeAchieveList = document.getElementById('home-achieve-list');
const homeAchieveCount = document.getElementById('home-achieve-count');
const homeAchieveSummary = document.getElementById('home-achieve-summary');

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
}

function parseDate(value) {
  const date = new Date(String(value ?? '').replace(' ', 'T'));
  return Number.isNaN(date.getTime()) ? null : date;
}

try {
  const profilePayload = await donorApi.getProfile();
  const profile = profilePayload?.data?.profile ?? {};
  if (bloodEl) {
    bloodEl.textContent = profile.blood_type ? `Tipo ${profile.blood_type}` : 'Tipo no indicado';
  }
  if (eligibleEl) {
    const eligible = Boolean(profile.eligible);
    eligibleEl.textContent = eligible ? 'Elegible para donar' : 'No elegible por ahora';
    eligibleEl.className = `badge-soft ${eligible ? 'badge-soft--green' : 'badge-soft--slate'}`;
  }
} catch {
  if (bloodEl) bloodEl.textContent = 'Perfil no disponible';
}

try {
  const apptPayload = await donorApi.listAppointments();
  const list = Array.isArray(apptPayload?.data) ? apptPayload.data : [];
  const now = Date.now();
  const next = list
    .filter((item) => item.status === 'pending' || item.status === 'confirmed')
    .filter((item) => {
      const date = parseDate(item.scheduled_at);
      return date && date.getTime() >= now;
    })
    .sort((a, b) => parseDate(a.scheduled_at) - parseDate(b.scheduled_at))[0];

  if (!next) {
    if (nextDateEl) nextDateEl.textContent = 'Sin cita';
    if (nextCenterEl) nextCenterEl.textContent = 'Agenda tu próxima donación.';
    if (nextMetaEl) nextMetaEl.textContent = '—';
  } else {
    const date = parseDate(next.scheduled_at);
    if (nextDateEl && date) {
      nextDateEl.textContent = date.toLocaleDateString('es-CR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
      });
    }
    if (nextCenterEl) nextCenterEl.textContent = next.center_name || 'Centro';
    if (nextMetaEl && date) {
      const time = date.toLocaleTimeString('es-CR', { hour: '2-digit', minute: '2-digit' });
      const status = next.status === 'confirmed' ? 'Confirmada' : 'Pendiente';
      nextMetaEl.textContent = `${time} · ${status}`;
    }
  }
} catch {
  if (nextCenterEl) nextCenterEl.textContent = 'No se pudieron cargar citas.';
}

try {
  const centersPayload = await centersApi.list();
  const centers = Array.isArray(centersPayload?.data) ? centersPayload.data : [];
  if (nearbyEl) {
    if (centers.length === 0) {
      nearbyEl.innerHTML = '<p class="text-muted mb-0" style="font-size: 0.875rem">Sin centros activos.</p>';
    } else {
      nearbyEl.innerHTML = centers
        .slice(0, 2)
        .map(
          (center) => `
          <div class="shortage-row">
            <div class="shortage-info">
              <div class="name">${escapeHtml(center.name)}</div>
              <div class="place">${escapeHtml(center.province || center.region || center.address || '—')}</div>
            </div>
            <span class="badge-soft badge-soft--teal">Activo</span>
          </div>`
        )
        .join('');
    }
  }
} catch {
  if (nearbyEl) {
    nearbyEl.innerHTML = '<p class="text-muted mb-0" style="font-size: 0.875rem">No se pudieron cargar centros.</p>';
  }
}

try {
  const notifPayload = await notificationsApi.list(5);
  const items = Array.isArray(notifPayload?.data?.notifications)
    ? notifPayload.data.notifications
    : [];
  const unread = Number(notifPayload?.data?.unread_count ?? 0);
  if (homeNotifCount) {
    homeNotifCount.textContent = unread ? `${unread} sin leer` : `${items.length} total`;
  }
  if (homeNotifList) {
    if (!items.length) {
      homeNotifList.innerHTML =
        '<p class="text-muted mb-0" style="font-size: 0.875rem">Sin avisos por ahora.</p>';
    } else {
      homeNotifList.innerHTML = items
        .map(
          (item) => `
        <div class="shortage-row">
          <div class="shortage-info">
            <div class="name">${escapeHtml(item.title)}${item.unread ? ' ·' : ''}</div>
            <div class="place">${escapeHtml(item.body || '')}</div>
          </div>
          ${item.unread ? '<span class="badge-soft badge-soft--rose">Nueva</span>' : ''}
        </div>`
        )
        .join('');
    }
  }
} catch {
  if (homeNotifList) {
    homeNotifList.innerHTML =
      '<p class="text-muted mb-0" style="font-size: 0.875rem">No se pudieron cargar avisos.</p>';
  }
}

try {
  const achievePayload = await donorApi.listAchievements();
  const items = Array.isArray(achievePayload?.data?.achievements)
    ? achievePayload.data.achievements
    : [];
  const donationCount = Number(achievePayload?.data?.donation_count ?? 0);
  const unlocked = items.filter((item) => item.unlocked).length;
  if (homeAchieveCount) {
    homeAchieveCount.textContent = `${unlocked}/${items.length}`;
  }
  if (homeAchieveSummary) {
    homeAchieveSummary.textContent =
      donationCount === 1
        ? '1 donacion registrada. Sigue sumando para desbloquear mas insignias.'
        : `${donationCount} donaciones registradas. Sigue sumando para desbloquear mas insignias.`;
  }
  if (homeAchieveList) {
    homeAchieveList.innerHTML = items
      .map((item) => {
        const locked = !item.unlocked;
        const progressLabel = locked
          ? `${item.progress}/${item.criteria_value}`
          : 'Desbloqueado';
        return `
        <div class="achieve-badge ${locked ? 'achieve-badge--locked' : ''}" title="${escapeHtml(item.description || '')}">
          <div class="ico" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4Z"/>
              <path d="M17 9h1a3 3 0 0 0 0-6h-1M7 9H6a3 3 0 0 1 0-6h1"/>
            </svg>
          </div>
          <div class="name">${escapeHtml(item.name)}</div>
          <div class="text-muted" style="font-size: 0.6875rem">${escapeHtml(progressLabel)}</div>
        </div>`;
      })
      .join('');
  }
} catch {
  if (homeAchieveSummary) {
    homeAchieveSummary.textContent = 'No se pudieron cargar los logros.';
  }
}
