'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { deleteStudent, getStudentById, studentErrorMessage } from '../../../../services/student';
import type { StudentRecord } from '../../../../types/student';

export default function StudentDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const studentId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const [student, setStudent] = React.useState<StudentRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [deleting, setDeleting] = React.useState(false);

  const loadStudent = React.useCallback(async () => {
    if (!Number.isInteger(studentId) || studentId < 1) { setError('The student identifier is invalid.'); setLoading(false); return; }
    setLoading(true); setError(null);
    try { setStudent(await getStudentById(studentId)); }
    catch (requestError: unknown) { setStudent(null); setError(studentErrorMessage(requestError, 'Unable to load student.')); }
    finally { setLoading(false); }
  }, [studentId]);
  React.useEffect(() => { void loadStudent(); }, [loadStudent]);

  async function handleDelete() {
    if (!student || !window.confirm(`Delete ${[student.first_name, student.last_name].filter(Boolean).join(' ') || 'this student'}? This action cannot be undone.`)) return;
    setDeleting(true); setError(null);
    try { await deleteStudent(student.id); router.push('/students'); }
    catch (requestError: unknown) { setError(studentErrorMessage(requestError, 'Unable to delete the student.')); setDeleting(false); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Student management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Student details</h1><p className="mt-2 text-sm text-slate-600">Review the fields exposed by the current Student service.</p></div><div className="flex flex-wrap gap-2"><button type="button" onClick={() => router.push('/students')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button>{student && <><button type="button" onClick={() => router.push(`/students/${student.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Edit</button><button type="button" disabled={deleting} onClick={() => void handleDelete()} className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-50">{deleting ? 'Deleting…' : 'Delete'}</button></>}</div></div>{error && <div className="flex flex-col gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{error}</span><button type="button" onClick={() => void loadStudent()} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold hover:bg-red-100">Retry</button></div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading student…</div> : student ? <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div className="mb-6 flex flex-wrap items-center justify-between gap-3"><div><p className="text-sm text-slate-500">Student record #{student.id}</p><h2 className="mt-1 text-2xl font-semibold text-slate-900">{[student.first_name, student.last_name].filter(Boolean).join(' ') || 'Unnamed student'}</h2></div><StatusBadge status={student.status} /></div><div className="grid gap-4 md:grid-cols-2"><Info label="Admission number" value={student.admission_number ?? '—'} /><Info label="First name" value={student.first_name ?? '—'} /><Info label="Last name" value={student.last_name ?? '—'} /><Info label="Email" value={student.email ?? '—'} /><Info label="Phone" value={student.phone ?? '—'} /><Info label="Class" value={student.class_name ?? '—'} /><Info label="Section" value={student.section ?? '—'} /><Info label="Status" value={student.status ?? '—'} /></div><p className="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">The current backend response intentionally omits roll number, date of birth, gender, user and enrollment reference identifiers.</p></div> : null}</div></Layout></ProtectedRoute>;
}

function StatusBadge({ status }: { status: string | null | undefined }) { const tone = status === 'active' ? 'bg-emerald-100 text-emerald-700' : status === 'graduated' ? 'bg-violet-100 text-violet-700' : status === 'transferred' ? 'bg-amber-100 text-amber-700' : status === 'withdrawn' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-700'; return <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${tone}`}>{status ?? 'unknown'}</span>; }
function Info({ label, value }: { label: string; value: string }) { return <div className="rounded-xl border border-slate-200 bg-slate-50 p-4"><p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p><p className="mt-2 break-words text-base font-semibold text-slate-900">{value}</p></div>; }
