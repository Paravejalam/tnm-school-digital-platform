'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import AcademicSessionForm from '../../../../components/AcademicSessionForm';
import { academicSessionErrorMessage, createAcademicSession, toAcademicSessionRequestError } from '../../../../services/academicSession';
import type { AcademicSessionFormValues } from '../../../../types/academicSession';

export default function AcademicSessionCreatePage() {
  const router = useRouter();
  const [submitting, setSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});

  async function handleSubmit(values: AcademicSessionFormValues) {
    setSubmitting(true);
    setError(null);
    setFieldErrors({});
    try {
      const created = await createAcademicSession(values);
      router.push(`/academic-sessions/${created.id}`);
    } catch (requestError: unknown) {
      const parsed = toAcademicSessionRequestError(requestError);
      setError(academicSessionErrorMessage(requestError, 'Unable to create academic session.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally {
      setSubmitting(false);
    }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Academic management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Create academic session</h1><p className="mt-2 text-sm text-slate-600">Create an academic session using the fields required by the backend.</p></div><button type="button" onClick={() => router.push('/academic-sessions')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}<AcademicSessionForm mode="create" serverErrors={fieldErrors} submitLabel="Create academic session" isSubmitting={submitting} onSubmit={handleSubmit} /></div></Layout></ProtectedRoute>;
}
