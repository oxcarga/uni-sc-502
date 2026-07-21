/**
 * Cliente HTTP para la API REST del backend (prefijo /api).
 * Nginx hace proxy de /api hacia el contenedor backend.
 * credentials: 'include' envía/recibe la cookie de sesión HttpOnly.
 */

const API_BASE = '/api';
const SESSION_CACHE_KEY = 'pulso_session_cache';

export async function apiFetch(path, options = {}) {
  const url = path.startsWith('http') ? path : `${API_BASE}${path.startsWith('/') ? path : `/${path}`}`;
  const { headers: optionHeaders, ...rest } = options;

  const response = await fetch(url, {
    ...rest,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...optionHeaders,
    },
  });

  const contentType = response.headers.get('content-type') ?? '';
  const isJson = contentType.includes('application/json');
  const payload = isJson ? await response.json() : await response.text();

  if (!response.ok) {
    const message =
      (isJson && typeof payload === 'object' && payload?.error) ||
      `API error: ${response.status} ${response.statusText}`;
    const error = new Error(message);
    error.status = response.status;
    error.payload = payload;
    throw error;
  }

  return payload;
}

export const configApi = {
  get: () => apiFetch('/config'),
};

/** Ambientes donde se muestran tips de desarrollo (p. ej. Mailhog). */
export function isLocalEnvironment(environment) {
  const value = String(environment ?? '').toLowerCase().trim();
  return value === 'local' || value === 'development';
}

export const usersApi = {
  list: () => apiFetch('/users'),
  get: (id) => apiFetch(`/users/${id}`),
  create: (data) =>
    apiFetch('/users', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    }),
};

export const authApi = {
  login: (data) =>
    apiFetch('/auth/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    }),
  confirmEmail: (token) =>
    apiFetch('/auth/confirm-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token }),
    }),
  resendConfirmation: (email) =>
    apiFetch('/auth/resend-confirmation', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email }),
    }),
  me: () => apiFetch('/auth/me'),
  logout: () =>
    apiFetch('/auth/logout', {
      method: 'POST',
    }),
};

/** Cache opcional del perfil; la sesión real vive en cookie de servidor. */
export function cacheSession(user) {
  sessionStorage.setItem(SESSION_CACHE_KEY, JSON.stringify(user));
}

export function getCachedSession() {
  const raw = sessionStorage.getItem(SESSION_CACHE_KEY);
  if (!raw) return null;

  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

export function clearSessionCache() {
  sessionStorage.removeItem(SESSION_CACHE_KEY);
}

/** @deprecated usar cacheSession — se mantiene por compatibilidad temporal */
export function saveSession(user) {
  cacheSession(user);
}

/** @deprecated usar getCachedSession */
export function getSession() {
  return getCachedSession();
}

/** @deprecated usar clearSessionCache */
export function clearSession() {
  clearSessionCache();
}

/** Destinos del dashboard según el rol del usuario. */
export function dashboardPathForRole(role) {
  switch (role) {
    case 'bank':
      return '/dashboard/bank/';
    case 'admin':
      return '/dashboard/admin/';
    case 'donor':
    default:
      return '/dashboard/donor/';
  }
}
