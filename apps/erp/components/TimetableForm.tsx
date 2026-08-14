'use client';

import React from 'react';
import {
  listTimetableReferences,
  timetableErrorMessage,
} from '../services/timetable';
import type {
  TimetableFormValues,
  TimetableRecord,
  TimetableReferenceData,
  TimetableStatus,
} from '../types/timetable';

const statusOptions: TimetableStatus[] = ['active', 'inactive', 'draft'];

const emptyValues: TimetableFormValues = {
  timetable_name: '',
  academic_session_id: '',
  class_id: '',
  section_id: '',
  subject_id: '',
  teacher_id: '',
  status: 'active',
};

export function toFormValues(value: Partial<TimetableFormValues> | Partial<TimetableRecord> | null | undefined): TimetableFormValues {
  return {
    timetable_name: String(value?.timetable_name ?? ''),
    academic_session_id: String(value?.academic_session_id ?? ''),
    class_id: String(value?.class_id ?? ''),
    section_id: String(value?.section_id ?? ''),
    subject_id: String(value?.subject_id ?? ''),
    teacher_id: String(value?.teacher_id ?? ''),
    status: value?.status === 'inactive' || value?.status === 'draft' ? value.status : 'active',
  };
}

export function validateTimetableForm(values: TimetableFormValues, references?: TimetableReferenceData | null): Record<string, string> {
  const errors: Record<string, string> = {};

  if (!values.timetable_name.trim()) {
    errors.timetable_name = 'Timetable name is required.';
  }

  for (const field of ['academic_session_id', 'class_id', 'section_id', 'subject_id', 'teacher_id'] as const) {
    const value = Number(values[field]);
    if (!values[field].trim()) {
      errors[field] = `${field.replaceAll('_', ' ')} is required.`;
    } else if (!Number.isInteger(value) || value < 1) {
      errors[field] = 'Select a valid option.';
    }
  }

  if (!statusOptions.includes(values.status)) {
    errors.status = 'Status must be active, inactive or draft.';
  }

  if (references) {
    const session = references.academicSessions.find((item) => item.id === Number(values.academic_session_id));
    const academicClass = references.classes.find((item) => item.id === Number(values.class_id));
    const section = references.sections.find((item) => item.id === Number(values.section_id));
    const subject = references.subjects.find((item) => item.id === Number(values.subject_id));
    const teacher = references.teachers.find((item) => item.id === Number(values.teacher_id));

    if (!session) errors.academic_session_id = 'Select a valid academic session.';
    if (!academicClass) errors.class_id = 'Select a valid class.';
    else if (academicClass.academic_session_id !== null && academicClass.academic_session_id !== Number(values.academic_session_id)) errors.class_id = 'Select a class from the chosen academic session.';
    if (!section) errors.section_id = 'Select a valid section.';
    else if (section.class_id !== null && section.class_id !== Number(values.class_id)) errors.section_id = 'Select a section from the chosen class.';
    if (!subject) errors.subject_id = 'Select a valid subject.';
    else if (subject.section_id !== null && subject.section_id !== Number(values.section_id)) errors.subject_id = 'Select a subject from the chosen section.';
    if (!teacher) errors.teacher_id = 'Select a valid teacher.';
  }

  return errors;
}

export function useTimetableReferences() {
  const [references, setReferences] = React.useState<TimetableReferenceData | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const loadReferences = React.useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      setReferences(await listTimetableReferences());
    } catch (requestError: unknown) {
      setReferences(null);
      setError(timetableErrorMessage(requestError, 'Unable to load timetable options.'));
    } finally {
      setLoading(false);
    }
  }, []);

  React.useEffect(() => {
    void loadReferences();
  }, [loadReferences]);

  return { references, loading, error, reload: loadReferences };
}

export default function TimetableForm({
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
  initialValues?: Partial<TimetableFormValues> | null;
  references: TimetableReferenceData | null;
  referencesLoading: boolean;
  referencesError: string | null;
  onRetryReferences: () => void;
  serverErrors?: Record<string, string>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: TimetableFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<TimetableFormValues>(() => toFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    setValues(toFormValues(initialValues ?? emptyValues));
    setErrors({});
  }, [initialValues]);

  function updateField(field: keyof TimetableFormValues, value: string) {
    setValues((current) => {
      if (field === 'academic_session_id') return { ...current, academic_session_id: value, class_id: '', section_id: '', subject_id: '' };
      if (field === 'class_id') return { ...current, class_id: value, section_id: '', subject_id: '' };
      if (field === 'section_id') return { ...current, section_id: value, subject_id: '' };
      return { ...current, [field]: value };
    });
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors = validateTimetableForm(values, references);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0 || !references) return;
    await onSubmit(values);
  }

  const classes = references?.classes.filter((academicClass) => {
    if (!values.academic_session_id) return true;
    return academicClass.academic_session_id === Number(values.academic_session_id);
  }) ?? [];
  const sections = references?.sections.filter((section) => {
    if (!values.class_id) return true;
    return section.class_id === Number(values.class_id);
  }) ?? [];
  const subjects = references?.subjects.filter((subject) => {
    if (!values.section_id) return false;
    return subject.section_id === Number(values.section_id);
  }) ?? [];
  const formErrors = { ...serverErrors, ...errors };
  const disabled = isSubmitting || referencesLoading || !references;

  return (
    <form onSubmit={handleSubmit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      {referencesError && (
        <div className="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between">
          <span>{referencesError}</span>
          <button type="button" onClick={onRetryReferences} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold text-red-700 hover:bg-red-100">
            Retry options
          </button>
        </div>
      )}

      <div className="grid gap-5 md:grid-cols-2">
        <Field id="timetable_name" label="Timetable name" error={formErrors.timetable_name}>
          <input
            id="timetable_name"
            value={values.timetable_name}
            onChange={(event) => updateField('timetable_name', event.target.value)}
            aria-invalid={Boolean(formErrors.timetable_name)}
            className={inputClass(Boolean(formErrors.timetable_name))}
            placeholder="e.g. Grade 7 Morning"
            maxLength={200}
            disabled={isSubmitting}
          />
        </Field>

        <Field id="status" label="Status" error={formErrors.status}>
          <select id="status" value={values.status} onChange={(event) => updateField('status', event.target.value)} aria-invalid={Boolean(formErrors.status)} className={inputClass(Boolean(formErrors.status))} disabled={isSubmitting}>
            {statusOptions.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}
          </select>
        </Field>

        <Field id="academic_session_id" label="Academic session" error={formErrors.academic_session_id}>
          <select id="academic_session_id" value={values.academic_session_id} onChange={(event) => updateField('academic_session_id', event.target.value)} aria-invalid={Boolean(formErrors.academic_session_id)} className={inputClass(Boolean(formErrors.academic_session_id))} disabled={disabled}>
            <option value="">{referencesLoading ? 'Loading sessions…' : 'Select academic session'}</option>
            {references?.academicSessions.map((session) => <option key={session.id} value={session.id}>{session.session_name ?? `Session #${session.id}`}</option>)}
          </select>
        </Field>

        <Field id="class_id" label="Class" error={formErrors.class_id}>
          <select id="class_id" value={values.class_id} onChange={(event) => updateField('class_id', event.target.value)} aria-invalid={Boolean(formErrors.class_id)} className={inputClass(Boolean(formErrors.class_id))} disabled={disabled}>
            <option value="">{referencesLoading ? 'Loading classes…' : 'Select class'}</option>
            {classes.length === 0 && <option disabled value="__empty_classes">No classes available for this session</option>}
            {classes.map((item) => <option key={item.id} value={item.id}>{item.class_name ?? `Class #${item.id}`}{item.code ? ` · ${item.code}` : ''}</option>)}
          </select>
        </Field>

        <Field id="section_id" label="Section" error={formErrors.section_id}>
          <select id="section_id" value={values.section_id} onChange={(event) => updateField('section_id', event.target.value)} aria-invalid={Boolean(formErrors.section_id)} className={inputClass(Boolean(formErrors.section_id))} disabled={disabled || !values.class_id}>
            <option value="">{values.class_id ? 'Select section' : 'Select a class first'}</option>
            {sections.length === 0 && values.class_id && <option disabled value="__empty_sections">No sections available for this class</option>}
            {sections.map((item) => <option key={item.id} value={item.id}>{item.section_name ?? `Section #${item.id}`}{item.code ? ` · ${item.code}` : ''}</option>)}
          </select>
        </Field>

        <Field id="subject_id" label="Subject" error={formErrors.subject_id}>
          <select id="subject_id" value={values.subject_id} onChange={(event) => updateField('subject_id', event.target.value)} aria-invalid={Boolean(formErrors.subject_id)} className={inputClass(Boolean(formErrors.subject_id))} disabled={disabled || !values.section_id}>
            <option value="">{values.section_id ? 'Select subject' : 'Select a section first'}</option>
            {subjects.length === 0 && values.section_id && <option disabled value="__empty_subjects">No subjects available for this section</option>}
            {subjects.map((item) => <option key={item.id} value={item.id}>{item.subject_name ?? `Subject #${item.id}`}{item.code ? ` · ${item.code}` : ''}</option>)}
          </select>
        </Field>

        <Field id="teacher_id" label="Teacher" error={formErrors.teacher_id}>
          <select id="teacher_id" value={values.teacher_id} onChange={(event) => updateField('teacher_id', event.target.value)} aria-invalid={Boolean(formErrors.teacher_id)} className={inputClass(Boolean(formErrors.teacher_id))} disabled={disabled}>
            <option value="">{referencesLoading ? 'Loading teachers…' : 'Select teacher'}</option>
            {references?.teachers.length === 0 && <option disabled value="__empty_teachers">No teachers available</option>}
            {references?.teachers.map((teacher) => <option key={teacher.id} value={teacher.id}>{teacherName(teacher.first_name, teacher.last_name)}{teacher.employee_id ? ` · ${teacher.employee_id}` : ''}</option>)}
          </select>
        </Field>
      </div>

      <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-xs text-slate-500">All academic assignments are required by the Timetable service.</p>
        <button type="submit" disabled={disabled} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">
          {isSubmitting ? 'Saving…' : submitLabel}
        </button>
      </div>
    </form>
  );
}

function inputClass(hasError: boolean): string {
  return `w-full rounded-xl border ${hasError ? 'border-red-300' : 'border-slate-200'} bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white disabled:cursor-not-allowed disabled:opacity-60`;
}

function statusLabel(status: TimetableStatus): string {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function teacherName(firstName: string | null, lastName: string | null): string {
  const name = [firstName, lastName].filter((part): part is string => Boolean(part)).join(' ');
  return name || 'Unnamed teacher';
}

function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) {
  return (
    <div>
      <label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label>
      <div className="mt-2">{children}</div>
      {error && <p className="mt-1 text-xs text-red-600">{error}</p>}
    </div>
  );
}
