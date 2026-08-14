import api, { setAccessToken } from './api';
import type { AuthResponse, UserProfile } from '../types/auth';

const REFRESH_COOKIE = 'tnm_refresh_token';

function setRefreshCookie(token: string) {
  // secure by default in production when running on https
  const secure = location.protocol === 'https:' ? '; Secure; SameSite=Strict' : '; SameSite=Strict';
  document.cookie = `${REFRESH_COOKIE}=${encodeURIComponent(token)}; Path=/;` + secure;
}

function getRefreshCookie(): string | null {
  const m = document.cookie.match(new RegExp('(^| )' + REFRESH_COOKIE + '=([^;]+)'));
  return m ? decodeURIComponent(m[2]) : null;
}

function clearRefreshCookie() {
  document.cookie = `${REFRESH_COOKIE}=; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT;`;
}

export async function login(email: string, password: string): Promise<UserProfile | null> {
  const body = await api.apiFetch('/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) });
  const data = body as AuthResponse;
  if (data?.token) {
    setAccessToken(data.token);
  }
  if (data?.refresh_token) {
    setRefreshCookie(data.refresh_token);
  }
  return data.user ?? null;
}

export async function refresh(): Promise<UserProfile | null> {
  const refreshToken = getRefreshCookie();
  if (!refreshToken) throw { status: 401, message: 'No refresh token' };

  const body = await api.apiFetch('/auth/refresh', { method: 'POST', body: JSON.stringify({ refresh_token: refreshToken }) });
  const data = body as AuthResponse;
  if (data?.token) setAccessToken(data.token);
  if (data?.refresh_token) setRefreshCookie(data.refresh_token);
  return data.user ?? null;
}

export async function logout(): Promise<void> {
  try {
    // attempt server logout with current Authorization header
    await api.apiFetch('/auth/logout', { method: 'POST', body: JSON.stringify({}) });
  } catch (e) {
    // ignore errors — still clear client state
  }
  setAccessToken(null);
  clearRefreshCookie();
}

export async function profile(): Promise<UserProfile | null> {
  const body = await api.apiFetch('/auth/profile', { method: 'GET' });
  return body?.user ?? null;
}

export default { login, refresh, logout, profile };
