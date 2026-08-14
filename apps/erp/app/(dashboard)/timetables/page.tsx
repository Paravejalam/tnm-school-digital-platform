'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../components/Layout';
import ProtectedRoute from '../../../components/ProtectedRoute';
import { useTimetableReferences } from '../../../components/TimetableForm';
import {
  deleteTimetable,
  listTimetables,
  timetableErrorMessage,
} from '../../../services/timetable';
import type { TimetableListResponse, TimetableRecord } from '../../../types/timetable';

const PER_PAGE = 15;

export default function TimetableListPage() {
  const router = useRouter();
  const referencesState = useTimetableReferences();
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [timetables, setTimetables] = React.useState<TimetableRecord[]>([]);
  const [page, setPage] = React.useState(1);
  const [total, setTotal] = React.useState(0);
  const [search, setSearch] = React.useState('');
  const [searchInput, setSearchInput] = React.useState('');
  const [deletingId, setDeletingId] = React.useState<number | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);

  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

  const loadTimetables = React.useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const result: TimetableListResponse = await listTimetables({
        page,
        per_page: PER_PAGE,
        search,
      });
      setTimetables(result.items);
      setTotal(result.pagination.total);
      setPage(result.pagination.page);
    } catch (requestError: unknown) {
      setError(timetableErrorMessage(requestError, 'Unable to load timetables.'));
      setTimetables([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  }, [page, search]);

  React.useEffect(() => {
    void loadTimetables();
  }, [loadTimetables]);

  async function handleDelete(item: TimetableRecord) {
    if (!window.confirm(`Delete timetable ${item.timetable_name ?? 'this entry'}?`)) return;

    setDeletingId(item.id);
    setError(null);
    setSuccess(null);
    try {
      await deleteTimetable(item.id);
      setSuccess('Timetable deleted successfully.');
      if (timetables.length === 1 && page > 1) {
        setPage((current) => current - 1);
      } else {
        await loadTimetables();
      }
    } catch (requestError: unknown) {
      setError(timetableErrorMessage(requestError, 'Unable to delete the timetable.'));
    } finally {
      setDeletingId(null);
    }
  }

  function handleSearch(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPage(1);
    setSearch(searchInput.trim());
    setSuccess(null);
  }

  return (
    <ProtectedRoute>
      <Layout>
        <div className="space-y-6">
          <div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Schedule management</p>
              <h1 className="mt-2 text-3xl font-semibold text-slate-900">Timetables</h1>
              <p className="mt-2 max-w-2xl text-sm text-slate-600">Manage academic timetable assignments by session, class, section, subject, and teacher.</p>
            </div>
            <button type="button" onClick={() => router.push('/timetables/new')} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
              Add timetable
            </button>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form onSubmit={handleSearch} className="flex flex-col gap-3 md:flex-row">
              <label htmlFor="timetable-search" className="sr-only">Search timetables</label>
              <input id="timetable-search" value={searchInput} onChange={(event) => setSearchInput(event.target.value)} placeholder="Search by timetable name" className="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white" />
              <button type="submit" className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100">Search</button>
              {search && <button type="button" onClick={() => { setSearch(''); setSearchInput(''); setPage(1); }} className="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100">Clear</button>}
            </form>
          </div>

          {error && <Alert message={error} onRetry={() => void loadTimetables()} />}
          {success && <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{success}</div>}
          {referencesState.error && <Alert message={`Lookup labels unavailable: ${referencesState.error}`} onRetry={() => void referencesState.reload()} subtle />}

          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
              <table className="min-w-[1050px] w-full text-left text-sm text-slate-700">
                <thead className="bg-slate-50 text-xs uppercase tracking-[0.14em] text-slate-500">
                  <tr>
                    <th className="px-4 py-3 font-semibold">Name</th>
                    <th className="px-4 py-3 font-semibold">Session</th>
                    <th className="px-4 py-3 font-semibold">Class</th>
                    <th className="px-4 py-3 font-semibold">Section</th>
                    <th className="px-4 py-3 font-semibold">Subject</th>
                    <th className="px-4 py-3 font-semibold">Teacher</th>
                    <th className="px-4 py-3 font-semibold">Status</th>
                    <th className="px-4 py-3 font-semibold text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {loading ? (
                    <tr><td colSpan={8} className="px-4 py-12 text-center text-slate-500">Loading timetables…</td></tr>
                  ) : timetables.length === 0 ? (
                    <tr><td colSpan={8} className="px-4 py-12 text-center text-slate-500">{search ? 'No timetables match your search.' : 'No timetables have been created yet.'}</td></tr>
                  ) : timetables.map((item) => (
                    <tr key={item.id} className="border-t border-slate-200 hover:bg-slate-50">
                      <td className="px-4 py-3 font-medium text-slate-800"><button type="button" onClick={() => router.push(`/timetables/${item.id}`)} className="text-left font-medium text-blue-600 hover:text-blue-700">{item.timetable_name ?? 'Unnamed timetable'}</button></td>
                      <td className="px-4 py-3">{lookupLabel(referencesState.references?.academicSessions.find((option) => option.id === item.academic_session_id)?.session_name, item.academic_session_id, 'Session')}</td>
                      <td className="px-4 py-3">{lookupLabel(referencesState.references?.classes.find((option) => option.id === item.class_id)?.class_name, item.class_id, 'Class')}</td>
                      <td className="px-4 py-3">{lookupLabel(referencesState.references?.sections.find((option) => option.id === item.section_id)?.section_name, item.section_id, 'Section')}</td>
                      <td className="px-4 py-3">{lookupLabel(referencesState.references?.subjects.find((option) => option.id === item.subject_id)?.subject_name, item.subject_id, 'Subject')}</td>
                      <td className="px-4 py-3">{teacherLabel(referencesState.references?.teachers.find((option) => option.id === item.teacher_id), item.teacher_id)}</td>
                      <td className="px-4 py-3"><StatusBadge status={item.status} /></td>
                      <td className="px-4 py-3"><div className="flex justify-end gap-2"><button type="button" onClick={() => router.push(`/timetables/${item.id}`)} className="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">View</button><button type="button" onClick={() => router.push(`/timetables/${item.id}/edit`)} className="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">Edit</button><button type="button" disabled={deletingId === item.id} onClick={() => void handleDelete(item)} className="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">{deletingId === item.id ? 'Deleting…' : 'Delete'}</button></div></td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            {!loading && total > 0 && <div className="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between"><span>Page {page} of {totalPages} · {total} total</span><div className="flex gap-2"><button type="button" onClick={() => setPage((current) => Math.max(1, current - 1))} disabled={page <= 1} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Previous</button><button type="button" onClick={() => setPage((current) => Math.min(totalPages, current + 1))} disabled={page >= totalPages} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button></div></div>}
          </div>
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

function Alert({ message, onRetry, subtle = false }: { message: string; onRetry: () => void; subtle?: boolean }) {
  return <div className={`flex flex-col gap-3 rounded-2xl border p-4 text-sm sm:flex-row sm:items-center sm:justify-between ${subtle ? 'border-amber-200 bg-amber-50 text-amber-800' : 'border-red-200 bg-red-50 text-red-700'}`}><span>{message}</span><button type="button" onClick={onRetry} className="rounded-lg border border-current/20 bg-white/70 px-3 py-2 font-semibold hover:bg-white">Retry</button></div>;
}
