'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { useTimetableReferences } from '../../../../components/TimetableForm';
import { deleteTimetable, getTimetableById, timetableErrorMessage } from '../../../../services/timetable';
import type { TimetableRecord } from '../../../../types/timetable';

export default function TimetableDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const timetableId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const referencesState = useTimetableReferences();
  const [item, setItem] = React.useState<TimetableRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [deleting, setDeleting] = React.useState(false);

  const loadTimetable = React.useCallback(async () => {
    if (!Number.isInteger(timetableId) || timetableId < 1) {
      setError('The timetable identifier is invalid.');
      setLoading(false);
      return;
    }

    setLoading(true);
    setError(null);
    try { setItem(await getTimetableById(timetableId)); }
    catch (requestError: unknown) { setItem(null); setError(timetableErrorMessage(requestError, 'Unable to load timetable.')); }
    finally { setLoading(false); }
  }, [timetableId]);

  React.useEffect(() => { void loadTimetable(); }, [loadTimetable]);

  async function handleDelete() {
    if (!item || !window.confirm(`Delete timetable ${item.timetable_name ?? 'this entry'}? This action cannot be undone.`)) return;
    setDeleting(true);
    setError(null);
    try { await deleteTimetable(item.id); router.push('/timetables'); }
    catch (requestError: unknown) { setError(timetableErrorMessage(requestError, 'Unable to delete the timetable.')); setDeleting(false); }
  }

  return (
    <ProtectedRoute>
      <Layout>
        <div className="space-y-6">
          <div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Schedule management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Timetable details</h1><p className="mt-2 text-sm text-slate-600">Review the academic assignment stored by the Timetable service.</p></div>
            <div className="flex flex-wrap gap-2"><button type="button" onClick={() => router.push('/timetables')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button>{item && <><button type="button" onClick={() => router.push(`/timetables/${item.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button><button type="button" disabled={deleting} onClick={() => void handleDelete()} className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">{deleting ? 'Deleting…' : 'Delete'}</button></>}</div>
          </div>

          {error && <div className="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{error}</span><button type="button" onClick={() => void loadTimetable()} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold hover:bg-red-100">Retry</button></div>}
          {referencesState.error && <div className="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between"><span>{referencesState.error} Showing identifiers where labels are unavailable.</span><button type="button" onClick={() => void referencesState.reload()} className="rounded-lg border border-amber-200 bg-white px-3 py-2 font-semibold text-amber-800 hover:bg-amber-100">Retry options</button></div>}

          {loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading timetable…</div> : item ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-slate-500">Timetable record #{item.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{item.timetable_name ?? 'Unnamed timetable'}</h2></div><StatusBadge status={item.status} /></div><div className="grid gap-4 md:grid-cols-2"><Info label="Academic session" value={lookupLabel(referencesState.references?.academicSessions.find((option) => option.id === item.academic_session_id)?.session_name, item.academic_session_id, 'Session')} /><Info label="Class" value={lookupLabel(referencesState.references?.classes.find((option) => option.id === item.class_id)?.class_name, item.class_id, 'Class')} /><Info label="Section" value={lookupLabel(referencesState.references?.sections.find((option) => option.id === item.section_id)?.section_name, item.section_id, 'Section')} /><Info label="Subject" value={lookupLabel(referencesState.references?.subjects.find((option) => option.id === item.subject_id)?.subject_name, item.subject_id, 'Subject')} /><Info label="Teacher" value={teacherLabel(referencesState.references?.teachers.find((option) => option.id === item.teacher_id), item.teacher_id)} /><Info label="Backend identifier" value={String(item.id)} /></div><p className="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">The current Timetable backend contract contains no period or time-slot fields.</p></div> : null}
        </div>
      </Layout>
    </ProtectedRoute>
  );
}

function lookupLabel(label: string | null | undefined, id: number | null, noun: string): string {
  if (label) return label;
  return id === null ? '—' : `${noun} #${id}`;
}

function teacherLabel(teacher: { first_name: string | null; last_name: string | null } | undefined, id: number | null): string {
  if (!teacher) return id === null ? '—' : `Teacher #${id}`;
  const name = [teacher.first_name, teacher.last_name].filter((part): part is string => Boolean(part)).join(' ');
  return name || `Teacher #${id}`;
}

function StatusBadge({ status }: { status: string | null | undefined }) {
  const tone = status === 'active' ? 'bg-emerald-100 text-emerald-700' : status === 'draft' ? 'bg-amber-100 text-amber-700' : status === 'inactive' ? 'bg-slate-200 text-slate-700' : 'bg-slate-100 text-slate-700';
  return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>;
}

function Info({ label, value }: { label: string; value: string }) {
  return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className="mt-2 text-base font-semibold text-slate-900">{value}</p></div>;
}
