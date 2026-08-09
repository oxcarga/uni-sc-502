const { getCachedSession } = await import(`/js/api.js?t=${Date.now()}`);

const form = document.getElementById('donor-profile-form');
const statusEl = document.getElementById('profile-status');
const firstNameEl = document.getElementById('firstName');
const lastNameEl = document.getElementById('lastName');
const emailEl = document.getElementById('email');
const bloodTypeEl = document.getElementById('bloodType');
const displayNameEl = document.getElementById('profile-display-name');
const displayEmailEl = document.getElementById('profile-display-email');
const avatarEl = document.getElementById('profile-avatar');
const bloodBadgeEl = document.getElementById('profile-blood-badge');

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

function showStatus(message, ok = true) {
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'alert-success', 'alert-danger');
  statusEl.classList.add(ok ? 'alert-success' : 'alert-danger');
}

const user = getCachedSession();
if (user) {
  if (firstNameEl) firstNameEl.value = user.first_name ?? '';
  if (lastNameEl) lastNameEl.value = user.last_name ?? '';
  if (emailEl) emailEl.value = user.email ?? '';
  if (bloodTypeEl && user.blood_type) bloodTypeEl.value = user.blood_type;
}

// Demo defaults until donor profile API exists
if (bloodTypeEl && !bloodTypeEl.value) bloodTypeEl.value = 'A+';
const phoneEl = document.getElementById('phone');
const provinceEl = document.getElementById('province');
if (phoneEl && !phoneEl.value) phoneEl.value = '8888-0000';
if (provinceEl && !provinceEl.value) provinceEl.value = 'San José';

syncSummary();

['input', 'change'].forEach((eventName) => {
  form?.addEventListener(eventName, syncSummary);
});

form?.addEventListener('submit', (event) => {
  event.preventDefault();

  const password = document.getElementById('newPassword')?.value ?? '';
  const confirm = document.getElementById('confirmPassword')?.value ?? '';

  if (password || confirm) {
    if (password.length < 8) {
      showStatus('La contraseña debe tener al menos 8 caracteres.', false);
      return;
    }
    if (password !== confirm) {
      showStatus('Las contraseñas no coinciden.', false);
      return;
    }
  }

  // Placeholder until PUT /api/donor/profile exists
  showStatus('Cambios listos para guardar. La API de perfil se conectará en una siguiente fase.');
});
