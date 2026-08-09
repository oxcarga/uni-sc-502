const { donorApi, centersApi } = await import(`/js/api.js?t=${Date.now()}`);

const bloodEl = document.getElementById('home-blood-type');
const eligibleEl = document.getElementById('home-eligible');
const nearbyEl = document.getElementById('home-nearby-list');

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;');
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
