'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { useAttendanceReferences } from '../../../../components/AttendanceForm';
import { attendanceErrorMessage, getAttendanceById } from '../../../../services/attendance';
import type { AttendanceRecord } from '../../../../types/attendance';

export default function AttendanceDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const attendanceId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const referencesState = useAttendanceReferences();
  const [item, setItem] = React.useState<AttendanceRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!Number.isInteger(attendanceId) || attendanceId < 1) {
      setError('The attendance identifier is invalid.');
      setLoading(false);
      return;
    }
    let mounted = true;
    async function loadAttendance() {
      setLoading(true);
      setError(null);
      try {
        const result = await getAttendanceById(attendanceId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(attendanceErrorMessage(requestError, 'Unable to load attendance record.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }
    void loadAttendance();
    return () => { mounted = false; };
  }, [attendanceId]);

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Attendance details</h1><p className="mt-2 text-sm text-slate-600">Review the Attendance API response for this student and date.</p></div><div className="flex items-center gap-2"><button type="button" onClick={() => router.push('/attendance')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button>{item && <button type="button" onClick={() => router.push(`/attendance/${item.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button>}</div></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{referencesState.error && <div className="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Reference labels are unavailable; identifiers are shown where necessary. {referencesState.error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading attendance…</div> : item ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-slate-500">Attendance record #{item.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{item.attendance_date ?? 'Undated attendance'}</h2></div><StatusBadge status={item.status} /></div><div className="grid gap-4 md:grid-cols-2"><Info label="Attendance date" value={item.attendance_date ?? '—'} /><Info label="Academic session" value={sessionLabel(referencesState.references?.sessions.find((entry) => entry.id === item.academic_session_id)?.session_name, item.academic_session_id)} /><Info label="Class" value={classLabel(referencesState.references?.classes.find((entry) => entry.id === item.class_id)?.class_name, item.class_id)} /><Info label="Section" value={sectionLabel(referencesState.references?.sections.find((entry) => entry.id === item.section_id)?.section_name, item.section_id)} /><Info label="Student" value={studentLabel(referencesState.references?.students.find((entry) => entry.id === item.student_id), item.student_id)} /><Info label="Status" value={item.status ?? '—'} /></div><p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Remarks and marker details are stored by the backend but intentionally omitted from the current Attendance read response.</p></div> : null}</div></Layout></ProtectedRoute>;
}

function sessionLabel(label: string | null | undefined, id: number | null): string { return label || (id === null ? '—' : `Session #${id}`); }
function classLabel(label: string | null | undefined, id: number | null): string { return label || (id === null ? '—' : `Class #${id}`); }
function sectionLabel(label: string | null | undefined, id: number | null): string { return label || (id === null ? '—' : `Section #${id}`); }
function studentLabel(student: { first_name: string | null; last_name: string | null; admission_number: string | null } | undefined, id: number | null): string { if (!student) return id === null ? '—' : `Student #${id}`; const name = [student.first_name, student.last_name].filter(Boolean).join(' ').trim(); return name || student.admission_number || (id === null ? '—' : `Student #${id}`); }
function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'present' ? 'bg-emerald-100 text-emerald-700' : status === 'absent' ? 'bg-rose-100 text-rose-700' : status === 'late' ? 'bg-amber-100 text-amber-700' : status === 'excused' ? 'bg-violet-100 text-violet-700' : status === 'holiday' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }
function Info({ label, value }: { label: string; value: string }) { return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className="mt-2 text-base font-semibold text-slate-900">{value}</p></div>; }
