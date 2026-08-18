const { 
  usersApi, 
  authApi, 
  configApi, 
  isLocalEnvironment 
} = await import(`/js/api.js?t=${Date.now()}`);

const form = document.getElementById('register-form');
const errorBox = document.getElementById('register-error');
const successBox = document.getElementById('register-success');
const successEmail = document.getElementById('register-success-email');
const mailhogTip = document.getElementById('register-mailhog-tip');
const resendBtn = document.getElementById('register-resend');
const resendStatus = document.getElementById('register-resend-status');
const passwordInput = document.getElementById('password');
const passwordConfirmInput = document.getElementById('passwordConfirm');

let registeredEmail = '';
let isLocal = false;

try {
  const config = await configApi.get();
  isLocal = isLocalEnvironment(config?.data?.environment);
} catch {
  // Si no hay config, no mostrar tips de desarrollo.
  isLocal = false;
}

if (form) {
  form.addEventListener('submit', handleSubmit);
}

if (resendBtn) {
  resendBtn.addEventListener('click', handleResend);
}

passwordInput?.addEventListener('input', syncPasswordMatchValidity);
passwordConfirmInput?.addEventListener('input', syncPasswordMatchValidity);

/**
 * Manejador del evento submit del formulario de registro.
 * @param {Event} event - El evento submit.
 */
async function handleSubmit(event) {
  event.preventDefault();
  clearError();
  syncPasswordMatchValidity();

  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }

  const data = Object.fromEntries(new FormData(form).entries());
  const password = String(data.password ?? '');
  const passwordConfirm = String(data.passwordConfirm ?? '');

  if (password !== passwordConfirm) {
    showError('Las contraseñas no coinciden.');
    return;
  }

  const email = String(data.email ?? '').trim();

  try {
    await usersApi.create({
      first_name: String(data.firstName ?? '').trim(),
      last_name: String(data.lastName ?? '').trim(),
      email,
      password,
      password_confirm: passwordConfirm,
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
      resendStatus.textContent = isLocal
        ? 'Si el correo está pendiente, reenviamos el enlace. Revisa tu bandeja o Mailhog.'
        : 'Si el correo está pendiente, reenviamos el enlace. Revisa tu bandeja.';
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

  if (mailhogTip) {
    mailhogTip.classList.toggle('d-none', !isLocal);
  }

  if (successBox) {
    successBox.classList.remove('d-none');
  }
}

function passwordsMatch() {
  return (passwordInput?.value ?? '') === (passwordConfirmInput?.value ?? '');
}

function syncPasswordMatchValidity() {
  if (!passwordConfirmInput) return;
  if (passwordConfirmInput.value && !passwordsMatch()) {
    passwordConfirmInput.setCustomValidity('Las contraseñas no coinciden.');
  } else {
    passwordConfirmInput.setCustomValidity('');
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
