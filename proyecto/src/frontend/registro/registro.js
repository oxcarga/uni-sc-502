const { usersApi } = await import(`../js/api.js?t=${Date.now()}`);

const form = document.getElementById('registro-form');
const errorBox = document.getElementById('registro-error');

if (form) {
  form.addEventListener('submit', handleSubmit);
}

/**
 * Manejador del evento submit del formulario de registro.
 * @param {Event} event - El evento submit.
 */
async function handleSubmit(event) {
  event.preventDefault();
  clearError();

  const data = Object.fromEntries(new FormData(form).entries());

  try {
    await usersApi.create({
      nombre: String(data.firstName ?? '').trim(),
      apellido: String(data.lastName ?? '').trim(),
      email: String(data.email ?? '').trim(),
      password: String(data.password ?? ''),
    });
    window.location.href = '/login/';
  } catch (error) {
    showError(error.message || 'No se pudo completar el registro.');
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
