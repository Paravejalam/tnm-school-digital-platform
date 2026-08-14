'use client';

import React from 'react';
import { attendanceErrorMessage, isAttendanceStatus, listAttendanceReferences } from '../services/attendance';
import type {
  AttendanceFormValues,
  AttendanceRecord,
  AttendanceReferenceOptions,
  AttendanceStatus,
} from '../types/attendance';

const statusOptions: AttendanceStatus[] = ['present', 'absent', 'late', 'excused', 'holiday'];
const emptyValues: AttendanceFormValues = {
  attendance_date: '',
  academic_session_id: '',
  class_id: '',
  section_id: '',
  student_id: '',
  status: 'present',
  remarks: '',
  marked_by: '',
};

export function toAttendanceFormValues(value: Partial<AttendanceFormValues> | Partial<AttendanceRecord> | null | undefined): AttendanceFormValues {
  return {
    attendance_date: String(value?.attendance_date ?? ''),
    academic_session_id: String(value?.academic_session_id ?? ''),
    class_id: String(value?.class_id ?? ''),
    section_id: String(value?.section_id ?? ''),
    student_id: String(value?.student_id ?? ''),
    status: isAttendanceStatus(String(value?.status ?? '')) ? String(value?.status) as AttendanceStatus : 'present',
    remarks: String(value?.remarks ?? ''),
    marked_by: String(value?.marked_by ?? ''),
  };
}

export function validateAttendanceForm(values: AttendanceFormValues, references: AttendanceReferenceOptions | null): Record<string, string> {
  const errors: Record<string, string> = {};
  if (!values.attendance_date.trim()) errors.attendance_date = 'Attendance date is required.';
  if (!validId(values.academic_session_id)) errors.academic_session_id = 'Select a valid academic session.';
  if (!validId(values.class_id)) errors.class_id = 'Select a valid class.';
  if (!validId(values.section_id)) errors.section_id = 'Select a valid section.';
  if (!validId(values.student_id)) errors.student_id = 'Select a valid student.';
  if (!statusOptions.includes(values.status)) errors.status = 'Status must be present, absent, late, excused, or holiday.';

  if (references) {
    if (validId(values.academic_session_id) && !references.sessions.some((item) => item.id === Number(values.academic_session_id))) errors.academic_session_id = 'Select a valid academic session.';
    if (validId(values.class_id) && !references.classes.some((item) => item.id === Number(values.class_id))) errors.class_id = 'Select a valid class.';
    if (validId(values.section_id) && !references.sections.some((item) => item.id === Number(values.section_id))) errors.section_id = 'Select a valid section.';
    if (validId(values.student_id) && !references.students.some((item) => item.id === Number(values.student_id))) errors.student_id = 'Select a valid student.';
  }

  return errors;
}

export function useAttendanceReferences() {
  const [references, setReferences] = React.useState<AttendanceReferenceOptions | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const loadReferences = React.useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setReferences(await listAttendanceReferences());
    } catch (requestError: unknown) {
      setReferences(null);
      setError(attendanceErrorMessage(requestError, 'Unable to load attendance selectors.'));
    } finally {
      setLoading(false);
    }
  }, []);

  React.useEffect(() => { void loadReferences(); }, [loadReferences]);
  return { references, loading, error, reload: loadReferences };
}

export default function AttendanceForm({
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
  initialValues?: Partial<AttendanceFormValues> | Partial<AttendanceRecord> | null;
  references: AttendanceReferenceOptions | null;
  referencesLoading: boolean;
  referencesError: string | null;
  onRetryReferences: () => void;
  serverErrors?: Record<string, string>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: AttendanceFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<AttendanceFormValues>(() => toAttendanceFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    setValues(toAttendanceFormValues(initialValues ?? emptyValues));
    setErrors({});
  }, [initialValues]);

  function updateField(field: keyof AttendanceFormValues, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  function updateSession(value: string) {
    setValues((current) => ({ ...current, academic_session_id: value, class_id: '', section_id: '', student_id: '' }));
    setErrors((current) => ({ ...current, academic_session_id: '', class_id: '', section_id: '', student_id: '' }));
  }

  function updateClass(value: string) {
    setValues((current) => ({ ...current, class_id: value, section_id: '', student_id: '' }));
    setErrors((current) => ({ ...current, class_id: '', section_id: '', student_id: '' }));
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors = validateAttendanceForm(values, references);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0 || !references) return;
    await onSubmit(values);
  }

  const formErrors = { ...serverErrors, ...errors };
  const selectedSessionId = Number(values.academic_session_id);
  const selectedClassId = Number(values.class_id);
  const classOptions = references?.classes.filter((item) => !values.academic_session_id || item.academic_session_id === selectedSessionId) ?? [];
  const sectionOptions = references?.sections.filter((item) => item.class_id === selectedClassId) ?? [];
  const disabled = isSubmitting || referencesLoading || !references;

  return (
    <form onSubmit={handleSubmit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      {referencesError && <div className="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{referencesError}</span><button type="button" onClick={onRetryReferences} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold text-red-700 hover:bg-red-100">Retry selectors</button></div>}

      <div className="grid gap-5 md:grid-cols-2">
        <Field id="attendance_date" label="Attendance date" error={formErrors.attendance_date}>
          <input id="attendance_date" type="date" value={values.attendance_date} onChange={(event) => updateField('attendance_date', event.target.value)} aria-invalid={Boolean(formErrors.attendance_date)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.attendance_date))} />
        </Field>

        <Field id="status" label="Status" error={formErrors.status}>
          <select id="status" value={values.status} onChange={(event) => updateField('status', event.target.value)} aria-invalid={Boolean(formErrors.status)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.status))}>{statusOptions.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}</select>
        </Field>

        <Field id="academic_session_id" label="Academic session" error={formErrors.academic_session_id}>
          <select id="academic_session_id" value={values.academic_session_id} onChange={(event) => updateSession(event.target.value)} aria-invalid={Boolean(formErrors.academic_session_id)} disabled={disabled} className={inputClass(Boolean(formErrors.academic_session_id))}>
            <option value="">{referencesLoading ? 'Loading sessions…' : 'Select academic session'}</option>
            {references?.sessions.map((item) => <option key={item.id} value={item.id}>{item.session_name ?? `Session #${item.id}`}</option>)}
          </select>
        </Field>

        <Field id="class_id" label="Class" error={formErrors.class_id}>
          <select id="class_id" value={values.class_id} onChange={(event) => updateClass(event.target.value)} aria-invalid={Boolean(formErrors.class_id)} disabled={disabled || !values.academic_session_id} className={inputClass(Boolean(formErrors.class_id))}>
            <option value="">{!values.academic_session_id ? 'Select an academic session first' : 'Select class'}</option>
            {classOptions.map((item) => <option key={item.id} value={item.id}>{classLabel(item)}</option>)}
          </select>
        </Field>

        <Field id="section_id" label="Section" error={formErrors.section_id}>
          <select id="section_id" value={values.section_id} onChange={(event) => updateField('section_id', event.target.value)} aria-invalid={Boolean(formErrors.section_id)} disabled={disabled || !values.class_id} className={inputClass(Boolean(formErrors.section_id))}>
            <option value="">{!values.class_id ? 'Select a class first' : 'Select section'}</option>
            {sectionOptions.map((item) => <option key={item.id} value={item.id}>{sectionLabel(item)}</option>)}
          </select>
        </Field>

        <Field id="student_id" label="Student" error={formErrors.student_id}>
          <select id="student_id" value={values.student_id} onChange={(event) => updateField('student_id', event.target.value)} aria-invalid={Boolean(formErrors.student_id)} disabled={disabled} className={inputClass(Boolean(formErrors.student_id))}>
            <option value="">{referencesLoading ? 'Loading students…' : 'Select student'}</option>
            {references?.students.map((item) => <option key={item.id} value={item.id}>{studentLabel(item)}</option>)}
          </select>
        </Field>

        {mode === 'create' && <Field id="remarks" label="Remarks (optional)" error={formErrors.remarks}>
          <textarea id="remarks" value={values.remarks} onChange={(event) => updateField('remarks', event.target.value)} aria-invalid={Boolean(formErrors.remarks)} disabled={isSubmitting} rows={3} className={inputClass(Boolean(formErrors.remarks))} />
        </Field>}
      </div>

      {mode === 'create' ? <p className="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-600">The signed-in user is recorded as the marker when available. The backend read response does not expose remarks or marker details after saving.</p> : <p className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Remarks and marker details are not returned by the Attendance API. Edit uses only exposed fields so existing backend-only values remain preserved.</p>}

      <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-xs text-slate-500">Required references must be selected from the current backend records.</p><button type="submit" disabled={disabled} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">{isSubmitting ? 'Saving…' : submitLabel}</button></div>
    </form>
  );
}

function validId(value: string): boolean {
  const number = Number(value);
  return Number.isInteger(number) && number > 0;
}

function inputClass(hasError: boolean): string {
  return `w-full rounded-xl border ${hasError ? 'border-red-300' : 'border-slate-200'} bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white disabled:cursor-not-allowed disabled:opacity-60`;
}

function statusLabel(status: AttendanceStatus): string {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function classLabel(item: AttendanceReferenceOptions['classes'][number]): string {
  return item.code ? `${item.class_name ?? `Class #${item.id}`} (${item.code})` : item.class_name ?? `Class #${item.id}`;
}

function sectionLabel(item: AttendanceReferenceOptions['sections'][number]): string {
  return item.code ? `${item.section_name ?? `Section #${item.id}`} (${item.code})` : item.section_name ?? `Section #${item.id}`;
}

function studentLabel(item: AttendanceReferenceOptions['students'][number]): string {
  const name = [item.first_name, item.last_name].filter(Boolean).join(' ').trim();
  const base = name || item.admission_number || `Student #${item.id}`;
  return item.admission_number ? `${base} · ${item.admission_number}` : base;
}

function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) {
  return <div><label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label><div className="mt-2">{children}</div>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</div>;
}
