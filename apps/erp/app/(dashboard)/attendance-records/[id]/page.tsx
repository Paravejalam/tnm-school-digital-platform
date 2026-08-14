'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { useAttendanceRecordReferences } from '../../../../components/AttendanceRecordForm';
import { attendanceRecordErrorMessage, deleteAttendanceRecord, getAttendanceRecordById } from '../../../../services/attendanceRecord';
import type { AttendanceRecordItem } from '../../../../types/attendanceRecord';

export default function AttendanceRecordDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const recordId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const referencesState = useAttendanceRecordReferences();
  const [item, setItem] = React.useState<AttendanceRecordItem | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [deleting, setDeleting] = React.useState(false);

  const loadRecord = React.useCallback(async () => {
    if (!Number.isInteger(recordId) || recordId < 1) { setError('The attendance record identifier is invalid.'); setLoading(false); return; }
    setLoading(true); setError(null);
    try { setItem(await getAttendanceRecordById(recordId)); }
    catch (requestError: unknown) { setItem(null); setError(attendanceRecordErrorMessage(requestError, 'Unable to load attendance record.')); }
    finally { setLoading(false); }
  }, [recordId]);

  React.useEffect(() => { void loadRecord(); }, [loadRecord]);

  async function handleDelete() {
    if (!item || !window.confirm(`Delete attendance record ${item.record_name ?? 'this entry'}? This action cannot be undone.`)) return;
    setDeleting(true); setError(null);
    try { await deleteAttendanceRecord(item.id); router.push('/attendance-records'); }
    catch (requestError: unknown) { setError(attendanceRecordErrorMessage(requestError, 'Unable to delete the attendance record.')); setDeleting(false); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Attendance record details</h1><p className="mt-2 text-sm text-slate-600">Review the fields exposed by the AttendanceRecord service.</p></div><div className="flex flex-wrap gap-2"><button type="button" onClick={() => router.push('/attendance-records')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button>{item && <><button type="button" onClick={() => router.push(`/attendance-records/${item.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button><button type="button" disabled={deleting} onClick={() => void handleDelete()} className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">{deleting ? 'Deleting…' : 'Delete'}</button></>}</div></div>{error && <div className="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{error}</span><button type="button" onClick={() => void loadRecord()} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold hover:bg-red-100">Retry</button></div>}{referencesState.error && <div className="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between"><span>Reference labels are unavailable; identifiers are shown where necessary. {referencesState.error}</span><button type="button" onClick={() => void referencesState.reload()} className="rounded-lg border border-amber-200 bg-white px-3 py-2 font-semibold hover:bg-amber-100">Retry lookups</button></div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading attendance record…</div> : item ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-slate-500">Attendance record #{item.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{item.record_name ?? 'Unnamed attendance record'}</h2></div><StatusBadge status={item.status} /></div><div className="grid gap-4 md:grid-cols-2"><Info label="Record name" value={item.record_name ?? '—'} /><Info label="Attendance entry" value={attendanceLabel(referencesState.references?.attendance.find((entry) => entry.id === item.attendance_id), item.attendance_id)} /><Info label="Student" value={studentLabel(referencesState.references?.students.find((entry) => entry.id === item.student_id), item.student_id)} /><Info label="Status" value={item.status ?? '—'} /></div><p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">The current backend response intentionally omits note and recorded-by metadata. Edit preserves those hidden values by sending only record name and status.</p></div> : null}</div></Layout></ProtectedRoute>;
}

function attendanceLabel(item: { attendance_date: string | null; status: string | null } | undefined, id: number | null): string { return item ? `${item.attendance_date ?? 'Undated'} · ${item.status ?? 'unknown'} · #${id}` : id === null ? '—' : `Attendance #${id}`; }
function studentLabel(item: { first_name: string | null; last_name: string | null; admission_number: string | null } | undefined, id: number | null): string { if (!item) return id === null ? '—' : `Student #${id}`; const name = [item.first_name, item.last_name].filter(Boolean).join(' ').trim(); return name || item.admission_number || (id === null ? '—' : `Student #${id}`); }
function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'present' ? 'bg-emerald-100 text-emerald-700' : status === 'absent' ? 'bg-rose-100 text-rose-700' : status === 'late' ? 'bg-amber-100 text-amber-700' : status === 'excused' ? 'bg-violet-100 text-violet-700' : status === 'holiday' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }
function Info({ label, value }: { label: string; value: string }) { return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className="mt-2 break-words text-base font-semibold text-slate-900">{value}</p></div>; }
