'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { useHolidayCalendarSessions } from '../../../../components/HolidayCalendarForm';
import { deleteHolidayCalendar, getHolidayCalendarById, holidayCalendarErrorMessage } from '../../../../services/holidayCalendar';
import type { HolidayCalendarRecord } from '../../../../types/holidayCalendar';

export default function HolidayCalendarDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const calendarId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const sessionState = useHolidayCalendarSessions();
  const [item, setItem] = React.useState<HolidayCalendarRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  const [deleting, setDeleting] = React.useState(false);

  React.useEffect(() => {
    if (!Number.isInteger(calendarId) || calendarId < 1) {
      setError('The holiday calendar identifier is invalid.');
      setLoading(false);
      return;
    }
    let mounted = true;
    async function loadCalendar() {
      setLoading(true);
      setError(null);
      try {
        const result = await getHolidayCalendarById(calendarId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(holidayCalendarErrorMessage(requestError, 'Unable to load holiday calendar.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }
    void loadCalendar();
    return () => { mounted = false; };
  }, [calendarId]);

  async function handleDelete() {
    if (!item || !window.confirm(`Delete holiday calendar ${item.holiday_name ?? 'this entry'}?`)) return;
    setDeleting(true);
    setError(null);
    setSuccess(null);
    try {
      await deleteHolidayCalendar(item.id);
      setSuccess('Holiday calendar deleted successfully.');
      setItem(null);
    } catch (requestError: unknown) {
      setError(holidayCalendarErrorMessage(requestError, 'Unable to delete the holiday calendar.'));
    } finally {
      setDeleting(false);
    }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Calendar management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Holiday calendar details</h1><p className="mt-2 text-sm text-slate-600">Review the record returned by the Holiday Calendar service.</p></div><div className="flex flex-wrap items-center gap-2"><button type="button" onClick={() => router.push('/holiday-calendars')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button>{item && <><button type="button" onClick={() => router.push(`/holiday-calendars/${item.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button><button type="button" onClick={() => void handleDelete()} disabled={deleting} className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">{deleting ? 'Deleting…' : 'Delete'}</button></>}</div></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{success && <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{success} <button type="button" onClick={() => router.push('/holiday-calendars')} className="ml-2 font-semibold underline">Return to list</button></div>}{sessionState.error && <div className="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between"><span>{sessionState.error} Showing the session identifier where the label is unavailable.</span><button type="button" onClick={() => void sessionState.reload()} className="rounded-lg border border-amber-200 bg-white px-3 py-2 font-semibold text-amber-800 hover:bg-amber-100">Retry session lookup</button></div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading holiday calendar…</div> : item ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-slate-500">Holiday calendar record #{item.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{item.holiday_name ?? 'Unnamed holiday calendar'}</h2></div><StatusBadge status={item.status} /></div><div className="grid gap-4 md:grid-cols-2"><Info label="Academic session" value={sessionLabel(sessionState.sessions?.find((session) => session.id === item.academic_session_id)?.session_name, item.academic_session_id)} /><Info label="Backend identifier" value={String(item.id)} /></div><div className="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">The backend detail response does not expose the holiday date, type, recurring flag or description.</div></div> : !loading && !success ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Holiday calendar details are unavailable.</div> : null}</div></Layout></ProtectedRoute>;
}

function sessionLabel(label: string | null | undefined, id: number | null): string { return label || (id === null ? '—' : `Session #${id}`); }

function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'active' ? 'bg-emerald-100 text-emerald-700' : status === 'inactive' ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }

function Info({ label, value }: { label: string; value: string }) { return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className="mt-2 text-base font-semibold text-slate-900">{value}</p></div>; }
