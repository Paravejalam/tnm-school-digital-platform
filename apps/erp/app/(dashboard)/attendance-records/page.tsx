'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../components/Layout';
import ProtectedRoute from '../../../components/ProtectedRoute';
import { useAttendanceRecordReferences } from '../../../components/AttendanceRecordForm';
import { attendanceRecordErrorMessage, deleteAttendanceRecord, listAttendanceRecords } from '../../../services/attendanceRecord';
import type { AttendanceRecordItem, AttendanceRecordListResponse } from '../../../types/attendanceRecord';

const PER_PAGE = 15;

export default function AttendanceRecordListPage() {
  const router = useRouter();
  const referencesState = useAttendanceRecordReferences();
  const [items, setItems] = React.useState<AttendanceRecordItem[]>([]);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [page, setPage] = React.useState(1);
  const [total, setTotal] = React.useState(0);
  const [search, setSearch] = React.useState('');
  const [searchInput, setSearchInput] = React.useState('');
  const [deletingId, setDeletingId] = React.useState<number | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

  const loadRecords = React.useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result: AttendanceRecordListResponse = await listAttendanceRecords({ page, per_page: PER_PAGE, search });
      setItems(result.items);
      setTotal(result.pagination.total);
      setPage(result.pagination.page);
    } catch (requestError: unknown) {
      setItems([]);
      setTotal(0);
      setError(attendanceRecordErrorMessage(requestError, 'Unable to load attendance records.'));
    } finally {
      setLoading(false);
    }
  }, [page, search]);

  React.useEffect(() => { void loadRecords(); }, [loadRecords]);

  function submitSearch(event: React.FormEvent<HTMLFormElement>) { event.preventDefault(); setPage(1); setSearch(searchInput.trim()); }

  async function handleDelete(item: AttendanceRecordItem) {
    if (!window.confirm(`Delete attendance record ${item.record_name ?? 'this entry'}?`)) return;
    setDeletingId(item.id);
    setError(null);
    setSuccess(null);
    try {
      await deleteAttendanceRecord(item.id);
      setSuccess('Attendance record deleted successfully.');
      if (items.length === 1 && page > 1) setPage((current) => current - 1);
      else await loadRecords();
    } catch (requestError: unknown) {
      setError(attendanceRecordErrorMessage(requestError, 'Unable to delete the attendance record.'));
    } finally { setDeletingId(null); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Attendance Records</h1><p className="mt-2 max-w-2xl text-sm text-slate-600">Manage detailed attendance entries linked to attendance sessions and students.</p></div><button type="button" onClick={() => router.push('/attendance-records/new')} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">Add attendance record</button></div>
    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><form onSubmit={submitSearch} className="flex flex-col gap-3 md:flex-row md:items-end"><div className="flex-1"><label htmlFor="attendance-record-search" className="mb-2 block text-sm font-medium text-slate-700">Search notes</label><input id="attendance-record-search" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white" /></div><button type="submit" className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Search</button>{search && <button type="button" onClick={() => { setSearch(''); setSearchInput(''); setPage(1); }} className="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100">Clear</button>}</form></div>
    {error && <Alert message={error} onRetry={() => void loadRecords()} />}{success && <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{success}</div>}{referencesState.error && <div className="flex flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between"><span>Reference labels are unavailable; identifiers are shown where necessary. {referencesState.error}</span><button type="button" onClick={() => void referencesState.reload()} className="rounded-lg border border-amber-200 bg-white px-3 py-2 font-semibold hover:bg-amber-100">Retry lookups</button></div>}
    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div className="overflow-x-auto"><table className="min-w-[820px] w-full text-left text-sm text-slate-700"><thead className="bg-slate-50 text-xs uppercase tracking-[0.14em] text-slate-500"><tr><th className="px-4 py-3 font-semibold">Record name</th><th className="px-4 py-3 font-semibold">Attendance</th><th className="px-4 py-3 font-semibold">Student</th><th className="px-4 py-3 font-semibold">Status</th><th className="px-4 py-3 text-right font-semibold">Actions</th></tr></thead><tbody>{loading ? <tr><td colSpan={5} className="px-4 py-12 text-center text-slate-500">Loading attendance records…</td></tr> : items.length === 0 ? <tr><td colSpan={5} className="px-4 py-12 text-center text-slate-500">{search ? 'No attendance records match your search.' : 'No attendance records have been created yet.'}</td></tr> : items.map((item) => <tr key={item.id} className="border-t border-slate-200 hover:bg-slate-50"><td className="px-4 py-3 font-medium"><button type="button" onClick={() => router.push(`/attendance-records/${item.id}`)} className="text-left text-blue-600 hover:text-blue-700">{item.record_name ?? 'Unnamed attendance record'}</button></td><td className="px-4 py-3">{attendanceLabel(referencesState.references?.attendance.find((entry) => entry.id === item.attendance_id), item.attendance_id)}</td><td className="px-4 py-3">{studentLabel(referencesState.references?.students.find((entry) => entry.id === item.student_id), item.student_id)}</td><td className="px-4 py-3"><StatusBadge status={item.status} /></td><td className="px-4 py-3"><div className="flex justify-end gap-2"><button type="button" onClick={() => router.push(`/attendance-records/${item.id}`)} className="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">View</button><button type="button" onClick={() => router.push(`/attendance-records/${item.id}/edit`)} className="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">Edit</button><button type="button" disabled={deletingId === item.id} onClick={() => void handleDelete(item)} className="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">{deletingId === item.id ? 'Deleting…' : 'Delete'}</button></div></td></tr>)}</tbody></table></div>{!loading && total > 0 && <div className="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between"><span>Page {page} of {totalPages} · {total} total</span><div className="flex gap-2"><button type="button" onClick={() => setPage((current) => Math.max(1, current - 1))} disabled={page <= 1} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Previous</button><button type="button" onClick={() => setPage((current) => Math.min(totalPages, current + 1))} disabled={page >= totalPages} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button></div></div>}</div>
  </div></Layout></ProtectedRoute>;
}

function attendanceLabel(item: { attendance_date: string | null; status: string | null } | undefined, id: number | null): string { return item ? `${item.attendance_date ?? 'Undated'} · ${item.status ?? 'unknown'} · #${id}` : id === null ? '—' : `Attendance #${id}`; }
function studentLabel(item: { first_name: string | null; last_name: string | null; admission_number: string | null } | undefined, id: number | null): string { if (!item) return id === null ? '—' : `Student #${id}`; const name = [item.first_name, item.last_name].filter(Boolean).join(' ').trim(); return name || item.admission_number || (id === null ? '—' : `Student #${id}`); }
function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'present' ? 'bg-emerald-100 text-emerald-700' : status === 'absent' ? 'bg-rose-100 text-rose-700' : status === 'late' ? 'bg-amber-100 text-amber-700' : status === 'excused' ? 'bg-violet-100 text-violet-700' : status === 'holiday' ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }
function Alert({ message, onRetry }: { message: string; onRetry: () => void }) { return <div className="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{message}</span><button type="button" onClick={onRetry} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold hover:bg-red-100">Retry</button></div>; }
