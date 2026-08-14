'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { useAcademicClassSessions } from '../../../../components/AcademicClassForm';
import { academicClassErrorMessage, getAcademicClassById } from '../../../../services/academicClass';
import type { AcademicClassRecord } from '../../../../types/academicClass';

export default function AcademicClassDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const classId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const sessionsState = useAcademicClassSessions();
  const [item, setItem] = React.useState<AcademicClassRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  React.useEffect(() => {
    if (!Number.isInteger(classId) || classId < 1) { setError('The academic class identifier is invalid.'); setLoading(false); return; }
    let mounted = true;
    async function loadClass() { setLoading(true); setError(null); try { const result = await getAcademicClassById(classId); if (mounted) setItem(result); } catch (requestError: unknown) { if (mounted) setError(academicClassErrorMessage(requestError, 'Unable to load academic class.')); } finally { if (mounted) setLoading(false); } }
    void loadClass(); return () => { mounted = false; };
  }, [classId]);
  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Academic management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Academic class details</h1><p className="mt-2 text-sm text-slate-600">Review the fields exposed by the AcademicClass service.</p></div><div className="flex items-center gap-2"><button type="button" onClick={() => router.push('/classes')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button>{item && <button type="button" onClick={() => router.push(`/classes/${item.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button>}</div></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{sessionsState.error && <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Session labels are unavailable; the session identifier is shown where necessary. {sessionsState.error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading academic class…</div> : item ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-slate-500">Academic class record #{item.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{item.class_name ?? 'Unnamed academic class'}</h2></div><StatusBadge status={item.status} /></div><div className="grid gap-4 md:grid-cols-2"><Info label="Class name" value={item.class_name ?? '—'} /><Info label="Academic session" value={sessionLabel(sessionsState.sessions?.find((session) => session.id === item.academic_session_id)?.session_name, item.academic_session_id)} /><Info label="Status" value={item.status ?? '—'} /><Info label="Backend identifier" value={String(item.id)} /></div><p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Grade level is stored by the backend but omitted from the current AcademicClass response.</p></div> : null}</div></Layout></ProtectedRoute>;
}

function sessionLabel(label: string | null | undefined, id: number | null): string { return label || (id === null ? '—' : `Session #${id}`); }
function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'active' ? 'bg-emerald-100 text-emerald-700' : status === 'inactive' ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }
function Info({ label, value }: { label: string; value: string }) { return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className="mt-2 text-base font-semibold text-slate-900">{value}</p></div>; }
