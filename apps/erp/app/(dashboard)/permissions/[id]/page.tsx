'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { getPermissionById, permissionErrorMessage } from '../../../../services/permission';
import type { PermissionRecord } from '../../../../types/permission';

export default function PermissionDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const permissionId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const [item, setItem] = React.useState<PermissionRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!Number.isInteger(permissionId) || permissionId < 1) {
      setError('The permission identifier is invalid.');
      setLoading(false);
      return;
    }
    let mounted = true;
    async function loadPermission() {
      setLoading(true);
      setError(null);
      try {
        const result = await getPermissionById(permissionId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(permissionErrorMessage(requestError, 'Unable to load permission.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }
    void loadPermission();
    return () => { mounted = false; };
  }, [permissionId]);

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Administration</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Permission details</h1><p className="mt-2 text-sm text-slate-600">Review the read-only permission definition returned by the authorization service.</p></div><button type="button" onClick={() => router.push('/permissions')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to permissions</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading permission…</div> : item ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6"><p className="text-sm text-slate-500">Permission record #{item.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{item.name ?? 'Unnamed permission'}</h2></div><div className="grid gap-4 md:grid-cols-2"><Info label="Slug" value={item.slug ?? '—'} mono /><Info label="Module" value={item.module ?? '—'} /><Info label="Description" value={item.description ?? '—'} /><Info label="Created" value={formatDate(item.created_at)} /><Info label="Updated" value={formatDate(item.updated_at)} /><Info label="Backend identifier" value={String(item.id)} /></div></div> : null}</div></Layout></ProtectedRoute>;
}

function formatDate(value: string | null): string { if (!value) return '—'; const parsed = new Date(value); return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString(); }
function Info({ label, value, mono = false }: { label: string; value: string; mono?: boolean }) { return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className={`mt-2 text-base font-semibold text-slate-900 ${mono ? 'font-mono text-sm' : ''}`}>{value}</p></div>; }
