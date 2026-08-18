/**
 * Cliente HTTP para la API REST del backend (prefijo /api).
 * Nginx hace proxy de /api hacia el contenedor backend.
 * credentials: 'include' envía/recibe la cookie de sesión HttpOnly.
 */

const API_BASE = '/api';
const SESSION_CACHE_KEY = 'pulso_session_cache';

export async function apiFetch(path, options = {}) {
  const url = path.startsWith('http')
    ? path
    : `${API_BASE}${path.startsWith('/') ? path : `/${path}`}`;

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

  const payload = isJson
    ? await response.json()
    : await response.text();

  if (!response.ok) {
    const message =
      (
        isJson
        && typeof payload === 'object'
        && payload?.error
      )
      || `API error: ${response.status} ${response.statusText}`;

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
  const value = String(environment ?? '')
    .toLowerCase()
    .trim();

  return value === 'local' || value === 'development';
}

export const usersApi = {
  list: () =>
    apiFetch('/users'),

  get: (id) =>
    apiFetch(`/users/${id}`),

  create: (data) =>
    apiFetch('/users', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  update: (id, data) =>
    apiFetch(`/users/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),
};

export const authApi = {
  login: (data) =>
    apiFetch('/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  confirmEmail: (token) =>
    apiFetch('/auth/confirm-email', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        token,
      }),
    }),

  resendConfirmation: (email) =>
    apiFetch('/auth/resend-confirmation', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        email,
      }),
    }),

  me: () =>
    apiFetch('/auth/me'),

  logout: () =>
    apiFetch('/auth/logout', {
      method: 'POST',
    }),
};

export const donorApi = {
  getProfile: () =>
    apiFetch('/donor/profile'),

  updateProfile: (data) =>
    apiFetch('/donor/profile', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  listAppointments: () =>
    apiFetch('/donor/appointments'),

  createAppointment: (data) =>
    apiFetch('/donor/appointments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  cancelAppointment: (id) =>
    apiFetch(`/donor/appointments/${id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        status: 'cancelled',
      }),
    }),

  listDonations: () =>
    apiFetch('/donor/donations'),

  listAchievements: () =>
    apiFetch('/donor/achievements'),
};

export const bankApi = {
  listAppointments: () =>
    apiFetch('/bank/appointments'),

  completeAppointment: (id) =>
    apiFetch(`/bank/appointments/${id}/complete`, {
      method: 'POST',
    }),

  getInventory: () =>
    apiFetch('/bank/inventory'),

  listInventoryMovements: (limit = 50) =>
    apiFetch(
      `/bank/inventory/movements?limit=${encodeURIComponent(
        String(limit)
      )}`
    ),

  createInventoryReceipt: (data) =>
    apiFetch('/bank/inventory/receipts', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  createInventoryAdjustment: (data) =>
    apiFetch('/bank/inventory/adjustments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  listRequests: () =>
    apiFetch('/bank/requests'),

  assignRequest: (id) =>
    apiFetch(`/bank/requests/${id}/assign`, {
      method: 'POST',
    }),

  listAlerts: (params = {}) => {
    const query = new URLSearchParams();

    if (params.status) {
      query.set('status', params.status);
    }

    const qs = query.toString();

    return apiFetch(
      `/bank/alerts${qs ? `?${qs}` : ''}`
    );
  },

  resolveAlert: (id) =>
    apiFetch(`/bank/alerts/${id}/resolve`, {
      method: 'POST',
    }),

  listCompatibleDonors: (params = {}) => {
    const query = new URLSearchParams();

    if (params.blood_type) {
      query.set('blood_type', params.blood_type);
    }

    if (
      params.eligible === false
      || params.eligible === 0
    ) {
      query.set('eligible', '0');
    }

    if (params.limit) {
      query.set('limit', String(params.limit));
    }

    const qs = query.toString();

    return apiFetch(
      `/bank/donors/compatible${qs ? `?${qs}` : ''}`
    );
  },
};

export const centersApi = {
  list: (params = {}) => {
    const query = new URLSearchParams();

    if (params.all) {
      query.set('all', '1');
    }

    const qs = query.toString();

    return apiFetch(
      `/centers${qs ? `?${qs}` : ''}`
    );
  },

  get: (id) =>
    apiFetch(`/centers/${id}`),

  create: (data) =>
    apiFetch('/centers', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  update: (id, data) =>
    apiFetch(`/centers/${id}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  updateActive: (id, active) =>
    apiFetch(`/centers/${id}/active`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        active,
      }),
    }),
};

export const notificationsApi = {
  list: (limit = 50) =>
    apiFetch(
      `/notifications?limit=${encodeURIComponent(
        String(limit)
      )}`
    ),

  markRead: (id) =>
    apiFetch(`/notifications/${id}/read`, {
      method: 'POST',
    }),
};

export const adminApi = {
  getPolicies: () =>
    apiFetch('/admin/policies'),

  updatePolicies: (data) =>
    apiFetch('/admin/policies', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    }),

  listAuditLog: (limit = 100) =>
    apiFetch(
      `/admin/audit-log?limit=${encodeURIComponent(
        String(limit)
      )}`
    ),
};

/** Cache opcional del perfil; la sesión real vive en cookie de servidor. */
export function cacheSession(user) {
  sessionStorage.setItem(
    SESSION_CACHE_KEY,
    JSON.stringify(user)
  );
}

export function getCachedSession() {
  const raw = sessionStorage.getItem(
    SESSION_CACHE_KEY
  );

  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

export function clearSessionCache() {
  sessionStorage.removeItem(
    SESSION_CACHE_KEY
  );
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