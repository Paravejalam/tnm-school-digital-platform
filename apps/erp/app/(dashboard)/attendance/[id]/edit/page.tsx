'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../../components/Layout';
import ProtectedRoute from '../../../../../components/ProtectedRoute';
import AttendanceForm, { toAttendanceFormValues, useAttendanceReferences } from '../../../../../components/AttendanceForm';
import { attendanceErrorMessage, getAttendanceById, toAttendanceRequestError, updateAttendance } from '../../../../../services/attendance';
import type { AttendanceFormValues, AttendanceRecord } from '../../../../../types/attendance';

export default function AttendanceEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const attendanceId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const referencesState = useAttendanceReferences();
  const [item, setItem] = React.useState<AttendanceRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  const [submitting, setSubmitting] = React.useState(false);

  React.useEffect(() => {
    if (!Number.isInteger(attendanceId) || attendanceId < 1) {
      setError('The attendance identifier is invalid.');
      setLoading(false);
      return;
    }
    let mounted = true;
    async function loadAttendance() {
      setLoading(true);
      setError(null);
      try {
        const result = await getAttendanceById(attendanceId);
        if (mounted) setItem(result);
      } catch (requestError: unknown) {
        if (mounted) setError(attendanceErrorMessage(requestError, 'Unable to load attendance record.'));
      } finally {
        if (mounted) setLoading(false);
      }
    }
    void loadAttendance();
    return () => { mounted = false; };
  }, [attendanceId]);

  async function handleSubmit(values: AttendanceFormValues) {
    if (!item) return;
    setSubmitting(true);
    setError(null);
    setFieldErrors({});
    try {
      await updateAttendance(item.id, { attendance_date: values.attendance_date, academic_session_id: values.academic_session_id, class_id: values.class_id, section_id: values.section_id, student_id: values.student_id, status: values.status });
      router.push(`/attendance/${item.id}`);
    } catch (requestError: unknown) {
      const parsed = toAttendanceRequestError(requestError);
      setError(attendanceErrorMessage(requestError, 'Unable to update attendance record.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally {
      setSubmitting(false);
    }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Edit attendance</h1><p className="mt-2 text-sm text-slate-600">Update the exposed Attendance fields without overwriting backend-only values.</p></div><button type="button" onClick={() => router.push(`/attendance/${item?.id ?? attendanceId}`)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to detail</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading attendance…</div> : item ? <AttendanceForm mode="edit" initialValues={toAttendanceFormValues(item)} references={referencesState.references} referencesLoading={referencesState.loading} referencesError={referencesState.error} onRetryReferences={() => void referencesState.reload()} serverErrors={fieldErrors} submitLabel="Save changes" isSubmitting={submitting} onSubmit={handleSubmit} /> : null}</div></Layout></ProtectedRoute>;
}
