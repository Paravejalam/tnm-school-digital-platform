import api from './api';
import type { SubjectFormValues, SubjectListResponse, SubjectRecord } from '../types/subject';

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

function normalizePayload(payload: SubjectFormValues) {
  return {
    subject_name: payload.subject_name.trim(),
    code: payload.code.trim() === '' ? null : payload.code.trim(),
    section_id: Number(payload.section_id),
    status: payload.status,
  };
}

export async function listSubjects(params: Partial<Record<string, string | number>> = {}): Promise<SubjectListResponse> {
  const query = toQueryString({
    page: 1,
    per_page: DEFAULT_PER_PAGE,
    ...params,
  });

  return api.apiFetch(`/subjects${query}`);
}

export async function getSubjectById(id: number): Promise<SubjectRecord> {
  const result = await api.apiFetch(`/subjects/${id}`);
  return result as SubjectRecord;
}

export async function createSubject(payload: SubjectFormValues): Promise<SubjectRecord> {
  const result = await api.apiFetch('/subjects', {
    method: 'POST',
    body: JSON.stringify(normalizePayload(payload)),
  });

  return result as SubjectRecord;
}

export async function updateSubject(id: number, payload: SubjectFormValues): Promise<SubjectRecord> {
  const result = await api.apiFetch(`/subjects/${id}`, {
    method: 'PUT',
    body: JSON.stringify(normalizePayload(payload)),
  });

  return result as SubjectRecord;
}

export async function deleteSubject(id: number): Promise<{ message: string }> {
  return api.apiFetch(`/subjects/${id}`, { method: 'DELETE' });
}

export default {
  listSubjects,
  getSubjectById,
  createSubject,
  updateSubject,
  deleteSubject,
};
