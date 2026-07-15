const { authApi, saveSession, panelPathForRole } = await import(`../js/api.js?t=${Date.now()}`);

const form = document.getElementById('login-form');
const errorBox = document.getElementById('login-error');
const resendWrap = document.getElementById('login-resend-wrap');
const resendBtn = document.getElementById('login-resend');

if (form) {
  form.addEventListener('submit', handleSubmit);
}

if (resendBtn) {
  resendBtn.addEventListener('click', handleResend);
}

/**
 * Manejador del evento submit del formulario de login.
 * @param {Event} event - El evento submit.
 */
async function handleSubmit(event) {
  event.preventDefault();
  clearError();
  hideResend();

  const data = Object.fromEntries(new FormData(form).entries());

  try {
    const payload = await authApi.login({
      email: String(data.email ?? '').trim(),
      password: String(data.password ?? ''),
    });

    const user = payload?.data;
    if (!user?.role) {
      throw new Error('Respuesta de login inválida.');
    }

    saveSession(user);
    window.location.href = panelPathForRole(user.role);
  } catch (error) {
    showError(error.message || 'No se pudo iniciar sesión.');
    if (error.status === 403) {
      showResend();
    }
  }
}

async function handleResend() {
  const data = Object.fromEntries(new FormData(form).entries());
  const email = String(data.email ?? '').trim();
  if (!email) {
    showError('Indica tu correo para reenviar la confirmación.');
    return;
  }

  try {
    await authApi.resendConfirmation(email);
    showError(
      'Si el correo está pendiente de confirmación, enviamos un nuevo enlace. Revisa tu bandeja o Mailhog (http://localhost:8025).'
    );
    errorBox?.classList.remove('alert-danger');
    errorBox?.classList.add('alert-info');
  } catch (error) {
    showError(error.message || 'No se pudo reenviar la confirmación.');
  }
}

function showError(message) {
  if (!errorBox) return;
  errorBox.classList.remove('alert-info');
  errorBox.classList.add('alert-danger');
  errorBox.textContent = message;
  errorBox.classList.remove('d-none');
}

function clearError() {
  if (!errorBox) return;
  errorBox.textContent = '';
  errorBox.classList.add('d-none');
  errorBox.classList.remove('alert-info');
  errorBox.classList.add('alert-danger');
}

function showResend() {
  resendWrap?.classList.remove('d-none');
}

function hideResend() {
  resendWrap?.classList.add('d-none');
}
