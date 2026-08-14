import api from './api';
import type {
  TimetableAcademicSessionOption,
  TimetableClassOption,
  TimetableFormValues,
  TimetableListResponse,
  TimetableRecord,
  TimetableReferenceData,
  TimetableSectionOption,
  TimetableSubjectOption,
  TimetableTeacherOption,
} from '../types/timetable';

const DEFAULT_PER_PAGE = 15;

function toQueryString(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    search.set(key, String(value));
  });

  const query = search.toString();
  return query ? `?${query}` : '';
}

interface JsonObject {
  [key: string]: unknown;
}

export interface TimetableRequestError {
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

function parseTimetableRecord(value: unknown): TimetableRecord {
  if (!isObject(value)) throw new Error('The timetable response was invalid.');

  return {
    id: requiredId(value.id, 'timetable'),
    timetable_name: stringValue(value.timetable_name),
    academic_session_id: numberValue(value.academic_session_id),
    class_id: numberValue(value.class_id),
    section_id: numberValue(value.section_id),
    subject_id: numberValue(value.subject_id),
    teacher_id: numberValue(value.teacher_id),
    status: stringValue(value.status),
  };
}

function parsePagination(value: unknown, itemCount: number): TimetableListResponse['pagination'] {
  if (!isObject(value)) {
    return { total: itemCount, page: 1, per_page: itemCount };
  }

  return {
    total: numberValue(value.total) ?? itemCount,
    page: numberValue(value.page) ?? 1,
    per_page: numberValue(value.per_page) ?? itemCount,
  };
}

function parseTimetableList(response: unknown): TimetableListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) {
    throw new Error('The timetable list response was invalid.');
  }

  return {
    items: payload.items.map(parseTimetableRecord),
    pagination: parsePagination(payload.pagination, payload.items.length),
  };
}

function resourceItems(response: unknown): JsonObject[] {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) {
    throw new Error('A timetable lookup response was invalid.');
  }

  return payload.items.filter(isObject);
}

function parseAcademicSessions(response: unknown): TimetableAcademicSessionOption[] {
  return resourceItems(response).map((item) => ({
    id: requiredId(item.id, 'academic session'),
    session_name: stringValue(item.session_name),
    status: stringValue(item.status),
  }));
}

function parseClasses(response: unknown): TimetableClassOption[] {
  return resourceItems(response).map((item) => ({
    id: requiredId(item.id, 'class'),
    class_name: stringValue(item.class_name),
    code: stringValue(item.code),
    academic_session_id: numberValue(item.academic_session_id),
    status: stringValue(item.status),
  }));
}

function parseSections(response: unknown): TimetableSectionOption[] {
  return resourceItems(response).map((item) => ({
    id: requiredId(item.id, 'section'),
    section_name: stringValue(item.section_name),
    code: stringValue(item.code),
    class_id: numberValue(item.class_id),
    status: stringValue(item.status),
  }));
}

function parseSubjects(response: unknown): TimetableSubjectOption[] {
  return resourceItems(response).map((item) => ({
    id: requiredId(item.id, 'subject'),
    subject_name: stringValue(item.subject_name),
    code: stringValue(item.code),
    section_id: numberValue(item.section_id),
    status: stringValue(item.status),
  }));
}

function parseTeachers(response: unknown): TimetableTeacherOption[] {
  return resourceItems(response).map((item) => ({
    id: requiredId(item.id, 'teacher'),
    employee_id: stringValue(item.employee_id),
    first_name: stringValue(item.first_name),
    last_name: stringValue(item.last_name),
    status: stringValue(item.status),
  }));
}

function normalizePayload(payload: TimetableFormValues): Record<string, string | number> {
  return {
    timetable_name: payload.timetable_name.trim(),
    academic_session_id: Number(payload.academic_session_id),
    class_id: Number(payload.class_id),
    section_id: Number(payload.section_id),
    subject_id: Number(payload.subject_id),
    teacher_id: Number(payload.teacher_id),
    status: payload.status,
  };
}

export async function listTimetables(params: Partial<Record<string, string | number>> = {}): Promise<TimetableListResponse> {
  const query = toQueryString({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
    ...params,
  });

  return parseTimetableList(await api.apiFetch(`/timetables${query}`));
}

export async function getTimetableById(id: number): Promise<TimetableRecord> {
  return parseTimetableRecord(unwrapResponse(await api.apiFetch(`/timetables/${id}`)));
}

export async function createTimetable(payload: TimetableFormValues): Promise<TimetableRecord> {
  const result: unknown = await api.apiFetch('/timetables', {
    method: 'POST',
    body: JSON.stringify(normalizePayload(payload)),
  });

  return parseTimetableRecord(unwrapResponse(result));
}

export async function updateTimetable(id: number, payload: TimetableFormValues): Promise<TimetableRecord> {
  const result: unknown = await api.apiFetch(`/timetables/${id}`, {
    method: 'PUT',
    body: JSON.stringify(normalizePayload(payload)),
  });

  return parseTimetableRecord(unwrapResponse(result));
}

export async function deleteTimetable(id: number): Promise<{ message: string }> {
  const response: unknown = unwrapResponse(await api.apiFetch(`/timetables/${id}`, { method: 'DELETE' }));
  if (!isObject(response) || typeof response.message !== 'string') {
    return { message: 'Timetable deleted successfully.' };
  }
  return { message: response.message };
}

export async function listTimetableReferences(): Promise<TimetableReferenceData> {
  const query = '?page=1&per_page=100';
  const [sessions, classes, sections, subjects, teachers] = await Promise.all([
    api.apiFetch(`/academic-sessions${query}`),
    api.apiFetch(`/classes${query}`),
    api.apiFetch(`/sections${query}`),
    api.apiFetch(`/subjects${query}`),
    api.apiFetch(`/teachers${query}`),
  ]);

  return {
    academicSessions: parseAcademicSessions(sessions),
    classes: parseClasses(classes),
    sections: parseSections(sections),
    subjects: parseSubjects(subjects),
    teachers: parseTeachers(teachers),
  };
}

export function toTimetableRequestError(error: unknown): TimetableRequestError {
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

export function timetableErrorMessage(error: unknown, fallback: string): string {
  const parsed = toTimetableRequestError(error);
  if (parsed.status === 401) return 'Your session has expired. Please sign in again.';
  if (parsed.status === 403) return 'You do not have permission to perform this action.';
  if (parsed.status === 404) return 'The requested timetable could not be found.';
  if (parsed.status >= 500) return 'The server could not complete the request. Please try again.';
  if (parsed.status === 0) return parsed.message;
  return parsed.message || fallback;
}

export default {
  listTimetables,
  getTimetableById,
  createTimetable,
  updateTimetable,
  deleteTimetable,
  listTimetableReferences,
};
