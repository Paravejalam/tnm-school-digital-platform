'use client';

import React from 'react';
import type {
  AcademicSessionFormValues,
  AcademicSessionRecord,
  AcademicSessionStatus,
} from '../types/academicSession';

const statusOptions: AcademicSessionStatus[] = ['active', 'inactive', 'archived'];
const emptyValues: AcademicSessionFormValues = {
  session_name: '',
  start_date: '',
  end_date: '',
  status: 'active',
  is_current: false,
};

export function toAcademicSessionFormValues(value: Partial<AcademicSessionFormValues> | Partial<AcademicSessionRecord> | null | undefined): AcademicSessionFormValues {
  const source = value as Partial<AcademicSessionFormValues>;
  return {
    session_name: String(value?.session_name ?? ''),
    start_date: String(source.start_date ?? ''),
    end_date: String(source.end_date ?? ''),
    status: value?.status === 'inactive' || value?.status === 'archived' ? value.status : 'active',
    is_current: Boolean(source.is_current),
  };
}

export function validateAcademicSessionForm(values: AcademicSessionFormValues, mode: 'create' | 'edit'): Record<string, string> {
  const errors: Record<string, string> = {};
  if (!values.session_name.trim()) errors.session_name = 'Academic session name is required.';
  if (mode === 'create') {
    if (!values.start_date) errors.start_date = 'Start date is required.';
    if (!values.end_date) errors.end_date = 'End date is required.';
  }
  if (!statusOptions.includes(values.status)) errors.status = 'Status must be active, inactive, or archived.';
  return errors;
}

export default function AcademicSessionForm({
  mode,
  initialValues,
  serverErrors = {},
  submitLabel,
  isSubmitting,
  onSubmit,
}: {
  mode: 'create' | 'edit';
  initialValues?: Partial<AcademicSessionFormValues> | Partial<AcademicSessionRecord> | null;
  serverErrors?: Record<string, string>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: AcademicSessionFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<AcademicSessionFormValues>(() => toAcademicSessionFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    setValues(toAcademicSessionFormValues(initialValues ?? emptyValues));
    setErrors({});
  }, [initialValues]);

  function updateField(field: keyof AcademicSessionFormValues, value: string | boolean) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors = validateAcademicSessionForm(values, mode);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;
    await onSubmit(values);
  }

  const formErrors = { ...serverErrors, ...errors };

  return (
    <form onSubmit={handleSubmit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="grid gap-5 md:grid-cols-2">
        <Field id="session_name" label="Session name" error={formErrors.session_name}>
          <input id="session_name" value={values.session_name} onChange={(event) => updateField('session_name', event.target.value)} aria-invalid={Boolean(formErrors.session_name)} maxLength={100} disabled={isSubmitting} placeholder="e.g. 2026-2027" className={inputClass(Boolean(formErrors.session_name))} />
        </Field>

        <Field id="status" label="Status" error={formErrors.status}>
          <select id="status" value={values.status} onChange={(event) => updateField('status', event.target.value)} aria-invalid={Boolean(formErrors.status)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.status))}>
            {statusOptions.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}
          </select>
        </Field>

        {mode === 'create' && <>
          <Field id="start_date" label="Start date" error={formErrors.start_date}>
            <input id="start_date" type="date" value={values.start_date} onChange={(event) => updateField('start_date', event.target.value)} aria-invalid={Boolean(formErrors.start_date)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.start_date))} />
          </Field>
          <Field id="end_date" label="End date" error={formErrors.end_date}>
            <input id="end_date" type="date" value={values.end_date} onChange={(event) => updateField('end_date', event.target.value)} aria-invalid={Boolean(formErrors.end_date)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.end_date))} />
          </Field>
          <label className="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 md:col-span-2">
            <input type="checkbox" checked={values.is_current} onChange={(event) => updateField('is_current', event.target.checked)} disabled={isSubmitting} className="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
            <span><span className="font-semibold text-slate-900">Mark as current session</span><span className="mt-1 block text-xs text-slate-500">The value is stored by the Academic Session API. Existing current-session state is preserved during edits because it is not included in read responses.</span></span>
          </label>
        </>}
      </div>

      {mode === 'edit' && <p className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">The current API read response exposes only the session name and status. Saving this form uses a partial update, so the stored dates and current-session flag remain unchanged.</p>}

      <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p className="text-xs text-slate-500">Fields and values follow the Academic Session backend contract.</p>
        <button type="submit" disabled={isSubmitting} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">{isSubmitting ? 'Saving…' : submitLabel}</button>
      </div>
    </form>
  );
}

function inputClass(hasError: boolean): string {
  return `w-full rounded-xl border ${hasError ? 'border-red-300' : 'border-slate-200'} bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white disabled:cursor-not-allowed disabled:opacity-60`;
}

function statusLabel(status: AcademicSessionStatus): string {
  return status.charAt(0).toUpperCase() + status.slice(1);
}

function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) {
  return <div><label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label><div className="mt-2">{children}</div>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</div>;
}
