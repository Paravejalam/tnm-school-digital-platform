import api from './api';
import { listAcademicClasses } from './academicClass';
import { listAcademicSessions } from './academicSession';
import { listSections } from './section';
import type {
  StudentCreateValues,
  StudentFormValues,
  StudentListResponse,
  StudentRecord,
  StudentReferenceOptions,
  StudentStatus,
  StudentUpdateValues,
} from '../types/student';

const DEFAULT_PER_PAGE = 15;

interface JsonObject { [key: string]: unknown }

export interface StudentRequestError {
  status: number;
  message: string;
  validation?: Record<string, string[]>;
}

function isObject(value: unknown): value is JsonObject { return typeof value === 'object' && value !== null; }
function unwrapResponse(response: unknown): unknown { return isObject(response) && response.status === 'success' && 'data' in response ? response.data : response; }
function numberValue(value: unknown): number | null {
  if (typeof value === 'number' && Number.isFinite(value)) return value;
  if (typeof value === 'string' && value.trim() !== '' && Number.isFinite(Number(value))) return Number(value);
  return null;
}
function stringValue(value: unknown): string | null { return typeof value === 'string' ? value : null; }
function requiredId(value: unknown): number {
  const id = numberValue(value);
  if (id === null || !Number.isInteger(id) || id < 1) throw new Error('The student response contained an invalid identifier.');
  return id;
}

function parseStudent(value: unknown): StudentRecord {
  if (!isObject(value)) throw new Error('The student response was invalid.');
  return {
    id: requiredId(value.id),
    admission_number: stringValue(value.admission_number),
    first_name: stringValue(value.first_name),
    last_name: stringValue(value.last_name),
    email: stringValue(value.email),
    phone: stringValue(value.phone),
    class_name: stringValue(value.class_name),
    section: stringValue(value.section),
    status: stringValue(value.status),
  };
}

function parseList(response: unknown): StudentListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) throw new Error('The student list response was invalid.');
  const items = payload.items.map(parseStudent);
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

function optionalNumber(value: string): number | null { return value.trim() === '' ? null : Number(value); }

function normalizeCreatePayload(payload: StudentCreateValues): Record<string, string | number | null> {
  const result: Record<string, string | number | null> = {
    admission_number: payload.admission_number.trim(),
    first_name: payload.first_name.trim(),
    last_name: payload.last_name.trim(),
    class_name: payload.class_name.trim(),
    status: payload.status,
    email: payload.email.trim() || null,
    phone: payload.phone.trim() || null,
    section: payload.section.trim() || null,
    roll_number: payload.roll_number.trim() || null,
    date_of_birth: payload.date_of_birth || null,
    gender: payload.gender || null,
  };
  if (payload.academic_session_id.trim() !== '') result.academic_session_id = optionalNumber(payload.academic_session_id);
  if (payload.class_id.trim() !== '') result.class_id = optionalNumber(payload.class_id);
  if (payload.section_id.trim() !== '') result.section_id = optionalNumber(payload.section_id);
  return result;
}

function normalizeUpdatePayload(payload: StudentUpdateValues): Record<string, string> {
  return {
    admission_number: payload.admission_number.trim(),
    first_name: payload.first_name.trim(),
    last_name: payload.last_name.trim(),
    email: payload.email.trim(),
    phone: payload.phone.trim(),
    class_name: payload.class_name.trim(),
    section: payload.section.trim(),
    status: payload.status,
  };
}

export async function listStudents(params: Partial<Record<string, string | number>> = {}): Promise<StudentListResponse> {
  return parseList(await api.apiFetch(`/students${toQueryString({ page: 1, per_page: DEFAULT_PER_PAGE, ...params })}`));
}

export async function getStudentById(id: number): Promise<StudentRecord> {
  return parseStudent(unwrapResponse(await api.apiFetch(`/students/${id}`)));
}

export async function createStudent(payload: StudentCreateValues): Promise<StudentRecord> {
  const response: unknown = await api.apiFetch('/students', { method: 'POST', body: JSON.stringify(normalizeCreatePayload(payload)) });
  return parseStudent(unwrapResponse(response));
}

export async function updateStudent(id: number, payload: StudentUpdateValues): Promise<StudentRecord> {
  const response: unknown = await api.apiFetch(`/students/${id}`, { method: 'PUT', body: JSON.stringify(normalizeUpdatePayload(payload)) });
  return parseStudent(unwrapResponse(response));
}

export async function deleteStudent(id: number): Promise<{ message: string }> {
  const response = unwrapResponse(await api.apiFetch(`/students/${id}`, { method: 'DELETE' }));
  return isObject(response) && typeof response.message === 'string' ? { message: response.message } : { message: 'Student deleted successfully.' };
}

export async function listStudentReferences(): Promise<StudentReferenceOptions> {
  const [sessions, classes, sections] = await Promise.all([
    listAcademicSessions({ page: 1, per_page: 100 }),
    listAcademicClasses({ page: 1, per_page: 100 }),
    listSections({ page: 1, per_page: 100 }),
  ]);
  return {
    sessions: sessions.items.map((item) => ({ id: item.id, session_name: item.session_name, status: item.status })),
    classes: classes.items.map((item) => ({ id: item.id, class_name: item.class_name, academic_session_id: item.academic_session_id, status: item.status })),
    sections: sections.items.map((item) => ({ id: item.id, section_name: item.section_name, class_id: item.class_id, status: item.status })),
  };
}

export function toStudentRequestError(error: unknown): StudentRequestError {
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

export function studentErrorMessage(error: unknown, fallback: string): string {
  const parsed = toStudentRequestError(error);
  if (parsed.status === 401) return 'Your session has expired. Please sign in again.';
  if (parsed.status === 403) return 'You do not have permission to perform this student action.';
  if (parsed.status === 404) return 'The requested student could not be found.';
  if (parsed.status >= 500) return 'The server could not complete the student request. Please try again.';
  if (parsed.status === 0) return parsed.message;
  return parsed.message || fallback;
}

export function isStudentStatus(value: string): value is StudentStatus {
  return ['active', 'inactive', 'graduated', 'transferred', 'withdrawn'].includes(value);
}

export default { listStudents, getStudentById, createStudent, updateStudent, deleteStudent, listStudentReferences };
