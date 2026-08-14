'use client';

import React from 'react';
import { listStudentReferences } from '../services/student';
import type {
  StudentClassOption,
  StudentCreateValues,
  StudentFormValues,
  StudentGender,
  StudentRecord,
  StudentReferenceOptions,
  StudentSectionOption,
  StudentSessionOption,
  StudentStatus,
} from '../types/student';

export const studentStatuses: StudentStatus[] = ['active', 'inactive', 'graduated', 'transferred', 'withdrawn'];
export const studentGenders: StudentGender[] = ['male', 'female', 'other', 'prefer_not_to_say'];

const emptyValues: StudentFormValues = {
  admission_number: '', first_name: '', last_name: '', email: '', phone: '', class_name: '', section: '', status: 'active',
  roll_number: '', date_of_birth: '', gender: '', academic_session_id: '', class_id: '', section_id: '',
};

export function toFormValues(value: Partial<StudentFormValues> | Partial<StudentRecord> | null | undefined): StudentFormValues {
  return {
    admission_number: String(value?.admission_number ?? ''),
    first_name: String(value?.first_name ?? ''),
    last_name: String(value?.last_name ?? ''),
    email: String(value?.email ?? ''),
    phone: String(value?.phone ?? ''),
    class_name: String(value?.class_name ?? ''),
    section: String(value?.section ?? ''),
    status: studentStatuses.includes(value?.status as StudentStatus) ? value?.status as StudentStatus : 'active',
    roll_number: String((value as Partial<StudentFormValues> | undefined)?.roll_number ?? ''),
    date_of_birth: String((value as Partial<StudentFormValues> | undefined)?.date_of_birth ?? ''),
    gender: studentGenders.includes((value as Partial<StudentFormValues> | undefined)?.gender as StudentGender) ? (value as Partial<StudentFormValues>).gender as StudentGender : '',
    academic_session_id: String((value as Partial<StudentFormValues> | undefined)?.academic_session_id ?? ''),
    class_id: String((value as Partial<StudentFormValues> | undefined)?.class_id ?? ''),
    section_id: String((value as Partial<StudentFormValues> | undefined)?.section_id ?? ''),
  };
}

export function validateStudentForm(values: StudentFormValues, mode: 'create' | 'edit' = 'create', references?: StudentReferenceOptions): Record<string, string> {
  const errors: Record<string, string> = {};
  if (!values.admission_number.trim()) errors.admission_number = 'Admission number is required.';
  if (!values.first_name.trim()) errors.first_name = 'First name is required.';
  if (!values.last_name.trim()) errors.last_name = 'Last name is required.';
  if (mode === 'create' && !values.class_name.trim()) errors.class_name = 'Class name is required.';
  if (values.email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email.trim())) errors.email = 'Email must be valid.';
  if (!studentStatuses.includes(values.status)) errors.status = 'Status is invalid.';
  if (values.gender && !studentGenders.includes(values.gender)) errors.gender = 'Gender is invalid.';
  if (mode === 'create' && references) {
    if (values.academic_session_id && !references.sessions.some((item) => item.id === Number(values.academic_session_id))) errors.academic_session_id = 'Select a valid academic session.';
    if (values.class_id && !references.classes.some((item) => item.id === Number(values.class_id))) errors.class_id = 'Select a valid academic class.';
    if (values.section_id && !references.sections.some((item) => item.id === Number(values.section_id))) errors.section_id = 'Select a valid section.';
  }
  return errors;
}

export function useStudentReferences() {
  const [references, setReferences] = React.useState<StudentReferenceOptions | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const reload = React.useCallback(async () => {
    setLoading(true); setError(null);
    try { setReferences(await listStudentReferences()); } catch (requestError: unknown) { setError(requestError instanceof Error ? requestError.message : 'Reference options could not be loaded.'); }
    finally { setLoading(false); }
  }, []);
  React.useEffect(() => { void reload(); }, [reload]);
  return { references, loading, error, reload };
}

interface StudentFormProps {
  mode: 'create' | 'edit';
  initialValues?: Partial<StudentFormValues> | Partial<StudentRecord> | null;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: StudentFormValues) => Promise<void> | void;
  references?: StudentReferenceOptions | null;
  referencesLoading?: boolean;
  referencesError?: string | null;
  onRetryReferences?: () => void;
  serverErrors?: Record<string, string>;
}

export default function StudentForm({ mode, initialValues, submitLabel, isSubmitting, onSubmit, references, referencesLoading = false, referencesError = null, onRetryReferences, serverErrors = {} }: StudentFormProps) {
  const [values, setValues] = React.useState<StudentFormValues>(() => toFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => { setValues(toFormValues(initialValues ?? emptyValues)); setErrors({}); }, [initialValues]);

  function updateField(field: keyof StudentFormValues, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors = validateStudentForm(values, mode, references ?? undefined);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;
    await onSubmit(values);
  }

  const fieldError = (field: string) => errors[field] || serverErrors[field];
  const classes = references?.classes ?? [];
  const sections = references?.sections ?? [];
  const sessions = references?.sessions ?? [];
  const selectedSession = values.academic_session_id ? Number(values.academic_session_id) : null;
  const selectedClass = values.class_id ? Number(values.class_id) : null;
  const visibleClasses = selectedSession === null ? classes : classes.filter((item) => item.academic_session_id === selectedSession);
  const visibleSections = selectedClass === null ? sections : sections.filter((item) => item.class_id === selectedClass);

  return (
    <form onSubmit={handleSubmit} noValidate className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="grid gap-5 md:grid-cols-2">
        <Field label="Admission number" required error={fieldError('admission_number')}><input value={values.admission_number} maxLength={50} onChange={(event) => updateField('admission_number', event.target.value)} className={inputClass(fieldError('admission_number'))} /></Field>
        <Field label="Status" required error={fieldError('status')}><select value={values.status} onChange={(event) => updateField('status', event.target.value)} className={inputClass(fieldError('status'))}>{studentStatuses.map((status) => <option key={status} value={status}>{formatLabel(status)}</option>)}</select></Field>
        <Field label="First name" required error={fieldError('first_name')}><input value={values.first_name} maxLength={100} onChange={(event) => updateField('first_name', event.target.value)} className={inputClass(fieldError('first_name'))} /></Field>
        <Field label="Last name" required error={fieldError('last_name')}><input value={values.last_name} maxLength={100} onChange={(event) => updateField('last_name', event.target.value)} className={inputClass(fieldError('last_name'))} /></Field>
        <Field label="Email" error={fieldError('email')}><input type="email" value={values.email} maxLength={255} onChange={(event) => updateField('email', event.target.value)} className={inputClass(fieldError('email'))} /></Field>
        <Field label="Phone"><input value={values.phone} maxLength={30} onChange={(event) => updateField('phone', event.target.value)} className={inputClass()} /></Field>
        <Field label="Class name" required={mode === 'create'} error={fieldError('class_name')}><input value={values.class_name} maxLength={100} onChange={(event) => updateField('class_name', event.target.value)} className={inputClass(fieldError('class_name'))} /></Field>
        <Field label="Section"><input value={values.section} maxLength={50} onChange={(event) => updateField('section', event.target.value)} className={inputClass()} /></Field>
      </div>

      {mode === 'create' && <>
        <div className="border-t border-slate-200 pt-5">
          <h2 className="text-base font-semibold text-slate-900">Optional enrollment details</h2>
          <p className="mt-1 text-sm text-slate-500">These fields are accepted when creating a student. The current Student read response does not return them.</p>
        </div>
        {referencesError && <div className="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between"><span>Reference options are unavailable. You can still enter the required class name manually. {referencesError}</span>{onRetryReferences && <button type="button" onClick={onRetryReferences} className="rounded-lg border border-amber-200 bg-white px-3 py-2 font-semibold hover:bg-amber-100">Retry options</button>}</div>}
        <div className="grid gap-5 md:grid-cols-2">
          <Field label="Roll number"><input value={values.roll_number} maxLength={30} onChange={(event) => updateField('roll_number', event.target.value)} className={inputClass()} /></Field>
          <Field label="Date of birth"><input type="date" value={values.date_of_birth} onChange={(event) => updateField('date_of_birth', event.target.value)} className={inputClass()} /></Field>
          <Field label="Gender" error={fieldError('gender')}><select value={values.gender} onChange={(event) => updateField('gender', event.target.value)} className={inputClass(fieldError('gender'))}><option value="">Not specified</option>{studentGenders.map((gender) => <option key={gender} value={gender}>{formatLabel(gender)}</option>)}</select></Field>
          <Field label="Academic session" error={fieldError('academic_session_id')}><select value={values.academic_session_id} disabled={referencesLoading || sessions.length === 0} onChange={(event) => { updateField('academic_session_id', event.target.value); if (values.class_id && !classes.some((item) => item.id === Number(values.class_id) && item.academic_session_id === Number(event.target.value))) { updateField('class_id', ''); updateField('section_id', ''); } }} className={inputClass(fieldError('academic_session_id'))}><option value="">No session reference</option>{sessions.map((item) => <option key={item.id} value={item.id}>{item.session_name ?? `Session #${item.id}`}</option>)}</select></Field>
          <Field label="Academic class reference" error={fieldError('class_id')}><select value={values.class_id} disabled={referencesLoading || visibleClasses.length === 0} onChange={(event) => { updateField('class_id', event.target.value); updateField('section_id', ''); }} className={inputClass(fieldError('class_id'))}><option value="">No class reference</option>{visibleClasses.map((item) => <option key={item.id} value={item.id}>{item.class_name ?? `Class #${item.id}`}</option>)}</select></Field>
          <Field label="Section reference" error={fieldError('section_id')}><select value={values.section_id} disabled={referencesLoading || visibleSections.length === 0} onChange={(event) => updateField('section_id', event.target.value)} className={inputClass(fieldError('section_id'))}><option value="">No section reference</option>{visibleSections.map((item) => <option key={item.id} value={item.id}>{item.section_name ?? `Section #${item.id}`}</option>)}</select></Field>
        </div>
      </>}

      {Object.keys(serverErrors).length > 0 && <p className="text-sm text-red-600">Some values were rejected by the server. Review the highlighted fields and try again.</p>}
      <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="submit" disabled={isSubmitting} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">{isSubmitting ? 'Saving…' : submitLabel}</button></div>
    </form>
  );
}

function inputClass(error?: string) { return `w-full rounded-xl border ${error ? 'border-red-300 bg-red-50' : 'border-slate-200 bg-slate-50'} px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white`; }
function formatLabel(value: string) { return value.split('_').map((part) => part.charAt(0).toUpperCase() + part.slice(1)).join(' '); }
function Field({ label, required, error, children }: { label: string; required?: boolean; error?: string; children: React.ReactNode }) { return <label className="block text-sm font-medium text-slate-700"><span className="mb-2 block">{label}{required && <span className="ml-1 text-red-600">*</span>}</span>{children}{error && <span className="mt-1 block text-xs text-red-600">{error}</span>}</label>; }

export type { StudentClassOption, StudentSectionOption, StudentSessionOption, StudentCreateValues };
