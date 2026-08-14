'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../../components/Layout';
import ProtectedRoute from '../../../../../components/ProtectedRoute';
import PeriodForm, { toFormValues, usePeriodTimetables } from '../../../../../components/PeriodForm';
import { getPeriodById, periodErrorMessage, toPeriodRequestError, updatePeriod } from '../../../../../services/period';
import type { PeriodFormValues, PeriodRecord } from '../../../../../types/period';

export default function PeriodEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const periodId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const timetableState = usePeriodTimetables();
  const [item, setItem] = React.useState<PeriodRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  const [submitting, setSubmitting] = React.useState(false);

  React.useEffect(() => {
    if (!Number.isInteger(periodId) || periodId < 1) {
      setError('The period identifier is invalid.');
      setLoading(false);
      return;
    }
    let mounted = true;
    async function loadPeriod() {
      setLoading(true);
      setError(null);
      try {
        const result = await getPeriodById(periodId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(periodErrorMessage(requestError, 'Unable to load period.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }
    void loadPeriod();
    return () => { mounted = false; };
  }, [periodId]);

  async function handleSubmit(values: PeriodFormValues) {
    if (!item) return;
    setSubmitting(true);
    setError(null);
    setFieldErrors({});
    try {
      await updatePeriod(item.id, { period_name: values.period_name, timetable_id: values.timetable_id, status: values.status });
      router.push(`/periods/${item.id}`);
    } catch (requestError: unknown) {
      const parsed = toPeriodRequestError(requestError);
      setError(periodErrorMessage(requestError, 'Unable to update period.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally {
      setSubmitting(false);
    }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Schedule management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Edit period</h1><p className="mt-2 text-sm text-slate-600">Update the period using the backend-supported response fields.</p></div><button type="button" onClick={() => router.push(`/periods/${item?.id ?? periodId}`)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to detail</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading period…</div> : item ? <PeriodForm mode="edit" initialValues={toFormValues(item)} timetables={timetableState.timetables} timetablesLoading={timetableState.loading} timetablesError={timetableState.error} onRetryTimetables={() => void timetableState.reload()} serverErrors={fieldErrors} submitLabel="Save changes" isSubmitting={submitting} onSubmit={handleSubmit} /> : null}</div></Layout></ProtectedRoute>;
}
