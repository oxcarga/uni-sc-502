/**
 * Guard de rutas internas (/panel/*).
 * Requiere <body data-required-role="donor|bank|admin">.
 */
const { authApi, cacheSession, clearSessionCache, panelPathForRole } = await import(
  `./api.js?t=${Date.now()}`
);

const requiredRole = document.body?.dataset?.requiredRole ?? '';
const userNameEl = document.getElementById('panel-user-name');
const logoutBtn = document.getElementById('panel-logout');

try {
  const payload = await authApi.me();
  const user = payload?.data;

  if (!user?.role) {
    throw new Error('Sesión inválida');
  }

  cacheSession(user);

  if (requiredRole && user.role !== requiredRole) {
    window.location.replace(panelPathForRole(user.role));
  } else {
    if (userNameEl) {
      userNameEl.textContent = `${user.first_name ?? ''} ${user.last_name ?? ''}`.trim();
    }
    document.body?.classList.remove('d-none');
  }
} catch {
  clearSessionCache();
  window.location.replace('/login/');
}

if (logoutBtn) {
  logoutBtn.addEventListener('click', async () => {
    try {
      await authApi.logout();
    } catch {
      // Igual limpiamos y salimos
    }
    clearSessionCache();
    window.location.href = '/login/';
  });
}
