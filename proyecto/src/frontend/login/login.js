const { authApi, saveSession, panelPathForRole } = await import(`../js/api.js?t=${Date.now()}`);

const form = document.getElementById('login-form');
const errorBox = document.getElementById('login-error');

if (form) {
  form.addEventListener('submit', handleSubmit);
}

/**
 * Manejador del evento submit del formulario de login.
 * @param {Event} event - El evento submit.
 */
async function handleSubmit(event) {
  event.preventDefault();
  clearError();

  const data = Object.fromEntries(new FormData(form).entries());

  try {
    const payload = await authApi.login({
      email: String(data.email ?? '').trim(),
      password: String(data.password ?? ''),
    });

    const user = payload?.data;
    if (!user?.rol) {
      throw new Error('Respuesta de login inválida.');
    }

    saveSession(user);
    window.location.href = panelPathForRole(user.rol);
  } catch (error) {
    showError(error.message || 'No se pudo iniciar sesión.');
  }
}

function showError(message) {
  if (!errorBox) return;
  errorBox.textContent = message;
  errorBox.classList.remove('d-none');
}

function clearError() {
  if (!errorBox) return;
  errorBox.textContent = '';
  errorBox.classList.add('d-none');
}
