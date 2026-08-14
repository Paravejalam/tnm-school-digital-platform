'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../../components/Layout';
import ProtectedRoute from '../../../../../components/ProtectedRoute';
import AcademicSessionForm, { toAcademicSessionFormValues } from '../../../../../components/AcademicSessionForm';
import { academicSessionErrorMessage, getAcademicSessionById, toAcademicSessionRequestError, updateAcademicSession } from '../../../../../services/academicSession';
import type { AcademicSessionFormValues, AcademicSessionRecord } from '../../../../../types/academicSession';

export default function AcademicSessionEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const sessionId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const [item, setItem] = React.useState<AcademicSessionRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  const [submitting, setSubmitting] = React.useState(false);

  React.useEffect(() => {
    if (!Number.isInteger(sessionId) || sessionId < 1) {
      setError('The academic session identifier is invalid.');
      setLoading(false);
      return;
    }
    let mounted = true;
    async function loadSession() {
      setLoading(true);
      setError(null);
      try {
        const result = await getAcademicSessionById(sessionId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(academicSessionErrorMessage(requestError, 'Unable to load academic session.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }
    void loadSession();
    return () => { mounted = false; };
  }, [sessionId]);

  async function handleSubmit(values: AcademicSessionFormValues) {
    if (!item) return;
    setSubmitting(true);
    setError(null);
    setFieldErrors({});
    try {
      await updateAcademicSession(item.id, { session_name: values.session_name, status: values.status });
      router.push(`/academic-sessions/${item.id}`);
    } catch (requestError: unknown) {
      const parsed = toAcademicSessionRequestError(requestError);
      setError(academicSessionErrorMessage(requestError, 'Unable to update academic session.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally {
      setSubmitting(false);
    }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Academic management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Edit academic session</h1><p className="mt-2 text-sm text-slate-600">Update the fields exposed by the Academic Session read contract.</p></div><button type="button" onClick={() => router.push(`/academic-sessions/${item?.id ?? sessionId}`)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to detail</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading academic session…</div> : item ? <AcademicSessionForm mode="edit" initialValues={toAcademicSessionFormValues(item)} serverErrors={fieldErrors} submitLabel="Save changes" isSubmitting={submitting} onSubmit={handleSubmit} /> : null}</div></Layout></ProtectedRoute>;
}
