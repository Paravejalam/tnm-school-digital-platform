'use client';

import React from 'react';
import { attendanceRecordErrorMessage, isAttendanceRecordStatus, listAttendanceRecordReferences } from '../services/attendanceRecord';
import type {
  AttendanceRecordFormValues,
  AttendanceRecordItem,
  AttendanceRecordReferenceOptions,
  AttendanceRecordStatus,
} from '../types/attendanceRecord';

const statusOptions: AttendanceRecordStatus[] = ['present', 'absent', 'late', 'excused', 'holiday'];
const emptyValues: AttendanceRecordFormValues = {
  record_name: '',
  attendance_id: '',
  student_id: '',
  status: 'present',
  note: '',
  recorded_by: '',
};

export function toAttendanceRecordFormValues(value: Partial<AttendanceRecordFormValues> | Partial<AttendanceRecordItem> | null | undefined): AttendanceRecordFormValues {
  const formValue = value as Partial<AttendanceRecordFormValues> | undefined;
  return {
    record_name: String(value?.record_name ?? ''),
    attendance_id: String(value?.attendance_id ?? ''),
    student_id: String(value?.student_id ?? ''),
    status: isAttendanceRecordStatus(String(value?.status ?? '')) ? String(value?.status) as AttendanceRecordStatus : 'present',
    note: String(formValue?.note ?? ''),
    recorded_by: String(formValue?.recorded_by ?? ''),
  };
}

export function validateAttendanceRecordForm(values: AttendanceRecordFormValues, references: AttendanceRecordReferenceOptions | null, mode: 'create' | 'edit'): Record<string, string> {
  const errors: Record<string, string> = {};
  if (!values.record_name.trim()) errors.record_name = 'Attendance record name is required.';
  if (!validId(values.attendance_id) && mode === 'create') errors.attendance_id = 'Select a valid attendance entry.';
  if (!validId(values.student_id) && mode === 'create') errors.student_id = 'Select a valid student.';
  if (!statusOptions.includes(values.status)) errors.status = 'Status must be present, absent, late, excused, or holiday.';

  if (references) {
    if (mode === 'create' && validId(values.attendance_id) && !references.attendance.some((item) => item.id === Number(values.attendance_id))) errors.attendance_id = 'Select a valid attendance entry.';
    if (mode === 'create' && validId(values.student_id) && !references.students.some((item) => item.id === Number(values.student_id))) errors.student_id = 'Select a valid student.';
  }
  return errors;
}

export function useAttendanceRecordReferences() {
  const [references, setReferences] = React.useState<AttendanceRecordReferenceOptions | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const loadReferences = React.useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setReferences(await listAttendanceRecordReferences());
    } catch (requestError: unknown) {
      setReferences(null);
      setError(attendanceRecordErrorMessage(requestError, 'Unable to load attendance record selectors.'));
    } finally {
      setLoading(false);
    }
  }, []);

  React.useEffect(() => { void loadReferences(); }, [loadReferences]);
  return { references, loading, error, reload: loadReferences };
}

export default function AttendanceRecordForm({
  mode,
  initialValues,
  references,
  referencesLoading,
  referencesError,
  onRetryReferences,
  serverErrors = {},
  submitLabel,
  isSubmitting,
  onSubmit,
}: {
  mode: 'create' | 'edit';
  initialValues?: Partial<AttendanceRecordFormValues> | Partial<AttendanceRecordItem> | null;
  references?: AttendanceRecordReferenceOptions | null;
  referencesLoading?: boolean;
  referencesError?: string | null;
  onRetryReferences?: () => void;
  serverErrors?: Record<string, string>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: AttendanceRecordFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<AttendanceRecordFormValues>(() => toAttendanceRecordFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    setValues(toAttendanceRecordFormValues(initialValues ?? emptyValues));
    setErrors({});
  }, [initialValues]);

  function updateField(field: keyof AttendanceRecordFormValues, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors = validateAttendanceRecordForm(values, references ?? null, mode);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0 || (mode === 'create' && !references)) return;
    await onSubmit(values);
  }

  const formErrors = { ...serverErrors, ...errors };
  const disabled = isSubmitting || (mode === 'create' && (referencesLoading || !references));

  return <form onSubmit={handleSubmit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    {referencesError && <div className="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{referencesError}</span>{onRetryReferences && <button type="button" onClick={onRetryReferences} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold text-red-700 hover:bg-red-100">Retry selectors</button>}</div>}
    <div className="grid gap-5 md:grid-cols-2">
      <Field id="record_name" label="Record name" error={formErrors.record_name}><input id="record_name" value={values.record_name} onChange={(event) => updateField('record_name', event.target.value)} aria-invalid={Boolean(formErrors.record_name)} disabled={isSubmitting} maxLength={200} className={inputClass(Boolean(formErrors.record_name))} /></Field>
      <Field id="status" label="Status" error={formErrors.status}><select id="status" value={values.status} onChange={(event) => updateField('status', event.target.value)} aria-invalid={Boolean(formErrors.status)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.status))}>{statusOptions.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}</select></Field>
      {mode === 'create' && <>
        <Field id="attendance_id" label="Attendance entry" error={formErrors.attendance_id}><select id="attendance_id" value={values.attendance_id} onChange={(event) => updateField('attendance_id', event.target.value)} aria-invalid={Boolean(formErrors.attendance_id)} disabled={disabled} className={inputClass(Boolean(formErrors.attendance_id))}><option value="">{referencesLoading ? 'Loading attendance…' : 'Select attendance entry'}</option>{references?.attendance.map((item) => <option key={item.id} value={item.id}>{attendanceLabel(item, references.students)}</option>)}</select></Field>
        <Field id="student_id" label="Student" error={formErrors.student_id}><select id="student_id" value={values.student_id} onChange={(event) => updateField('student_id', event.target.value)} aria-invalid={Boolean(formErrors.student_id)} disabled={disabled} className={inputClass(Boolean(formErrors.student_id))}><option value="">{referencesLoading ? 'Loading students…' : 'Select student'}</option>{references?.students.map((item) => <option key={item.id} value={item.id}>{studentLabel(item)}</option>)}</select></Field>
        <Field id="note" label="Note (optional)" error={formErrors.note}><textarea id="note" value={values.note} onChange={(event) => updateField('note', event.target.value)} aria-invalid={Boolean(formErrors.note)} disabled={isSubmitting} rows={3} className={inputClass(Boolean(formErrors.note))} /></Field>
      </>}
    </div>
    {mode === 'edit' ? <p className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">The AttendanceRecord read response does not expose note or recorded-by metadata. Edit sends only the record name and status so those backend values remain preserved.</p> : <p className="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">The signed-in user is recorded when available. Attendance record notes are accepted by the backend but are not returned in read responses.</p>}
    <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-xs text-slate-500">Fields follow the AttendanceRecord API validation contract.</p><button type="submit" disabled={disabled} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">{isSubmitting ? 'Saving…' : submitLabel}</button></div>
  </form>;
}

function validId(value: string): boolean { const number = Number(value); return Number.isInteger(number) && number > 0; }
function inputClass(hasError: boolean): string { return `w-full rounded-xl border ${hasError ? 'border-red-300' : 'border-slate-200'} bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white disabled:cursor-not-allowed disabled:opacity-60`; }
function statusLabel(status: AttendanceRecordStatus): string { return status.charAt(0).toUpperCase() + status.slice(1); }
function studentLabel(item: AttendanceRecordReferenceOptions['students'][number]): string { const name = [item.first_name, item.last_name].filter(Boolean).join(' ').trim(); const base = name || item.admission_number || `Student #${item.id}`; return item.admission_number ? `${base} · ${item.admission_number}` : base; }
function attendanceLabel(item: AttendanceRecordReferenceOptions['attendance'][number], students: AttendanceRecordReferenceOptions['students']): string { const student = students.find((entry) => entry.id === item.student_id); const studentName = student ? studentLabel(student) : `Student #${item.student_id ?? '—'}`; return `${item.attendance_date ?? 'Undated'} · ${studentName} · ${item.status ?? 'unknown'} · #${item.id}`; }
function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) { return <div><label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label><div className="mt-2">{children}</div>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</div>; }
