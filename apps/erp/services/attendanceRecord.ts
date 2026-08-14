import api from './api';
import { listAttendances } from './attendance';
import { listStudents } from './student';
import type {
  AttendanceRecordFormValues,
  AttendanceRecordItem,
  AttendanceRecordListResponse,
  AttendanceRecordReferenceOptions,
  AttendanceRecordStatus,
  AttendanceRecordUpdateValues,
} from '../types/attendanceRecord';

const DEFAULT_PER_PAGE = 15;
const REFERENCE_PER_PAGE = 100;

interface JsonObject {
  [key: string]: unknown;
}

export interface AttendanceRecordRequestError {
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

function parseAttendanceRecord(value: unknown): AttendanceRecordItem {
  if (!isObject(value)) throw new Error('The attendance record response was invalid.');
  return {
    id: requiredId(value.id, 'attendance record'),
    record_name: stringValue(value.record_name),
    attendance_id: numberValue(value.attendance_id),
    student_id: numberValue(value.student_id),
    status: stringValue(value.status),
  };
}

function parseList(response: unknown): AttendanceRecordListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) throw new Error('The attendance record list response was invalid.');

  const items = payload.items.map(parseAttendanceRecord);
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

function normalizeCreatePayload(payload: AttendanceRecordFormValues): Record<string, string | number | null> {
  return {
    record_name: payload.record_name.trim(),
    attendance_id: Number(payload.attendance_id),
    student_id: Number(payload.student_id),
    status: payload.status,
    note: payload.note.trim() === '' ? null : payload.note.trim(),
    recorded_by: payload.recorded_by.trim() === '' ? null : Number(payload.recorded_by),
  };
}

function normalizeUpdatePayload(payload: AttendanceRecordUpdateValues): Record<string, string> {
  return {
    record_name: payload.record_name.trim(),
    status: payload.status,
  };
}

export async function listAttendanceRecords(params: Partial<Record<string, string | number>> = {}): Promise<AttendanceRecordListResponse> {
  const query = new URLSearchParams();
  Object.entries({ page: 1, per_page: DEFAULT_PER_PAGE, ...params }).forEach(([key, value]) => {
    if (value === undefined || (typeof value === 'string' && value === '')) return;
    query.set(key, String(value));
  });
  return parseList(await api.apiFetch(`/attendance-records?${query.toString()}`));
}

export async function getAttendanceRecordById(id: number): Promise<AttendanceRecordItem> {
  return parseAttendanceRecord(unwrapResponse(await api.apiFetch(`/attendance-records/${id}`)));
}

export async function createAttendanceRecord(payload: AttendanceRecordFormValues): Promise<AttendanceRecordItem> {
  const response: unknown = await api.apiFetch('/attendance-records', {
    method: 'POST',
    body: JSON.stringify(normalizeCreatePayload(payload)),
  });
  return parseAttendanceRecord(unwrapResponse(response));
}

export async function updateAttendanceRecord(id: number, payload: AttendanceRecordUpdateValues): Promise<AttendanceRecordItem> {
  const response: unknown = await api.apiFetch(`/attendance-records/${id}`, {
    method: 'PUT',
    body: JSON.stringify(normalizeUpdatePayload(payload)),
  });
  return parseAttendanceRecord(unwrapResponse(response));
}

export async function deleteAttendanceRecord(id: number): Promise<{ message: string }> {
  const response = unwrapResponse(await api.apiFetch(`/attendance-records/${id}`, { method: 'DELETE' }));
  if (!isObject(response) || typeof response.message !== 'string') return { message: 'Attendance record deleted successfully.' };
  return { message: response.message };
}

export async function listAttendanceRecordReferences(): Promise<AttendanceRecordReferenceOptions> {
  const [attendance, students] = await Promise.all([
    listAttendances({ page: 1, per_page: REFERENCE_PER_PAGE }),
    listStudents({ page: 1, per_page: REFERENCE_PER_PAGE }),
  ]);
  return { attendance: attendance.items, students: students.items };
}

export function toAttendanceRecordRequestError(error: unknown): AttendanceRecordRequestError {
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

export function attendanceRecordErrorMessage(error: unknown, fallback: string): string {
  const parsed = toAttendanceRecordRequestError(error);
  if (parsed.status === 401) return 'Your session has expired. Please sign in again.';
  if (parsed.status === 403) return 'You do not have permission to perform this attendance record action.';
  if (parsed.status === 404) return 'The requested attendance record could not be found.';
  if (parsed.status >= 500) return 'The server could not complete the attendance record request. Please try again.';
  if (parsed.status === 0) return parsed.message;
  return parsed.message || fallback;
}

export function isAttendanceRecordStatus(value: string): value is AttendanceRecordStatus {
  return value === 'present' || value === 'absent' || value === 'late' || value === 'excused' || value === 'holiday';
}

export default {
  listAttendanceRecords,
  getAttendanceRecordById,
  createAttendanceRecord,
  updateAttendanceRecord,
  deleteAttendanceRecord,
  listAttendanceRecordReferences,
};
