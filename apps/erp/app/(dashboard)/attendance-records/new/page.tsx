'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import AttendanceRecordForm, { useAttendanceRecordReferences } from '../../../../components/AttendanceRecordForm';
import { attendanceRecordErrorMessage, createAttendanceRecord, toAttendanceRecordRequestError } from '../../../../services/attendanceRecord';
import { useAuth } from '../../../../hooks/useAuth';
import type { AttendanceRecordFormValues } from '../../../../types/attendanceRecord';

export default function AttendanceRecordCreatePage() {
  const router = useRouter();
  const { user } = useAuth();
  const referencesState = useAttendanceRecordReferences();
  const [submitting, setSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});

  async function handleSubmit(values: AttendanceRecordFormValues) {
    setSubmitting(true); setError(null); setFieldErrors({});
    try {
      const created = await createAttendanceRecord({ ...values, recorded_by: user ? String(user.id) : '' });
      router.push(`/attendance-records/${created.id}`);
    } catch (requestError: unknown) {
      const parsed = toAttendanceRecordRequestError(requestError);
      setError(attendanceRecordErrorMessage(requestError, 'Unable to create attendance record.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally { setSubmitting(false); }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Create attendance record</h1><p className="mt-2 text-sm text-slate-600">Create a detailed attendance entry against an existing attendance record.</p></div><button type="button" onClick={() => router.push('/attendance-records')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}<AttendanceRecordForm mode="create" references={referencesState.references} referencesLoading={referencesState.loading} referencesError={referencesState.error} onRetryReferences={() => void referencesState.reload()} serverErrors={fieldErrors} submitLabel="Create attendance record" isSubmitting={submitting} onSubmit={handleSubmit} /></div></Layout></ProtectedRoute>;
}
