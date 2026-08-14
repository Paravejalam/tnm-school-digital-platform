'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../components/Layout';
import ProtectedRoute from '../../../components/ProtectedRoute';
import { deleteStudent, listStudents, studentErrorMessage } from '../../../services/student';
import type { StudentListResponse, StudentRecord } from '../../../types/student';

const PER_PAGE = 15;

export default function StudentListPage() {
  const router = useRouter();
  const [items, setItems] = React.useState<StudentRecord[]>([]);
  const [page, setPage] = React.useState(1);
  const [total, setTotal] = React.useState(0);
  const [name, setName] = React.useState('');
  const [admissionNumber, setAdmissionNumber] = React.useState('');
  const [nameInput, setNameInput] = React.useState('');
  const [admissionInput, setAdmissionInput] = React.useState('');
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [success, setSuccess] = React.useState<string | null>(null);
  const [deletingId, setDeletingId] = React.useState<number | null>(null);
  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

  const loadStudents = React.useCallback(async (nextPage = page, nextName = name, nextAdmission = admissionNumber) => {
    setLoading(true); setError(null);
    try {
      const result: StudentListResponse = await listStudents({ page: nextPage, per_page: PER_PAGE, name: nextName, admission_number: nextAdmission });
      setItems(result.items); setTotal(result.pagination.total); setPage(result.pagination.page);
    } catch (requestError: unknown) {
      setItems([]); setTotal(0); setError(studentErrorMessage(requestError, 'Unable to load students.'));
    } finally { setLoading(false); }
  }, [page, name, admissionNumber]);

  React.useEffect(() => { void loadStudents(); }, [loadStudents]);

  function submitSearch(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setName(nameInput.trim()); setAdmissionNumber(admissionInput.trim()); setPage(1); setSuccess(null);
  }

  function clearSearch() { setName(''); setAdmissionNumber(''); setNameInput(''); setAdmissionInput(''); setPage(1); }

  async function handleDelete(student: StudentRecord) {
    const displayName = [student.first_name, student.last_name].filter(Boolean).join(' ') || 'this student';
    if (!window.confirm(`Delete ${displayName}? This action cannot be undone.`)) return;
    setDeletingId(student.id); setError(null); setSuccess(null);
    try {
      await deleteStudent(student.id);
      setSuccess('Student deleted successfully.');
      if (items.length === 1 && page > 1) setPage((current) => current - 1); else await loadStudents();
    } catch (requestError: unknown) { setError(studentErrorMessage(requestError, 'Unable to delete the student.')); }
    finally { setDeletingId(null); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6">
    <div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Student management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Students</h1><p className="mt-2 max-w-2xl text-sm text-slate-600">Search and manage enrolled student records.</p></div><button type="button" onClick={() => router.push('/students/new')} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Add student</button></div>
    <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><form onSubmit={submitSearch} className="grid gap-3 md:grid-cols-[1fr_1fr_auto_auto]"><label className="sr-only" htmlFor="student-name-search">Search by name</label><input id="student-name-search" value={nameInput} onChange={(event) => setNameInput(event.target.value)} placeholder="Search by name" className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-blue-500 focus:bg-white" /><label className="sr-only" htmlFor="student-admission-search">Admission number</label><input id="student-admission-search" value={admissionInput} onChange={(event) => setAdmissionInput(event.target.value)} placeholder="Exact admission number" className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-blue-500 focus:bg-white" /><button type="submit" className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-100">Search</button>{(name || admissionNumber) && <button type="button" onClick={clearSearch} className="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-100">Clear</button>}</form></div>
    {error && <Alert message={error} onRetry={() => void loadStudents()} />}{success && <div className="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">{success}</div>}
    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div className="overflow-x-auto"><table className="min-w-[900px] w-full text-left text-sm text-slate-700"><thead className="bg-slate-50 text-xs uppercase tracking-[0.14em] text-slate-500"><tr><th className="px-4 py-3 font-semibold">Admission</th><th className="px-4 py-3 font-semibold">Name</th><th className="px-4 py-3 font-semibold">Email</th><th className="px-4 py-3 font-semibold">Class</th><th className="px-4 py-3 font-semibold">Section</th><th className="px-4 py-3 font-semibold">Status</th><th className="px-4 py-3 text-right font-semibold">Actions</th></tr></thead><tbody>{loading ? <tr><td colSpan={7} className="px-4 py-12 text-center text-slate-500">Loading students…</td></tr> : items.length === 0 ? <tr><td colSpan={7} className="px-4 py-12 text-center text-slate-500">{name || admissionNumber ? 'No students match the current filters.' : 'No students have been created yet.'}</td></tr> : items.map((student) => <tr key={student.id} className="border-t border-slate-200 hover:bg-slate-50"><td className="px-4 py-3 font-medium text-slate-800">{student.admission_number ?? '—'}</td><td className="px-4 py-3"><button type="button" onClick={() => router.push(`/students/${student.id}`)} className="font-medium text-blue-600 hover:text-blue-700">{[student.first_name, student.last_name].filter(Boolean).join(' ') || 'Unnamed student'}</button></td><td className="px-4 py-3">{student.email || '—'}</td><td className="px-4 py-3">{student.class_name || '—'}</td><td className="px-4 py-3">{student.section || '—'}</td><td className="px-4 py-3"><StatusBadge status={student.status} /></td><td className="px-4 py-3"><div className="flex justify-end gap-2"><button type="button" onClick={() => router.push(`/students/${student.id}`)} className="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">View</button><button type="button" onClick={() => router.push(`/students/${student.id}/edit`)} className="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">Edit</button><button type="button" disabled={deletingId === student.id} onClick={() => void handleDelete(student)} className="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">{deletingId === student.id ? 'Deleting…' : 'Delete'}</button></div></td></tr>)}</tbody></table></div>{!loading && total > 0 && <div className="flex flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 sm:flex-row sm:items-center sm:justify-between"><span>Page {page} of {totalPages} · {total} total</span><div className="flex gap-2"><button type="button" disabled={page <= 1} onClick={() => setPage((current) => Math.max(1, current - 1))} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Previous</button><button type="button" disabled={page >= totalPages} onClick={() => setPage((current) => Math.min(totalPages, current + 1))} className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">Next</button></div></div>}</div>
  </div></Layout></ProtectedRoute>;
}

function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'active' ? 'bg-emerald-100 text-emerald-700' : status === 'graduated' ? 'bg-violet-100 text-violet-700' : status === 'transferred' ? 'bg-amber-100 text-amber-700' : status === 'withdrawn' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }
function Alert({ message, onRetry }: { message: string; onRetry: () => void }) { return <div className="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{message}</span><button type="button" onClick={onRetry} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold hover:bg-red-100">Retry</button></div>; }
