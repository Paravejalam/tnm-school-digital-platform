import api from './api';
import { listAcademicClasses } from './academicClass';
import type {
  SectionClassOption,
  SectionFormValues,
  SectionListResponse,
  SectionRecord,
  SectionStatus,
  SectionUpdateValues,
} from '../types/section';

const DEFAULT_PER_PAGE = 15;

interface JsonObject {
  [key: string]: unknown;
}

export interface SectionRequestError {
  status: number;
  message: string;
  validation?: Record<string, string[]>;
}

function isObject(value: unknown): value is JsonObject { return typeof value === 'object' && value !== null; }
function unwrapResponse(response: unknown): unknown { return isObject(response) && response.status === 'success' && 'data' in response ? response.data : response; }
function numberValue(value: unknown): number | null { if (typeof value === 'number' && Number.isFinite(value)) return value; if (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value))) return Number(value); return null; }
function stringValue(value: unknown): string | null { return typeof value === 'string' ? value : null; }
function requiredId(value: unknown): number { const id = numberValue(value); if (id === null || !Number.isInteger(id) || id < 1) throw new Error('The section response contained an invalid identifier.'); return id; }

function parseSection(value: unknown): SectionRecord {
  if (!isObject(value)) throw new Error('The section response was invalid.');
  return { id: requiredId(value.id), section_name: stringValue(value.section_name), code: stringValue(value.code), class_id: numberValue(value.class_id), status: stringValue(value.status), capacity: numberValue(value.capacity) };
}

function parseList(response: unknown): SectionListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) throw new Error('The section list response was invalid.');
  const items = payload.items.map(parseSection);
  const pagination = isObject(payload.pagination) ? payload.pagination : {};
  return { items, pagination: { total: numberValue(pagination.total) ?? items.length, page: numberValue(pagination.page) ?? 1, per_page: numberValue(pagination.per_page) ?? DEFAULT_PER_PAGE } };
}

function toQueryString(params: Record<string, string | number | undefined>): string {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => { if (value === undefined || (typeof value === 'string' && value === '')) return; query.set(key, String(value)); });
  const result = query.toString(); return result ? `?${result}` : '';
}

function normalizeCreatePayload(payload: SectionFormValues): Record<string, string | number | null> {
  return { section_name: payload.section_name.trim(), class_id: Number(payload.class_id), capacity: payload.capacity.trim() === '' ? null : Number(payload.capacity), status: payload.status };
}

function normalizeUpdatePayload(payload: SectionUpdateValues): Record<string, string | number> {
  return { section_name: payload.section_name.trim(), class_id: Number(payload.class_id), status: payload.status };
}

export async function listSections(params: Partial<Record<string, string | number>> = {}): Promise<SectionListResponse> { const query = toQueryString({ page: 1, per_page: DEFAULT_PER_PAGE, ...params }); return parseList(await api.apiFetch(`/sections${query}`)); }
export async function getSectionById(id: number): Promise<SectionRecord> { return parseSection(unwrapResponse(await api.apiFetch(`/sections/${id}`))); }
export async function createSection(payload: SectionFormValues): Promise<SectionRecord> { const response: unknown = await api.apiFetch('/sections', { method: 'POST', body: JSON.stringify(normalizeCreatePayload(payload)) }); return parseSection(unwrapResponse(response)); }
export async function updateSection(id: number, payload: SectionUpdateValues): Promise<SectionRecord> { const response: unknown = await api.apiFetch(`/sections/${id}`, { method: 'PUT', body: JSON.stringify(normalizeUpdatePayload(payload)) }); return parseSection(unwrapResponse(response)); }
export async function deleteSection(id: number): Promise<{ message: string }> { const response = unwrapResponse(await api.apiFetch(`/sections/${id}`, { method: 'DELETE' })); if (!isObject(response) || typeof response.message !== 'string') return { message: 'Section deleted successfully.' }; return { message: response.message }; }
export async function listSectionClasses(): Promise<SectionClassOption[]> { const result = await listAcademicClasses({ page: 1, per_page: 100 }); return result.items.map((item) => ({ id: item.id, class_name: item.class_name, academic_session_id: item.academic_session_id, status: item.status })); }

export function toSectionRequestError(error: unknown): SectionRequestError {
  if (!isObject(error)) return { status: 0, message: 'A network error prevented the request from completing.' };
  const validation = isObject(error.validation) ? Object.fromEntries(Object.entries(error.validation).filter((entry): entry is [string, string[]] => Array.isArray(entry[1]) && entry[1].every((message): message is string => typeof message === 'string'))) : undefined;
  return { status: typeof error.status === 'number' ? error.status : 0, message: typeof error.message === 'string' ? error.message : 'The request could not be completed.', validation };
}
export function sectionErrorMessage(error: unknown, fallback: string): string { const parsed = toSectionRequestError(error); if (parsed.status === 401) return 'Your session has expired. Please sign in again.'; if (parsed.status === 403) return 'You do not have permission to perform this section action.'; if (parsed.status === 404) return 'The requested section could not be found.'; if (parsed.status >= 500) return 'The server could not complete the section request. Please try again.'; if (parsed.status === 0) return parsed.message; return parsed.message || fallback; }
export function isSectionStatus(value: string): value is SectionStatus { return value === 'active' || value === 'inactive'; }

export default { listSections, getSectionById, createSection, updateSection, deleteSection, listSectionClasses };
