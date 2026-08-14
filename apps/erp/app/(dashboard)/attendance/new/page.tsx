'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import AttendanceForm, { useAttendanceReferences } from '../../../../components/AttendanceForm';
import { attendanceErrorMessage, createAttendance, toAttendanceRequestError } from '../../../../services/attendance';
import { useAuth } from '../../../../hooks/useAuth';
import type { AttendanceFormValues } from '../../../../types/attendance';

export default function AttendanceCreatePage() {
  const router = useRouter();
  const { user } = useAuth();
  const referencesState = useAttendanceReferences();
  const [submitting, setSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = React.useState<Record<string, string>>({});

  async function handleSubmit(values: AttendanceFormValues) {
    setSubmitting(true);
    setError(null);
    setFieldErrors({});
    try {
      const created = await createAttendance({ ...values, marked_by: user ? String(user.id) : '' });
      router.push(`/attendance/${created.id}`);
    } catch (requestError: unknown) {
      const parsed = toAttendanceRequestError(requestError);
      setError(attendanceErrorMessage(requestError, 'Unable to create attendance record.'));
      setFieldErrors(Object.fromEntries(Object.entries(parsed.validation ?? {}).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])));
    } finally {
      setSubmitting(false);
    }
  }

  return <ProtectedRoute><Layout><div className="space-y-6"><div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between"><div><p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance management</p><h1 className="mt-2 text-3xl font-semibold text-slate-900">Create attendance</h1><p className="mt-2 text-sm text-slate-600">Record a student attendance status for a date and academic context.</p></div><button type="button" onClick={() => router.push('/attendance')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Back to list</button></div>{error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}<AttendanceForm mode="create" references={referencesState.references} referencesLoading={referencesState.loading} referencesError={referencesState.error} onRetryReferences={() => void referencesState.reload()} serverErrors={fieldErrors} submitLabel="Create attendance" isSubmitting={submitting} onSubmit={handleSubmit} /></div></Layout></ProtectedRoute>;
}
