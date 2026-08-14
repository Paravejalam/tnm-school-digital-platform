import api from './api';
import { listAcademicSessions } from './academicSession';
import type { AcademicClassRecord } from '../types/academicClass';
import type {
  AttendanceFormValues,
  AttendanceListResponse,
  AttendanceRecord,
  AttendanceReferenceOptions,
  AttendanceStatus,
  AttendanceUpdateValues,
} from '../types/attendance';
import type { SectionRecord } from '../types/section';
import type { StudentRecord } from '../types/student';

const DEFAULT_PER_PAGE = 15;
const REFERENCE_PER_PAGE = 100;

interface JsonObject {
  [key: string]: unknown;
}

export interface AttendanceRequestError {
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

function requiredId(value: unknown, resource: string): number {
  const id = numberValue(value);
  if (id === null || !Number.isInteger(id) || id < 1) throw new Error(`The ${resource} response contained an invalid identifier.`);
  return id;
}

function parseAttendance(value: unknown): AttendanceRecord {
  if (!isObject(value)) throw new Error('The attendance response was invalid.');

  return {
    id: requiredId(value.id, 'attendance'),
    attendance_date: stringValue(value.attendance_date),
    academic_session_id: numberValue(value.academic_session_id),
    class_id: numberValue(value.class_id),
    section_id: numberValue(value.section_id),
    student_id: numberValue(value.student_id),
    status: stringValue(value.status),
    remarks: stringValue(value.remarks),
    marked_by: numberValue(value.marked_by),
  };
}

function parseList(response: unknown): AttendanceListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) throw new Error('The attendance list response was invalid.');

  const items = payload.items.map(parseAttendance);
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

function parseItems(response: unknown, resource: string): JsonObject[] {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) throw new Error(`The ${resource} lookup response was invalid.`);
  return payload.items.filter(isObject);
}

function parseAcademicClass(value: JsonObject): AcademicClassRecord {
  return {
    id: requiredId(value.id, 'academic class'),
    class_name: stringValue(value.class_name),
    code: stringValue(value.code),
    academic_session_id: numberValue(value.academic_session_id),
    status: stringValue(value.status),
  };
}

function parseSection(value: JsonObject): SectionRecord {
  return {
    id: requiredId(value.id, 'section'),
    section_name: stringValue(value.section_name),
    code: stringValue(value.code),
    class_id: numberValue(value.class_id),
    status: stringValue(value.status),
  };
}

function parseStudent(value: JsonObject): StudentRecord {
  return {
    id: requiredId(value.id, 'student'),
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

function toQueryString(params: Record<string, string | number | undefined>): string {
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === '') return;
    query.set(key, String(value));
  });
  const result = query.toString();
  return result ? `?${result}` : '';
}

function normalizeCreatePayload(payload: AttendanceFormValues): Record<string, string | number | null> {
  return {
    attendance_date: payload.attendance_date,
    academic_session_id: Number(payload.academic_session_id),
    class_id: Number(payload.class_id),
    section_id: Number(payload.section_id),
    student_id: Number(payload.student_id),
    status: payload.status,
    remarks: payload.remarks.trim() === '' ? null : payload.remarks.trim(),
    marked_by: payload.marked_by.trim() === '' ? null : Number(payload.marked_by),
  };
}

function normalizeUpdatePayload(payload: AttendanceUpdateValues): Record<string, string | number> {
  return {
    attendance_date: payload.attendance_date,
    academic_session_id: Number(payload.academic_session_id),
    class_id: Number(payload.class_id),
    section_id: Number(payload.section_id),
    student_id: Number(payload.student_id),
    status: payload.status,
  };
}

export async function listAttendances(params: Partial<Record<string, string | number>> = {}): Promise<AttendanceListResponse> {
  const query = toQueryString({ page: 1, per_page: DEFAULT_PER_PAGE, ...params });
  return parseList(await api.apiFetch(`/attendance${query}`));
}

export async function getAttendanceById(id: number): Promise<AttendanceRecord> {
  return parseAttendance(unwrapResponse(await api.apiFetch(`/attendance/${id}`)));
}

export async function createAttendance(payload: AttendanceFormValues): Promise<AttendanceRecord> {
  const response: unknown = await api.apiFetch('/attendance', {
    method: 'POST',
    body: JSON.stringify(normalizeCreatePayload(payload)),
  });
  return parseAttendance(unwrapResponse(response));
}

export async function updateAttendance(id: number, payload: AttendanceUpdateValues): Promise<AttendanceRecord> {
  const response: unknown = await api.apiFetch(`/attendance/${id}`, {
    method: 'PUT',
    body: JSON.stringify(normalizeUpdatePayload(payload)),
  });
  return parseAttendance(unwrapResponse(response));
}

export async function deleteAttendance(id: number): Promise<{ message: string }> {
  const response = unwrapResponse(await api.apiFetch(`/attendance/${id}`, { method: 'DELETE' }));
  if (!isObject(response) || typeof response.message !== 'string') return { message: 'Attendance deleted successfully.' };
  return { message: response.message };
}

export async function listAttendanceReferences(): Promise<AttendanceReferenceOptions> {
  const [sessions, classesResponse, sectionsResponse, studentsResponse] = await Promise.all([
    listAcademicSessions({ page: 1, per_page: REFERENCE_PER_PAGE }),
    api.apiFetch(`/classes?page=1&per_page=${REFERENCE_PER_PAGE}`),
    api.apiFetch(`/sections?page=1&per_page=${REFERENCE_PER_PAGE}`),
    api.apiFetch(`/students?page=1&per_page=${REFERENCE_PER_PAGE}`),
  ]);

  return {
    sessions: sessions.items,
    classes: parseItems(classesResponse, 'academic class').map(parseAcademicClass),
    sections: parseItems(sectionsResponse, 'section').map(parseSection),
    students: parseItems(studentsResponse, 'student').map(parseStudent),
  };
}

export function toAttendanceRequestError(error: unknown): AttendanceRequestError {
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

export function attendanceErrorMessage(error: unknown, fallback: string): string {
  const parsed = toAttendanceRequestError(error);
  if (parsed.status === 401) return 'Your session has expired. Please sign in again.';
  if (parsed.status === 403) return 'You do not have permission to perform this attendance action.';
  if (parsed.status === 404) return 'The requested attendance record could not be found.';
  if (parsed.status >= 500) return 'The server could not complete the attendance request. Please try again.';
  if (parsed.status === 0) return parsed.message;
  return parsed.message || fallback;
}

export function isAttendanceStatus(value: string): value is AttendanceStatus {
  return value === 'present' || value === 'absent' || value === 'late' || value === 'excused' || value === 'holiday';
}

export default {
  listAttendances,
  getAttendanceById,
  createAttendance,
  updateAttendance,
  deleteAttendance,
  listAttendanceReferences,
};
