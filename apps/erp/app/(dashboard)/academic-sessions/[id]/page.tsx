'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { academicSessionErrorMessage, getAcademicSessionById } from '../../../../services/academicSession';
import type { AcademicSessionRecord } from '../../../../types/academicSession';

export default function AcademicSessionDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const sessionId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const [item, setItem] = React.useState<AcademicSessionRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!Number.isInteger(sessionId) || sessionId < 1) {
      setError('The academic session identifier is invalid.');
      setLoading(false);
      return;
    }
    let mounted = true;
    async function loadSession() {
      setLoading(true);
      setError(null);
      try {
        const result = await getAcademicSessionById(sessionId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(academicSessionErrorMessage(requestError, 'Unable to load academic session.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }
    void loadSession();
    return () => { mounted = false; };
  }, [sessionId]);

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Academic management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Academic session details</h1><p className="mt-2 text-sm text-slate-600">Review the fields exposed by the Academic Session service.</p></div><div className="flex items-center gap-2"><button type="button" onClick={() => router.push('/academic-sessions')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button>{item && <button type="button" onClick={() => router.push(`/academic-sessions/${item.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button>}</div></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading academic session…</div> : item ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-slate-500">Academic session record #{item.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{item.session_name ?? 'Unnamed academic session'}</h2></div><StatusBadge status={item.status} /></div><div className="grid gap-4 md:grid-cols-2"><Info label="Session name" value={item.session_name ?? '—'} /><Info label="Backend identifier" value={String(item.id)} /></div><p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">The current API response intentionally exposes only the identifier, session name, and status. Stored dates and current-session state are preserved by update operations but are not available to display here.</p></div> : null}</div></Layout></ProtectedRoute>;
}

function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'active' ? 'bg-emerald-100 text-emerald-700' : status === 'inactive' ? 'bg-slate-200 text-slate-700' : status === 'archived' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }
function Info({ label, value }: { label: string; value: string }) { return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className="mt-2 text-base font-semibold text-slate-900">{value}</p></div>; }
