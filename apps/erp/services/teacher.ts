import api from './api';
import type { TeacherFormValues, TeacherListResponse, TeacherRecord } from '../types/teacher';

const DEFAULT_PER_PAGE = 10;

function toQueryString(params: Record<string, string | number | undefined>): string {
  const search = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') return;
    search.set(key, String(value));
  });

  const query = search.toString();
  return query ? `?${query}` : '';
}

export async function listTeachers(params: Partial<Record<string, string | number>> = {}): Promise<TeacherListResponse> {
  const query = toQueryString({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
    ...params,
  });

  return api.apiFetch(`/teachers${query}`);
}

export async function getTeacherById(id: number): Promise<TeacherRecord> {
  const result = await api.apiFetch(`/teachers/${id}`);
  return result as TeacherRecord;
}

export async function createTeacher(payload: TeacherFormValues): Promise<TeacherRecord> {
  const result = await api.apiFetch('/teachers', {
    method: 'POST',
    body: JSON.stringify(payload),
  });

  return result as TeacherRecord;
}

export async function updateTeacher(id: number, payload: TeacherFormValues): Promise<TeacherRecord> {
  const result = await api.apiFetch(`/teachers/${id}`, {
    method: 'PUT',
    body: JSON.stringify(payload),
  });

  return result as TeacherRecord;
}

export async function deleteTeacher(id: number): Promise<{ message: string }> {
  return api.apiFetch(`/teachers/${id}`, { method: 'DELETE' });
}

export default {
  listTeachers,
  getTeacherById,
  createTeacher,
  updateTeacher,
  deleteTeacher,
};
