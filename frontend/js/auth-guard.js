/**
 * Guard de rutas internas (/dashboard/*).
 * Requiere <body data-required-role="donor|bank|admin">.
 */
const { authApi, cacheSession, clearSessionCache, dashboardPathForRole } = await import(
  `./api.js?t=${Date.now()}`
);

const ROLE_LABELS = {
  donor: 'Donante',
  bank: 'Banco',
  admin: 'Administrador',
};

const requiredRole = document.body?.dataset?.requiredRole ?? '';
const userNameEl = document.getElementById('dashboard-user-name');
const userRoleEl = document.getElementById('dashboard-user-role');
const userInitialsEl = document.getElementById('dashboard-user-initials');
const greetingNameEl = document.getElementById('dashboard-greeting-name');
const homeNameEl = document.getElementById('dashboard-home-name');
const logoutBtn = document.getElementById('dashboard-logout');

function initialsFromName(firstName = '', lastName = '') {
  const a = firstName.trim().charAt(0);
  const b = lastName.trim().charAt(0);
  return `${a}${b}`.toUpperCase() || 'PS';
}

try {
  const payload = await authApi.me();
  const user = payload?.data;

  if (!user?.role) {
    throw new Error('Sesión inválida');
  }

  cacheSession(user);

  if (requiredRole && user.role !== requiredRole) {
    window.location.replace(dashboardPathForRole(user.role));
  } else {
    const firstName = (user.first_name ?? '').trim();
    const lastName = (user.last_name ?? '').trim();
    const fullName = `${firstName} ${lastName}`.trim();
    const shortName = firstName || fullName || 'usuario';

    if (userNameEl) {
      userNameEl.textContent = fullName || shortName;
    }
    if (userRoleEl) {
      userRoleEl.textContent = ROLE_LABELS[user.role] ?? user.role;
    }
    if (userInitialsEl) {
      userInitialsEl.textContent = initialsFromName(firstName, lastName);
    }
    if (greetingNameEl) {
      greetingNameEl.textContent = shortName;
    }
    if (homeNameEl) {
      homeNameEl.textContent = shortName;
    }
    document.body?.classList.remove('d-none');
    if (document.getElementById('notif-bell')) {
      import(`./notifications-ui.js?t=${Date.now()}`);
    }
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

/** Menú de usuario del topbar (funciona con o sin Bootstrap JS). */
const userMenuRoot = userInitialsEl?.closest('.dropdown');
const userMenu = userMenuRoot?.querySelector('.dropdown-menu');

function closeUserMenu() {
  userMenu?.classList.remove('show');
  userInitialsEl?.setAttribute('aria-expanded', 'false');
}

function toggleUserMenu(event) {
  event.preventDefault();
  event.stopPropagation();
  const willOpen = !userMenu?.classList.contains('show');
  userMenu?.classList.toggle('show', willOpen);
  userInitialsEl?.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
}

if (userInitialsEl && userMenu) {
  userInitialsEl.addEventListener('click', toggleUserMenu);
  document.addEventListener('click', (event) => {
    if (!userMenuRoot?.contains(event.target)) {
      closeUserMenu();
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeUserMenu();
    }
  });
}
