'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../../components/Layout';
import ProtectedRoute from '../../../../../components/ProtectedRoute';
import AttendanceRecordForm, { toAttendanceRecordFormValues } from '../../../../../components/AttendanceRecordForm';
import { attendanceRecordErrorMessage, getAttendanceRecordById, toAttendanceRecordRequestError, updateAttendanceRecord } from '../../../../../services/attendanceRecord';
import type { AttendanceRecordFormValues, AttendanceRecordItem } from '../../../../../types/attendanceRecord';

export default function AttendanceRecordEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const rawId = params?.id;
  const recordId = typeof rawId === 'string' ? Number(rawId) : NaN;
  const [item, setItem] = React.useState<AttendanceRecordItem | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});
  const [submitting, setSubmitting] = React.useState(false);

  React.useEffect(() => {
    if (!Number.isInteger(recordId) || recordId < 1) { setError('The attendance record identifier is invalid.'); setLoading(false); return; }
    let mounted = true;
    async function loadRecord() {
      setLoading(true); setError(null);
      try { const result = await getAttendanceRecordById(recordId); if (mounted) setItem(result); }
      catch (requestError: unknown) { if (mounted) setError(attendanceRecordErrorMessage(requestError, 'Unable to load attendance record.')); }
      finally { if (mounted) setLoading(false); }
    }
    void loadRecord();
    return () => { mounted = false; };
  }, [recordId]);

  async function handleSubmit(values: AttendanceRecordFormValues) {
    if (!item) return;
    setSubmitting(true); setError(null); setFieldErrors({});
    try {
      await updateAttendanceRecord(item.id, { record_name: values.record_name, status: values.status });
      router.push(`/attendance-records/${item.id}`);
    } catch (requestError: unknown) {
      const parsed = toAttendanceRecordRequestError(requestError);
      setError(attendanceRecordErrorMessage(requestError, 'Unable to update attendance record.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally { setSubmitting(false); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Edit attendance record</h1><p className="mt-2 text-sm text-slate-600">Update the fields supported by the AttendanceRecord edit contract.</p></div><button type="button" onClick={() => router.push(`/attendance-records/${item?.id ?? recordId}`)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to detail</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}{loading ? <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading attendance record…</div> : item ? <AttendanceRecordForm mode="edit" initialValues={toAttendanceRecordFormValues(item)} serverErrors={fieldErrors} submitLabel="Save changes" isSubmitting={submitting} onSubmit={handleSubmit} /> : null}</div></Layout></ProtectedRoute>;
}
