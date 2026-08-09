const { getCachedSession } = await import(`/js/api.js?t=${Date.now()}`);

const form = document.getElementById('bank-settings-form');
const statusEl = document.getElementById('settings-status');
const accountEmailEl = document.getElementById('accountEmail');
const accountNameEl = document.getElementById('accountName');

function showStatus(message, type = 'info') {
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-info');
  statusEl.classList.add(`alert-${type}`);
}

const user = getCachedSession();
if (user) {
  if (accountEmailEl) accountEmailEl.value = user.email ?? '';
  if (accountNameEl) {
    accountNameEl.value = `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
  }
}

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
    'Formulario válido en el cliente. Aún no se guarda: la API del centro se conectará en fases posteriores.',
    'info'
  );
});
