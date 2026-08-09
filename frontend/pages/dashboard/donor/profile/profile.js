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

function showStatus(message, type = 'info') {
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
  statusEl.classList.add(`alert-${type}`);
}

const user = getCachedSession();
if (user) {
  if (firstNameEl) firstNameEl.value = user.first_name ?? '';
  if (lastNameEl) lastNameEl.value = user.last_name ?? '';
  if (emailEl) emailEl.value = user.email ?? '';
}

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
      showStatus('La contraseña debe tener al menos 8 caracteres.', 'danger');
      return;
    }
    if (password !== confirm) {
      showStatus('Las contraseñas no coinciden.', 'danger');
      return;
    }
  }

  showStatus(
    'Formulario válido en el cliente. Aún no se guarda: la API de perfil se conecta en P4.',
    'info'
  );
});
