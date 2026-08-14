import api from './api';
import type { PermissionListResponse, PermissionRecord } from '../types/permission';

const DEFAULT_PER_PAGE = 15;

interface JsonObject {
  [key: string]: unknown;
}

export interface PermissionRequestError {
  status: number;
  message: string;
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
  if (id === null || !Number.isInteger(id) || id < 1) throw new Error('The permission response contained an invalid identifier.');
  return id;
}

function parsePermission(value: unknown): PermissionRecord {
  if (!isObject(value)) throw new Error('The permission response was invalid.');
  return {
    id: requiredId(value.id),
    name: stringValue(value.name),
    slug: stringValue(value.slug),
    module: stringValue(value.module),
    description: stringValue(value.description),
    created_at: stringValue(value.created_at),
    updated_at: stringValue(value.updated_at),
  };
}

function parseList(response: unknown): PermissionListResponse {
  const payload = unwrapResponse(response);
  if (!isObject(payload) || !Array.isArray(payload.items)) throw new Error('The permission list response was invalid.');
  const items = payload.items.map(parsePermission);
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
  const query = new URLSearchParams();
  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === '') return;
    query.set(key, String(value));
  });
  const result = query.toString();
  return result ? `?${result}` : '';
}

export async function listPermissions(params: Partial<Record<string, string | number>> = {}): Promise<PermissionListResponse> {
  const query = toQueryString({ page: 1, per_page: DEFAULT_PER_PAGE, ...params });
  return parseList(await api.apiFetch(`/permissions${query}`));
}

export async function getPermissionById(id: number): Promise<PermissionRecord> {
  return parsePermission(unwrapResponse(await api.apiFetch(`/permissions/${id}`)));
}

export function toPermissionRequestError(error: unknown): PermissionRequestError {
  if (!isObject(error)) return { status: 0, message: 'A network error prevented the request from completing.' };
  return {
    status: typeof error.status === 'number' ? error.status : 0,
    message: typeof error.message === 'string' ? error.message : 'The request could not be completed.',
  };
}

export function permissionErrorMessage(error: unknown, fallback: string): string {
  const parsed = toPermissionRequestError(error);
  if (parsed.status === 401) return 'Your session has expired. Please sign in again.';
  if (parsed.status === 403) return 'You do not have permission to view permissions.';
  if (parsed.status === 404) return 'The requested permission could not be found.';
  if (parsed.status >= 500) return 'The server could not complete the request. Please try again.';
  if (parsed.status === 0) return parsed.message;
  return parsed.message || fallback;
}

export default { listPermissions, getPermissionById };
