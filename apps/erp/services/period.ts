import api from './api';
import type { PeriodCreateValues, PeriodListResponse, PeriodRecord, PeriodUpdateValues } from '../types/period';

const DEFAULT_PER_PAGE = 15;

interface JsonObject {
  [key: string]: unknown;
}

export interface PeriodRequestError {
  status: number;
  message: string;
  validation?: Record<string, string[]>;
}

function isObject(value: unknown): value is JsonObject {
  return typeof value === 'object' && value !== null;
}

function unwrapResponse(response: unknown): unknown {
  if (isObject(response) && response.status === 'success' && 'data' in response) {
    return response.data;
  }

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
  if (id === null || !Number.isInteger(id) || id < 1) throw new Error('The period response contained an invalid identifier.');
  return id;
}

function parsePeriod(value: unknown): PeriodRecord {
  if (!isObject(value)) throw new Error('The period response was invalid.');
  return {
    id: requiredId(value.id),
    period_name: stringValue(value.period_name),
    timetable_id: numberValue(value.timetable_id),
    status: stringValue(value.status),
  };
}

function parseList(response: unknown): PeriodListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) throw new Error('The period list response was invalid.');
  const items = payload.items.map(parsePeriod);
  const pagination = isObject(payload.pagination) ? payload.pagination : {};
  return {
    items,
    pagination: {
      total: numberValue(pagination.total) ?? items.length,
      page: numberValue(pagination.page) ?? 1,
      per_page: numberValue(pagination.per_page) ?? items.length,
    },
  };
}

function toQueryString(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    search.set(key, String(value));
  });

  const query = search.toString();
  return query ? `?${query}` : '';
}

function normalizeCreatePayload(payload: PeriodCreateValues): Record<string, string | number | null> {
  return {
    period_name: payload.period_name.trim(),
    timetable_id: Number(payload.timetable_id),
    day_of_week: payload.day_of_week,
    start_time: payload.start_time,
    end_time: payload.end_time,
    period_order: payload.period_order.trim() === '' ? null : Number(payload.period_order),
    status: payload.status,
  };
}

function normalizeUpdatePayload(payload: PeriodUpdateValues): Record<string, string | number> {
  return {
    period_name: payload.period_name.trim(),
    timetable_id: Number(payload.timetable_id),
    status: payload.status,
  };
}

export async function listPeriods(params: Partial<Record<string, string | number>> = {}): Promise<PeriodListResponse> {
  const query = toQueryString({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
    ...params,
  });

  return parseList(await api.apiFetch(`/periods${query}`));
}

export async function getPeriodById(id: number): Promise<PeriodRecord> {
  return parsePeriod(unwrapResponse(await api.apiFetch(`/periods/${id}`)));
}

export async function createPeriod(payload: PeriodCreateValues): Promise<PeriodRecord> {
  const result: unknown = await api.apiFetch('/periods', {
    method: 'POST',
    body: JSON.stringify(normalizeCreatePayload(payload)),
  });

  return parsePeriod(unwrapResponse(result));
}

export async function updatePeriod(id: number, payload: PeriodUpdateValues): Promise<PeriodRecord> {
  const result: unknown = await api.apiFetch(`/periods/${id}`, {
    method: 'PUT',
    body: JSON.stringify(normalizeUpdatePayload(payload)),
  });

  return parsePeriod(unwrapResponse(result));
}

export async function deletePeriod(id: number): Promise<{ message: string }> {
  const response: unknown = unwrapResponse(await api.apiFetch(`/periods/${id}`, { method: 'DELETE' }));
  if (!isObject(response) || typeof response.message !== 'string') return { message: 'Period deleted successfully.' };
  return { message: response.message };
}

export function toPeriodRequestError(error: unknown): PeriodRequestError {
  if (!isObject(error)) return { status: 0, message: 'A network error prevented the request from completing.' };

  const validation = isObject(error.validation)
    ? Object.fromEntries(
        Object.entries(error.validation).filter((entry): entry is [string, string[]] =>
          Array.isArray(entry[1]) && entry[1].every((message): message is string => typeof message === 'string'),
        ),
      )
    : undefined;

  return {
    status: typeof error.status === 'number' ? error.status : 0,
    message: typeof error.message === 'string' ? error.message : 'The request could not be completed.',
    validation,
  };
}

export function periodErrorMessage(error: unknown, fallback: string): string {
  const parsed = toPeriodRequestError(error);
  if (parsed.status === 401) return 'Your session has expired. Please sign in again.';
  if (parsed.status === 403) return 'You do not have permission to perform this action.';
  if (parsed.status === 404) return 'The requested period could not be found.';
  if (parsed.status >= 500) return 'The server could not complete the request. Please try again.';
  if (parsed.status === 0) return parsed.message;
  return parsed.message || fallback;
}

export default {
  listPeriods,
  getPeriodById,
  createPeriod,
  updatePeriod,
  deletePeriod,
};
