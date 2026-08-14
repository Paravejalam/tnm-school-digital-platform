import api from './api';
import type {
  AcademicSessionCreateValues,
  AcademicSessionListResponse,
  AcademicSessionRecord,
  AcademicSessionStatus,
  AcademicSessionUpdateValues,
} from '../types/academicSession';

const DEFAULT_PER_PAGE = 15;

interface JsonObject {
  [key: string]: unknown;
}

export interface AcademicSessionRequestError {
  status: number;
  message: string;
  validation?: Record<string, string[]>;
}

function isObject(value: unknown): value is JsonObject {
  return typeof value === 'object' && value !== null;
}

function unwrapResponse(response: unknown): unknown {
  if (isObject(response) && response.status === 'success' && 'data' in response) return response.data;
  return response;
}

function numberValue(value: unknown): number | null {
  if (typeof value === 'number' && Number.isFinite(value)) return value;
  if (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value))) return Number(value);
  return null;
}

function stringValue(value: unknown): string | null {
  return typeof value === 'string' ? value : null;
}

function requiredId(value: unknown): number {
  const id = numberValue(value);
  if (id === null || !Number.isInteger(id) || id < 1) throw new Error('The academic session response contained an invalid identifier.');
  return id;
}

function parseAcademicSession(value: unknown): AcademicSessionRecord {
  if (!isObject(value)) throw new Error('The academic session response was invalid.');
  return {
    id: requiredId(value.id),
    session_name: stringValue(value.session_name),
    status: stringValue(value.status),
  };
}

function parseList(response: unknown): AcademicSessionListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) throw new Error('The academic session list response was invalid.');

  const items = payload.items.map(parseAcademicSession);
  const pagination = isObject(payload.pagination) ? payload.pagination : {};
  return {
    items,
    pagination: {
      total: numberValue(pagination.total) ?? items.length,
      page: numberValue(pagination.page) ?? 1,
      per_page: numberValue(pagination.per_page) ?? DEFAULT_PER_PAGE,
    },
  };
}

function toQueryString(params: Record<string, string | number | undefined>): string {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === '') return;
    query.set(key, String(value));
  });
  const result = query.toString();
  return result ? `?${result}` : '';
}

function normalizeCreatePayload(payload: AcademicSessionCreateValues): Record<string, string | number> {
  return {
    session_name: payload.session_name.trim(),
    start_date: payload.start_date,
    end_date: payload.end_date,
    status: payload.status,
    is_current: payload.is_current ? 1 : 0,
  };
}

function normalizeUpdatePayload(payload: AcademicSessionUpdateValues): Record<string, string> {
  return {
    session_name: payload.session_name.trim(),
    status: payload.status,
  };
}

export async function listAcademicSessions(params: Partial<Record<string, string | number>> = {}): Promise<AcademicSessionListResponse> {
  const query = toQueryString({ page: 1, per_page: DEFAULT_PER_PAGE, ...params });
  return parseList(await api.apiFetch(`/academic-sessions${query}`));
}

export async function getAcademicSessionById(id: number): Promise<AcademicSessionRecord> {
  return parseAcademicSession(unwrapResponse(await api.apiFetch(`/academic-sessions/${id}`)));
}

export async function createAcademicSession(payload: AcademicSessionCreateValues): Promise<AcademicSessionRecord> {
  const response: unknown = await api.apiFetch('/academic-sessions', {
    method: 'POST',
    body: JSON.stringify(normalizeCreatePayload(payload)),
  });
  return parseAcademicSession(unwrapResponse(response));
}

export async function updateAcademicSession(id: number, payload: AcademicSessionUpdateValues): Promise<AcademicSessionRecord> {
  const response: unknown = await api.apiFetch(`/academic-sessions/${id}`, {
    method: 'PUT',
    body: JSON.stringify(normalizeUpdatePayload(payload)),
  });
  return parseAcademicSession(unwrapResponse(response));
}

export async function deleteAcademicSession(id: number): Promise<{ message: string }> {
  const response = unwrapResponse(await api.apiFetch(`/academic-sessions/${id}`, { method: 'DELETE' }));
  if (!isObject(response) || typeof response.message !== 'string') return { message: 'Academic session deleted successfully.' };
  return { message: response.message };
}

export function toAcademicSessionRequestError(error: unknown): AcademicSessionRequestError {
  if (!isObject(error)) return { status: 0, message: 'A network error prevented the request from completing.' };

  const validation = isObject(error.validation)
    ? Object.fromEntries(Object.entries(error.validation).filter((entry): entry is [string, string[]] => Array.isArray(entry[1]) && entry[1].every((message): message is string => typeof message === 'string')))
    : undefined;

  return {
    status: typeof error.status === 'number' ? error.status : 0,
    message: typeof error.message === 'string' ? error.message : 'The request could not be completed.',
    validation,
  };
}

export function academicSessionErrorMessage(error: unknown, fallback: string): string {
  const parsed = toAcademicSessionRequestError(error);
  if (parsed.status === 401) return 'Your session has expired. Please sign in again.';
  if (parsed.status === 403) return 'You do not have permission to perform this action.';
  if (parsed.status === 404) return 'The requested academic session could not be found.';
  if (parsed.status >= 500) return 'The server could not complete the request. Please try again.';
  if (parsed.status === 0) return parsed.message;
  return parsed.message || fallback;
}

export function isAcademicSessionStatus(value: string): value is AcademicSessionStatus {
  return value === 'active' || value === 'inactive' || value === 'archived';
}

export default {
  listAcademicSessions,
  getAcademicSessionById,
  createAcademicSession,
  updateAcademicSession,
  deleteAcademicSession,
};
