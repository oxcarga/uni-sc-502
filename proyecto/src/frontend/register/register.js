const { usersApi, authApi } = await import(`../js/api.js?t=${Date.now()}`);

const form = document.getElementById('register-form');
const errorBox = document.getElementById('register-error');
const successBox = document.getElementById('register-success');
const successEmail = document.getElementById('register-success-email');
const resendBtn = document.getElementById('register-resend');
const resendStatus = document.getElementById('register-resend-status');

let registeredEmail = '';

if (form) {
  form.addEventListener('submit', handleSubmit);
}

if (resendBtn) {
  resendBtn.addEventListener('click', handleResend);
}

/**
 * Manejador del evento submit del formulario de registro.
 * @param {Event} event - El evento submit.
 */
async function handleSubmit(event) {
  event.preventDefault();
  clearError();

  const data = Object.fromEntries(new FormData(form).entries());
  const email = String(data.email ?? '').trim();

  try {
    await usersApi.create({
      first_name: String(data.firstName ?? '').trim(),
      last_name: String(data.lastName ?? '').trim(),
      email,
      password: String(data.password ?? ''),
    });

    registeredEmail = email;
    showSuccess(email);
  } catch (error) {
    showError(error.message || 'No se pudo completar el registro.');
  }
}

async function handleResend() {
  if (!registeredEmail) return;
  clearError();

  try {
    await authApi.resendConfirmation(registeredEmail);
    if (resendStatus) {
      resendStatus.textContent =
        'Si el correo está pendiente, reenviamos el enlace. Revisa tu bandeja o Mailhog.';
      resendStatus.classList.remove('d-none');
    }
  } catch (error) {
    showError(error.message || 'No se pudo reenviar la confirmación.');
  }
}

function showSuccess(email) {
  if (form) {
    form.classList.add('d-none');
  }

  if (successEmail) {
    successEmail.textContent = email;
  }

  if (successBox) {
    successBox.classList.remove('d-none');
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
