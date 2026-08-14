'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../../components/Layout';
import ProtectedRoute from '../../../../../components/ProtectedRoute';
import HolidayCalendarForm, { toFormValues, useHolidayCalendarSessions } from '../../../../../components/HolidayCalendarForm';
import { getHolidayCalendarById, holidayCalendarErrorMessage, toHolidayCalendarRequestError, updateHolidayCalendar } from '../../../../../services/holidayCalendar';
import type { HolidayCalendarFormValues, HolidayCalendarRecord, HolidayCalendarUpdateValues } from '../../../../../types/holidayCalendar';

export default function HolidayCalendarEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const calendarId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const sessionState = useHolidayCalendarSessions();
  const [item, setItem] = React.useState<HolidayCalendarRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  const [submitting, setSubmitting] = React.useState(false);

  React.useEffect(() => {
    if (!Number.isInteger(calendarId) || calendarId < 1) {
      setError('The holiday calendar identifier is invalid.');
      setLoading(false);
      return;
    }
    let mounted = true;
    async function loadCalendar() {
      setLoading(true);
      setError(null);
      try {
        const result = await getHolidayCalendarById(calendarId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(holidayCalendarErrorMessage(requestError, 'Unable to load holiday calendar.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }
    void loadCalendar();
    return () => { mounted = false; };
  }, [calendarId]);

  async function handleSubmit(values: HolidayCalendarFormValues) {
    if (!item) return;
    setSubmitting(true);
    setError(null);
    setFieldErrors({});
    try {
      const updateValues: HolidayCalendarUpdateValues = {
        holiday_name: values.holiday_name,
        academic_session_id: values.academic_session_id,
        status: values.status,
      };
      await updateHolidayCalendar(item.id, updateValues);
      router.push(`/holiday-calendars/${item.id}`);
    } catch (requestError: unknown) {
      const parsed = toHolidayCalendarRequestError(requestError);
      setError(holidayCalendarErrorMessage(requestError, 'Unable to update holiday calendar.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally {
      setSubmitting(false);
    }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Calendar management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Edit holiday calendar</h1><p className="mt-2 text-sm text-slate-600">Update the holiday calendar record using the backend contract.</p></div><button type="button" onClick={() => router.push(`/holiday-calendars/${item?.id ?? calendarId}`)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to detail</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading holiday calendar…</div> : item ? <HolidayCalendarForm mode="edit" initialValues={toFormValues(item)} sessions={sessionState.sessions} sessionsLoading={sessionState.loading} sessionsError={sessionState.error} onRetrySessions={() => void sessionState.reload()} serverErrors={fieldErrors} submitLabel="Save changes" isSubmitting={submitting} onSubmit={handleSubmit} /> : null}</div></Layout></ProtectedRoute>;
}
