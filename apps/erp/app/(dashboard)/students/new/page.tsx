'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import StudentForm, { useStudentReferences } from '../../../../components/StudentForm';
import { createStudent, studentErrorMessage, toStudentRequestError } from '../../../../services/student';
import type { StudentFormValues } from '../../../../types/student';

export default function StudentCreatePage() {
  const router = useRouter();
  const referencesState = useStudentReferences();
  const [submitting, setSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});

  async function handleSubmit(values: StudentFormValues) {
    setSubmitting(true); setError(null); setFieldErrors({});
    try { const created = await createStudent(values); router.push(`/students/${created.id}`); }
    catch (requestError: unknown) { const parsed = toStudentRequestError(requestError); setError(studentErrorMessage(requestError, 'Unable to create student.')); setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.']))); }
    finally { setSubmitting(false); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Student management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Create student</h1><p className="mt-2 text-sm text-slate-600">Add a student using the fields supported by the Student API.</p></div><button type="button" onClick={() => router.push('/students')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}<StudentForm mode="create" references={referencesState.references} referencesLoading={referencesState.loading} referencesError={referencesState.error} onRetryReferences={() => void referencesState.reload()} serverErrors={fieldErrors} submitLabel="Create student" isSubmitting={submitting} onSubmit={handleSubmit} /></div></Layout></ProtectedRoute>;
}
