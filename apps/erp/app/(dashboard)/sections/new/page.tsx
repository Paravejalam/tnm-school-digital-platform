'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import SectionForm, { useSectionClasses } from '../../../../components/SectionForm';
import { createSection, sectionErrorMessage, toSectionRequestError } from '../../../../services/section';
import type { SectionFormValues } from '../../../../types/section';

export default function SectionCreatePage() {
  const router = useRouter(); const classesState = useSectionClasses(); const [submitting, setSubmitting] = React.useState(false); const [error, setError] = React.useState<string | null>(null); const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  async function handleSubmit(values: SectionFormValues) { setSubmitting(true); setError(null); setFieldErrors({}); try { const created = await createSection(values); router.push(`/sections/${created.id}`); } catch (requestError: unknown) { const parsed = toSectionRequestError(requestError); setError(sectionErrorMessage(requestError, 'Unable to create section.')); setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.']))); } finally { setSubmitting(false); } }
  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Academic management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Create section</h1><p className="mt-2 text-sm text-slate-600">Create a section within an academic class.</p></div><button type="button" onClick={() => router.push('/sections')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}<SectionForm mode="create" classes={classesState.classes} classesLoading={classesState.loading} classesError={classesState.error} onRetryClasses={() => void classesState.reload()} serverErrors={fieldErrors} submitLabel="Create section" isSubmitting={submitting} onSubmit={handleSubmit} /></div></Layout></ProtectedRoute>;
}
