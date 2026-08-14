import api from './api';
import type {
  HolidayCalendarCreateValues,
  HolidayCalendarListResponse,
  HolidayCalendarRecord,
  HolidayCalendarSessionOption,
  HolidayCalendarUpdateValues,
} from '../types/holidayCalendar';

const DEFAULT_PER_PAGE = 15;

interface JsonObject {
  [key: string]: unknown;
}

export interface HolidayCalendarRequestError {
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

function requiredId(value: unknown, resource: string): number {
  const id = numberValue(value);
  if (id === null || !Number.isInteger(id) || id < 1) {
    throw new Error(`The ${resource} response contained an invalid identifier.`);
  }
  return id;
}

function parseHolidayCalendar(value: unknown): HolidayCalendarRecord {
  if (!isObject(value)) throw new Error('The holiday calendar response was invalid.');

  return {
    id: requiredId(value.id, 'holiday calendar'),
    holiday_name: stringValue(value.holiday_name),
    academic_session_id: numberValue(value.academic_session_id),
    status: stringValue(value.status),
  };
}

function parseList(response: unknown): HolidayCalendarListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) {
    throw new Error('The holiday calendar list response was invalid.');
  }

  const items = payload.items.map(parseHolidayCalendar);
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

function parseSessionOptions(response: unknown): HolidayCalendarSessionOption[] {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) {
    throw new Error('The academic session lookup response was invalid.');
  }

  return payload.items.filter(isObject).map((item) => ({
    id: requiredId(item.id, 'academic session'),
    session_name: stringValue(item.session_name),
    status: stringValue(item.status),
  }));
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

function normalizeCreatePayload(payload: HolidayCalendarCreateValues): Record<string, string | number> {
  return {
    holiday_name: payload.holiday_name.trim(),
    academic_session_id: Number(payload.academic_session_id),
    holiday_date: payload.holiday_date,
    holiday_type: payload.holiday_type,
    is_recurring: payload.is_recurring ? 1 : 0,
    description: payload.description.trim(),
    status: payload.status,
  };
}

function normalizeUpdatePayload(payload: HolidayCalendarUpdateValues): Record<string, string | number> {
  return {
    holiday_name: payload.holiday_name.trim(),
    academic_session_id: Number(payload.academic_session_id),
    status: payload.status,
  };
}

export async function listHolidayCalendars(params: Partial<Record<string, string | number>> = {}): Promise<HolidayCalendarListResponse> {
  const query = toQueryString({ page: 1, per_page: DEFAULT_PER_PAGE, ...params });
  return parseList(await api.apiFetch(`/holiday-calendars${query}`));
}

export async function getHolidayCalendarById(id: number): Promise<HolidayCalendarRecord> {
  return parseHolidayCalendar(unwrapResponse(await api.apiFetch(`/holiday-calendars/${id}`)));
}

export async function createHolidayCalendar(payload: HolidayCalendarCreateValues): Promise<HolidayCalendarRecord> {
  const response: unknown = await api.apiFetch('/holiday-calendars', {
    method: 'POST',
    body: JSON.stringify(normalizeCreatePayload(payload)),
  });
  return parseHolidayCalendar(unwrapResponse(response));
}

export async function updateHolidayCalendar(id: number, payload: HolidayCalendarUpdateValues): Promise<HolidayCalendarRecord> {
  const response: unknown = await api.apiFetch(`/holiday-calendars/${id}`, {
    method: 'PUT',
    body: JSON.stringify(normalizeUpdatePayload(payload)),
  });
  return parseHolidayCalendar(unwrapResponse(response));
}

export async function deleteHolidayCalendar(id: number): Promise<{ message: string }> {
  const response: unknown = unwrapResponse(await api.apiFetch(`/holiday-calendars/${id}`, { method: 'DELETE' }));
  if (!isObject(response) || typeof response.message !== 'string') {
    return { message: 'Holiday calendar deleted successfully.' };
  }
  return { message: response.message };
}

export async function listHolidayCalendarSessions(): Promise<HolidayCalendarSessionOption[]> {
  return parseSessionOptions(await api.apiFetch('/academic-sessions?page=1&per_page=100'));
}

export function toHolidayCalendarRequestError(error: unknown): HolidayCalendarRequestError {
  if (!isObject(error)) {
    return { status: 0, message: 'A network error prevented the request from completing.' };
  }

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

export function holidayCalendarErrorMessage(error: unknown, fallback: string): string {
  const parsed = toHolidayCalendarRequestError(error);
  if (parsed.status === 401) return 'Your session has expired. Please sign in again.';
  if (parsed.status === 403) return 'You do not have permission to perform this action.';
  if (parsed.status === 404) return 'The requested holiday calendar could not be found.';
  if (parsed.status >= 500) return 'The server could not complete the request. Please try again.';
  if (parsed.status === 0) return parsed.message;
  return parsed.message || fallback;
}

export default {
  listHolidayCalendars,
  getHolidayCalendarById,
  createHolidayCalendar,
  updateHolidayCalendar,
  deleteHolidayCalendar,
  listHolidayCalendarSessions,
};
