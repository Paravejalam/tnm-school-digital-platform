'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import AcademicClassForm, { useAcademicClassSessions } from '../../../../components/AcademicClassForm';
import { academicClassErrorMessage, createAcademicClass, toAcademicClassRequestError } from '../../../../services/academicClass';
import type { AcademicClassFormValues } from '../../../../types/academicClass';

export default function AcademicClassCreatePage() {
  const router = useRouter();
  const sessionsState = useAcademicClassSessions();
  const [submitting, setSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  async function handleSubmit(values: AcademicClassFormValues) {
    setSubmitting(true); setError(null); setFieldErrors({});
    try { const created = await createAcademicClass(values); router.push(`/classes/${created.id}`); }
    catch (requestError: unknown) { const parsed = toAcademicClassRequestError(requestError); setError(academicClassErrorMessage(requestError, 'Unable to create academic class.')); setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.']))); }
    finally { setSubmitting(false); }
  }
  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Academic management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Create academic class</h1><p className="mt-2 text-sm text-slate-600">Create a class grouping for an academic session.</p></div><button type="button" onClick={() => router.push('/classes')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}<AcademicClassForm mode="create" sessions={sessionsState.sessions} sessionsLoading={sessionsState.loading} sessionsError={sessionsState.error} onRetrySessions={() => void sessionsState.reload()} serverErrors={fieldErrors} submitLabel="Create academic class" isSubmitting={submitting} onSubmit={handleSubmit} /></div></Layout></ProtectedRoute>;
}
