'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { usePeriodTimetables } from '../../../../components/PeriodForm';
import { deletePeriod, getPeriodById, periodErrorMessage } from '../../../../services/period';
import type { PeriodRecord } from '../../../../types/period';

export default function PeriodDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const periodId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const timetableState = usePeriodTimetables();
  const [item, setItem] = React.useState<PeriodRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [deleting, setDeleting] = React.useState(false);

  const loadPeriod = React.useCallback(async () => {
    if (!Number.isInteger(periodId) || periodId < 1) { setError('The period identifier is invalid.'); setLoading(false); return; }
    setLoading(true); setError(null);
    try { setItem(await getPeriodById(periodId)); }
    catch (requestError: unknown) { setItem(null); setError(periodErrorMessage(requestError, 'Unable to load period.')); }
    finally { setLoading(false); }
  }, [periodId]);
  React.useEffect(() => { void loadPeriod(); }, [loadPeriod]);

  async function handleDelete() {
    if (!item || !window.confirm(`Delete period ${item.period_name ?? 'this entry'}? This action cannot be undone.`)) return;
    setDeleting(true); setError(null);
    try { await deletePeriod(item.id); router.push('/periods'); }
    catch (requestError: unknown) { setError(periodErrorMessage(requestError, 'Unable to delete the period.')); setDeleting(false); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Schedule management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Period details</h1><p className="mt-2 text-sm text-slate-600">Review the fields exposed by the Period service.</p></div><div className="flex flex-wrap gap-2"><button type="button" onClick={() => router.push('/periods')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button>{item && <><button type="button" onClick={() => router.push(`/periods/${item.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button><button type="button" disabled={deleting} onClick={() => void handleDelete()} className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">{deleting ? 'Deleting…' : 'Delete'}</button></>}</div></div>{error && <div className="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{error}</span><button type="button" onClick={() => void loadPeriod()} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold hover:bg-red-100">Retry</button></div>}{timetableState.error && <div className="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between"><span>{timetableState.error} Showing the timetable identifier where the label is unavailable.</span><button type="button" onClick={() => void timetableState.reload()} className="rounded-lg border border-amber-200 bg-white px-3 py-2 font-semibold hover:bg-amber-100">Retry timetable lookup</button></div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading period…</div> : item ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-slate-500">Period record #{item.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{item.period_name ?? 'Unnamed period'}</h2></div><StatusBadge status={item.status} /></div><div className="grid gap-4 md:grid-cols-2"><Info label="Timetable" value={timetableLabel(timetableState.timetables?.find((timetable) => timetable.id === item.timetable_id)?.timetable_name, item.timetable_id)} /><Info label="Period name" value={item.period_name ?? '—'} /><Info label="Status" value={item.status ?? '—'} /><Info label="Backend identifier" value={String(item.id)} /></div><p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Day, start time, end time, and period order are stored by the backend but intentionally omitted from the current Period response.</p></div> : null}</div></Layout></ProtectedRoute>;
}

function timetableLabel(label: string | null | undefined, id: number | null): string { return label || (id === null ? '—' : `Timetable #${id}`); }
function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'active' ? 'bg-emerald-100 text-emerald-700' : status === 'inactive' ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }
function Info({ label, value }: { label: string; value: string }) { return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className="mt-2 break-words text-base font-semibold text-slate-900">{value}</p></div>; }
