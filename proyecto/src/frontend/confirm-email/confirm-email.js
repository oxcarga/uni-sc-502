const { authApi, saveSession, panelPathForRole } = await import(`../js/api.js?t=${Date.now()}`);

const statusEl = document.getElementById('confirm-status');
const errorBox = document.getElementById('confirm-error');

const token = new URLSearchParams(window.location.search).get('token') ?? '';

if (!token) {
  showError('El enlace de confirmación no incluye un token válido.');
} else {
  confirmToken(token);
}

async function confirmToken(value) {
  try {
    const payload = await authApi.confirmEmail(value);
    const user = payload?.data;

    if (!user?.role) {
      throw new Error('Respuesta de confirmación inválida.');
    }

    saveSession(user);
    if (statusEl) {
      statusEl.textContent = 'Correo confirmado. Entrando a tu panel…';
    }
    window.location.href = panelPathForRole(user.role);
  } catch (error) {
    showError(error.message || 'No se pudo confirmar el correo.');
  }
}

function showError(message) {
  if (statusEl) {
    statusEl.textContent = 'No pudimos confirmar tu correo.';
  }
  if (!errorBox) return;
  errorBox.textContent = message;
  errorBox.classList.remove('d-none');
}
