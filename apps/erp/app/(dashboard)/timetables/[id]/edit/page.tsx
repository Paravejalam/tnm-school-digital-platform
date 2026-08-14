'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../../components/Layout';
import ProtectedRoute from '../../../../../components/ProtectedRoute';
import TimetableForm, { toFormValues, useTimetableReferences } from '../../../../../components/TimetableForm';
import { getTimetableById, timetableErrorMessage, toTimetableRequestError, updateTimetable } from '../../../../../services/timetable';
import type { TimetableFormValues, TimetableRecord } from '../../../../../types/timetable';

export default function TimetableEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const timetableId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const referencesState = useTimetableReferences();
  const [item, setItem] = React.useState<TimetableRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  const [submitting, setSubmitting] = React.useState(false);

  React.useEffect(() => {
    if (!Number.isInteger(timetableId) || timetableId < 1) {
      setError('The timetable identifier is invalid.');
      setLoading(false);
      return;
    }

    let mounted = true;
    async function loadTimetable() {
      setLoading(true);
      setError(null);
      try {
        const result = await getTimetableById(timetableId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(timetableErrorMessage(requestError, 'Unable to load timetable.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }

    void loadTimetable();
    return () => { mounted = false; };
  }, [timetableId]);

  async function handleSubmit(values: TimetableFormValues) {
    if (!item) return;

    setSubmitting(true);
    setError(null);
    setFieldErrors({});
    try {
      await updateTimetable(item.id, values);
      router.push(`/timetables/${item.id}`);
    } catch (requestError: unknown) {
      const parsed = toTimetableRequestError(requestError);
      setError(timetableErrorMessage(requestError, 'Unable to update timetable.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <ProtectedRoute>
      <Layout>
        <div className="space-y-6">
          <div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Schedule management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Edit timetable</h1><p className="mt-2 text-sm text-slate-600">Update the assignment while keeping the existing backend identifiers intact.</p></div>
            <button type="button" onClick={() => router.push(`/timetables/${item?.id ?? timetableId}`)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to detail</button>
          </div>

          {error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}

          {loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading timetable…</div> : item ? <TimetableForm initialValues={toFormValues(item)} references={referencesState.references} referencesLoading={referencesState.loading} referencesError={referencesState.error} onRetryReferences={() => void referencesState.reload()} serverErrors={fieldErrors} submitLabel="Save changes" isSubmitting={submitting} onSubmit={handleSubmit} /> : null}
        </div>
      </Layout>
    </ProtectedRoute>
  );
}
