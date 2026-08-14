'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../components/Layout';
import ProtectedRoute from '../../../components/ProtectedRoute';
import { listPermissions, permissionErrorMessage } from '../../../services/permission';
import type { PermissionListResponse, PermissionRecord } from '../../../types/permission';

const PER_PAGE = 15;

export default function PermissionListPage() {
  const router = useRouter();
  const [items, setItems] = React.useState<PermissionRecord[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [page, setPage] = React.useState(1);
  const [total, setTotal] = React.useState(0);
  const [search, setSearch] = React.useState('');
  const [module, setModule] = React.useState('');
  const [searchInput, setSearchInput] = React.useState('');
  const [moduleInput, setModuleInput] = React.useState('');
  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

  const loadPermissions = React.useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result: PermissionListResponse = await listPermissions({ page, per_page: PER_PAGE, search, module });
      setItems(result.items);
      setTotal(result.pagination.total);
      setPage(result.pagination.page);
    } catch (requestError: unknown) {
      setItems([]);
      setTotal(0);
      setError(permissionErrorMessage(requestError, 'Unable to load permissions.'));
    } finally {
      setLoading(false);
    }
  }, [module, page, search]);

  React.useEffect(() => { void loadPermissions(); }, [loadPermissions]);

  function submitFilters(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPage(1);
    setSearch(searchInput.trim());
    setModule(moduleInput.trim());
  }

  function clearFilters() {
    setPage(1);
    setSearch('');
    setModule('');
    setSearchInput('');
    setModuleInput('');
  }

  const hasFilters = Boolean(search || module);

  return <ProtectedRoute><Layout><div className="space-y-6">
    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Administration</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Permissions</h1><p className="mt-2 max-w-2xl text-sm text-slate-600">Review the authorization permissions registered for the school ERP. Permissions are managed by the backend and are read-only here.</p></div>
    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><form onSubmit={submitFilters} className="grid gap-3 md:grid-cols-[1fr_1fr_auto_auto]"><div><label htmlFor="permission-search" className="mb-2 block text-sm font-medium text-slate-700">Search</label><input id="permission-search" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} placeholder="Name or slug" className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white" /></div><div><label htmlFor="permission-module" className="mb-2 block text-sm font-medium text-slate-700">Module</label><input id="permission-module" value={moduleInput} onChange={(event) => setModuleInput(event.target.value)} placeholder="e.g. Auth" className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white" /></div><button type="submit" className="self-end rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Apply</button>{hasFilters && <button type="button" onClick={clearFilters} className="self-end rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100">Clear</button>}</form></div>
    {error && <Alert message={error} onRetry={() => void loadPermissions()} />}
    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div className="overflow-x-auto"><table className="min-w-[1050px] w-full text-left text-sm text-slate-700"><thead className="bg-slate-50 text-xs uppercase tracking-[0.14em] text-slate-500"><tr><th className="px-4 py-3 font-semibold">Name</th><th className="px-4 py-3 font-semibold">Slug</th><th className="px-4 py-3 font-semibold">Module</th><th className="px-4 py-3 font-semibold">Description</th><th className="px-4 py-3 text-right font-semibold">Actions</th></tr></thead><tbody>{loading ? <tr><td colSpan={5} className="px-4 py-12 text-center text-slate-500">Loading permissions…</td></tr> : items.length === 0 ? <tr><td colSpan={5} className="px-4 py-12 text-center text-slate-500">{hasFilters ? 'No permissions match the selected filters.' : 'No permissions are available.'}</td></tr> : items.map((item) => <tr key={item.id} className="border-t border-slate-200 hover:bg-slate-50"><td className="px-4 py-3 font-medium"><button type="button" onClick={() => router.push(`/permissions/${item.id}`)} className="text-left text-blue-600 hover:text-blue-700">{item.name ?? 'Unnamed permission'}</button></td><td className="px-4 py-3 font-mono text-xs text-slate-600">{item.slug ?? '—'}</td><td className="px-4 py-3">{item.module ?? '—'}</td><td className="max-w-sm px-4 py-3 text-slate-600">{item.description ?? '—'}</td><td className="px-4 py-3 text-right"><button type="button" onClick={() => router.push(`/permissions/${item.id}`)} className="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">View</button></td></tr>)}</tbody></table></div>{!loading && total > 0 && <div className="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between"><span>Page {page} of {totalPages} · {total} total</span><div className="flex gap-2"><button type="button" onClick={() => setPage((current) => Math.max(1, current - 1))} disabled={page <= 1} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Previous</button><button type="button" onClick={() => setPage((current) => Math.min(totalPages, current + 1))} disabled={page >= totalPages} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button></div></div>}</div>
  </div></Layout></ProtectedRoute>;
}

function Alert({ message, onRetry }: { message: string; onRetry: () => void }) { return <div className="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{message}</span><button type="button" onClick={onRetry} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold hover:bg-red-100">Retry</button></div>; }
