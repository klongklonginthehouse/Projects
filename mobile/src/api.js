import * as SecureStore from 'expo-secure-store';

// Android emulator reaches your computer at 10.0.2.2. On a real phone, use your
// computer's LAN IP (e.g. http://192.168.1.10:8000/api) and run: php artisan serve --host=0.0.0.0
export const API_URL = 'http://192.168.254.119:8000/api';

const KEY = 'iskolarnotes_token';
let token = null;

export const getToken = () => token;

export async function loadToken() {
  token = await SecureStore.getItemAsync(KEY);
  return token;
}

export async function saveToken(t) {
  token = t;
  if (t) await SecureStore.setItemAsync(KEY, t);
  else await SecureStore.deleteItemAsync(KEY);
}

export async function api(path, { method = 'GET', body, form } = {}) {
  const headers = { Accept: 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  let payload;
  if (form) {
    payload = form; // multipart: fetch sets the boundary itself
  } else if (body) {
    headers['Content-Type'] = 'application/json';
    payload = JSON.stringify(body);
  }

  let res;
  try {
    res = await fetch(`${API_URL}${path}`, { method, headers, body: payload });
  } catch (e) {
    throw new Error('Cannot reach the server. Check your connection and the API address.');
  }

  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const first = data.errors ? Object.values(data.errors)[0][0] : null;
    const err = new Error(first || data.message || `Request failed (${res.status})`);
    err.status = res.status;
    throw err;
  }
  return data;
}
