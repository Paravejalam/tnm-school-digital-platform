import { API_BASE } from '../lib/config/api';

type JSONValue = string | number | boolean | null | { [key: string]: JSONValue } | JSONValue[];

export interface ApiError {
  status: number;
  message: string;
  validation?: Record<string, string[]>;
}

let accessToken: string | null = null;

export function setAccessToken(token: string | null) {
  accessToken = token;
}

async function parseJson(resp: Response) {
  const text = await resp.text();
  try {
    return text ? JSON.parse(text) : null;
  } catch {
    return null;
  }
}

export async function apiFetch(path: string, opts: RequestInit = {}): Promise<any> {
  const url = API_BASE + path;
  const headers: Record<string, string> = { 'Content-Type': 'application/json' };
  if (accessToken) headers['Authorization'] = `Bearer ${accessToken}`;

  const res = await fetch(url, { ...opts, headers });
  const body = await parseJson(res);
  if (!res.ok) {
    const error: ApiError = {
      status: res.status,
      message: (body && body.error && body.error.message) || (body && body.message) || res.statusText,
      validation: body?.error?.validation ?? body?.validation,
    };
    throw error;
  }

  return body;
}

export default { apiFetch, setAccessToken };
