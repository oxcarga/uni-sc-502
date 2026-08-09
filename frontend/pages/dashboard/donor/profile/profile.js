const { donorApi, cacheSession } = await import(`/js/api.js?t=${Date.now()}`);

const form = document.getElementById('donor-profile-form');
const statusEl = document.getElementById('profile-status');
const saveBtn = document.getElementById('profile-save-btn');
const firstNameEl = document.getElementById('firstName');
const lastNameEl = document.getElementById('lastName');
const emailEl = document.getElementById('email');
const phoneEl = document.getElementById('phone');
const bloodTypeEl = document.getElementById('bloodType');
const birthDateEl = document.getElementById('birthDate');
const lastDonationAtEl = document.getElementById('lastDonationAt');
const medicalHistoryEl = document.getElementById('medicalHistory');
const provinceEl = document.getElementById('province');
const cantonEl = document.getElementById('canton');
const addressEl = document.getElementById('address');
const notifyNearbyEl = document.getElementById('notifyNearby');
const notifyAppointmentsEl = document.getElementById('notifyAppointments');
const notifyBloodMatchEl = document.getElementById('notifyBloodMatch');
const displayNameEl = document.getElementById('profile-display-name');
const displayEmailEl = document.getElementById('profile-display-email');
const avatarEl = document.getElementById('profile-avatar');
const bloodBadgeEl = document.getElementById('profile-blood-badge');
const eligibleBadgeEl = document.getElementById('profile-eligible-badge');
const newPasswordEl = document.getElementById('newPassword');
const confirmPasswordEl = document.getElementById('confirmPassword');

/** Snapshot del último estado persistido (o cargado). */
let savedSnapshot = '';

function initials(firstName = '', lastName = '') {
  const a = firstName.trim().charAt(0);
  const b = lastName.trim().charAt(0);
  return `${a}${b}`.toUpperCase() || 'PS';
}

function syncSummary() {
  const firstName = firstNameEl?.value?.trim() ?? '';
  const lastName = lastNameEl?.value?.trim() ?? '';
  const email = emailEl?.value?.trim() ?? '';
  const bloodType = bloodTypeEl?.value ?? '';

  if (displayNameEl) {
    displayNameEl.textContent = `${firstName} ${lastName}`.trim() || 'Donante';
  }
  if (displayEmailEl) {
    displayEmailEl.textContent = email || '—';
  }
  if (avatarEl) {
    avatarEl.textContent = initials(firstName, lastName);
  }
  if (bloodBadgeEl) {
    bloodBadgeEl.textContent = bloodType ? `Tipo: ${bloodType}` : 'Tipo: —';
  }
}

const STATUS_AUTO_HIDE_MS = 7000;
const STATUS_FADE_MS = 400;
let statusHideTimer = null;
let statusFadeTimer = null;

function clearStatusTimers() {
  if (statusHideTimer) {
    clearTimeout(statusHideTimer);
    statusHideTimer = null;
  }
  if (statusFadeTimer) {
    clearTimeout(statusFadeTimer);
    statusFadeTimer = null;
  }
}

function hideStatus() {
  if (!statusEl) return;
  clearStatusTimers();
  statusEl.classList.add('is-fading');
  statusFadeTimer = setTimeout(() => {
    statusEl.classList.add('d-none');
    statusEl.classList.remove('is-fading');
    statusEl.textContent = '';
    statusFadeTimer = null;
  }, STATUS_FADE_MS);
}

function showStatus(message, type = 'info') {
  if (!statusEl) return;
  clearStatusTimers();
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'is-fading', 'alert-success', 'alert-danger', 'alert-info');
  statusEl.classList.add(`alert-${type}`);

  // Errores permanecen; éxito/info se desvanecen solos.
  if (type !== 'danger') {
    statusHideTimer = setTimeout(hideStatus, STATUS_AUTO_HIDE_MS);
  }
}

function currentFormSnapshot() {
  return JSON.stringify({
    first_name: firstNameEl?.value?.trim() ?? '',
    last_name: lastNameEl?.value?.trim() ?? '',
    phone: phoneEl?.value?.trim() ?? '',
    blood_type: bloodTypeEl?.value ?? '',
    birth_date: birthDateEl?.value || '',
    medical_history: medicalHistoryEl?.value?.trim() ?? '',
    province: provinceEl?.value ?? '',
    canton: cantonEl?.value?.trim() ?? '',
    address: addressEl?.value?.trim() ?? '',
    notify_nearby: Boolean(notifyNearbyEl?.checked),
    notify_appointments: Boolean(notifyAppointmentsEl?.checked),
    notify_blood_match: Boolean(notifyBloodMatchEl?.checked),
    new_password: newPasswordEl?.value ?? '',
    confirm_password: confirmPasswordEl?.value ?? '',
  });
}

function isFormDirty() {
  return currentFormSnapshot() !== savedSnapshot;
}

function updateSaveButtonState() {
  if (!saveBtn) return;
  saveBtn.disabled = !isFormDirty();
}

function markFormClean() {
  if (newPasswordEl) newPasswordEl.value = '';
  if (confirmPasswordEl) confirmPasswordEl.value = '';
  savedSnapshot = currentFormSnapshot();
  updateSaveButtonState();
}

function fillForm(payload) {
  const user = payload?.user ?? {};
  const profile = payload?.profile ?? {};

  if (firstNameEl) firstNameEl.value = user.first_name ?? '';
  if (lastNameEl) lastNameEl.value = user.last_name ?? '';
  if (emailEl) emailEl.value = user.email ?? '';
  if (phoneEl) phoneEl.value = profile.phone ?? '';
  if (bloodTypeEl) bloodTypeEl.value = profile.blood_type ?? '';
  if (birthDateEl) birthDateEl.value = profile.birth_date ?? '';
  if (lastDonationAtEl) lastDonationAtEl.value = profile.last_donation_at ?? '';
  if (medicalHistoryEl) medicalHistoryEl.value = profile.medical_history ?? '';
  if (provinceEl) provinceEl.value = profile.province ?? '';
  if (cantonEl) cantonEl.value = profile.canton ?? '';
  if (addressEl) addressEl.value = profile.address ?? '';
  if (notifyNearbyEl) notifyNearbyEl.checked = Boolean(profile.notify_nearby);
  if (notifyAppointmentsEl) notifyAppointmentsEl.checked = Boolean(profile.notify_appointments);
  if (notifyBloodMatchEl) notifyBloodMatchEl.checked = Boolean(profile.notify_blood_match);

  if (eligibleBadgeEl) {
    const eligible = Boolean(profile.eligible);
    eligibleBadgeEl.textContent = eligible ? 'Elegible' : 'No elegible';
    eligibleBadgeEl.className = `badge-soft ${eligible ? 'badge-soft--green' : 'badge-soft--slate'}`;
  }

  cacheSession({
    id: user.id,
    first_name: user.first_name,
    last_name: user.last_name,
    email: user.email,
    role: user.role,
  });

  syncSummary();
  markFormClean();
}

try {
  const payload = await donorApi.getProfile();
  fillForm(payload?.data);
} catch (error) {
  showStatus(error?.message || 'No se pudo cargar el perfil.', 'danger');
  updateSaveButtonState();
}

['input', 'change'].forEach((eventName) => {
  form?.addEventListener(eventName, () => {
    syncSummary();
    updateSaveButtonState();
  });
});

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  if (!isFormDirty()) return;

  const password = newPasswordEl?.value ?? '';
  const confirm = confirmPasswordEl?.value ?? '';

  if (password || confirm) {
    if (password.length < 8) {
      showStatus('La contraseña debe tener al menos 8 caracteres.', 'danger');
      return;
    }
    if (password !== confirm) {
      showStatus('Las contraseñas no coinciden.', 'danger');
      return;
    }
  }

  const body = {
    first_name: firstNameEl?.value?.trim() ?? '',
    last_name: lastNameEl?.value?.trim() ?? '',
    phone: phoneEl?.value?.trim() ?? '',
    blood_type: bloodTypeEl?.value ?? '',
    birth_date: birthDateEl?.value || null,
    medical_history: medicalHistoryEl?.value?.trim() ?? '',
    province: provinceEl?.value ?? '',
    canton: cantonEl?.value?.trim() ?? '',
    address: addressEl?.value?.trim() ?? '',
    notify_nearby: Boolean(notifyNearbyEl?.checked),
    notify_appointments: Boolean(notifyAppointmentsEl?.checked),
    notify_blood_match: Boolean(notifyBloodMatchEl?.checked),
  };

  if (password) {
    body.password = password;
    body.password_confirm = confirm;
  }

  try {
    const payload = await donorApi.updateProfile(body);
    fillForm(payload?.data);
    showStatus(payload?.message || 'Perfil actualizado.', 'success');
  } catch (error) {
    showStatus(error?.message || 'No se pudo guardar el perfil.', 'danger');
  } finally {
    updateSaveButtonState();
  }
});
