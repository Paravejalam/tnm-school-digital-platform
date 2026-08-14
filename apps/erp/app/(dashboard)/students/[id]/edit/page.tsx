'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../../components/Layout';
import ProtectedRoute from '../../../../../components/ProtectedRoute';
import StudentForm, { toFormValues } from '../../../../../components/StudentForm';
import { getStudentById, studentErrorMessage, toStudentRequestError, updateStudent } from '../../../../../services/student';
import type { StudentFormValues, StudentRecord } from '../../../../../types/student';

export default function StudentEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const studentId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const [student, setStudent] = React.useState<StudentRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  const [submitting, setSubmitting] = React.useState(false);

  React.useEffect(() => {
    if (!Number.isInteger(studentId) || studentId < 1) { setError('The student identifier is invalid.'); setLoading(false); return; }
    let mounted = true;
    async function loadStudent() {
      setLoading(true); setError(null);
      try { const result = await getStudentById(studentId); if (mounted) setStudent(result); }
      catch (requestError: unknown) { if (mounted) setError(studentErrorMessage(requestError, 'Unable to load student.')); }
      finally { if (mounted) setLoading(false); }
    }
    void loadStudent(); return () => { mounted = false; };
  }, [studentId]);

  async function handleSubmit(values: StudentFormValues) {
    if (!student) return;
    setSubmitting(true); setError(null); setFieldErrors({});
    try { await updateStudent(student.id, values); router.push(`/students/${student.id}`); }
    catch (requestError: unknown) { const parsed = toStudentRequestError(requestError); setError(studentErrorMessage(requestError, 'Unable to update student.')); setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.']))); }
    finally { setSubmitting(false); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Student management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Edit student</h1><p className="mt-2 text-sm text-slate-600">Update the fields exposed by the current Student response.</p></div><button type="button" onClick={() => router.push(`/students/${student?.id ?? ''}`)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to detail</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading student…</div> : student ? <StudentForm mode="edit" initialValues={toFormValues(student)} serverErrors={fieldErrors} submitLabel="Save changes" isSubmitting={submitting} onSubmit={handleSubmit} /> : null}</div></Layout></ProtectedRoute>;
}
