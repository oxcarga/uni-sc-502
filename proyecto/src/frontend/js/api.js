/**
 * Cliente HTTP para la API REST del backend (prefijo /api).
 * Nginx hace proxy de /api hacia el contenedor backend.
 */

const API_BASE = '/api';
const SESSION_KEY = 'pulso_sesion';

export async function apiFetch(path, options = {}) {
  const url = path.startsWith('http') ? path : `${API_BASE}${path.startsWith('/') ? path : `/${path}`}`;

  const response = await fetch(url, {
    headers: {
      Accept: 'application/json',
      ...options.headers,
    },
    ...options,
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
};

export function saveSession(user) {
  sessionStorage.setItem(SESSION_KEY, JSON.stringify(user));
}

export function getSession() {
  const raw = sessionStorage.getItem(SESSION_KEY);
  if (!raw) return null;

  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

export function clearSession() {
  sessionStorage.removeItem(SESSION_KEY);
}

/** Destinos placeholder hasta que existan los paneles reales. */
export function panelPathForRole(rol) {
  switch (rol) {
    case 'banco':
      return '/panel/banco/';
    case 'admin':
      return '/panel/admin/';
    case 'donante':
    default:
      return '/panel/donante/';
  }
}
