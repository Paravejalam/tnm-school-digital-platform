'use client';

import React from 'react';
import type { SubjectFormValues, SubjectRecord, SubjectStatus } from '../types/subject';

const statusOptions: SubjectStatus[] = ['active', 'inactive'];

const emptyValues: SubjectFormValues = {
  subject_name: '',
  code: '',
  section_id: '',
  status: 'active',
};

export function toFormValues(value: Partial<SubjectFormValues> | Partial<SubjectRecord> | null | undefined): SubjectFormValues {
  return {
    subject_name: String(value?.subject_name ?? ''),
    code: String(value?.code ?? ''),
    section_id: String(value?.section_id ?? ''),
    status: (value?.status as SubjectStatus) ?? 'active',
  };
}

export function validateSubjectForm(values: SubjectFormValues): Record<string, string> {
  const errors: Record<string, string> = {};

  if (!values.subject_name.trim()) errors.subject_name = 'Subject name is required.';
  if (!values.section_id.trim()) errors.section_id = 'Section ID is required.';

  if (!statusOptions.includes(values.status)) {
    errors.status = 'Status is invalid.';
  }

  return errors;
}

export default function SubjectForm({
  initialValues,
  submitLabel,
  isSubmitting,
  onSubmit,
}: {
  initialValues?: Partial<SubjectFormValues> | null;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: SubjectFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<SubjectFormValues>(() => toFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    setValues(toFormValues(initialValues ?? emptyValues));
  }, [initialValues]);

  function updateField(field: keyof SubjectFormValues, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const nextErrors = validateSubjectForm(values);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;
    await onSubmit(values);
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="grid gap-5 md:grid-cols-2">
        <Field label="Subject name">
          <input
            value={values.subject_name}
            onChange={(event) => updateField('subject_name', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
            placeholder="e.g. Mathematics"
          />
          {errors.subject_name && <FieldError>{errors.subject_name}</FieldError>}
        </Field>

        <Field label="Status">
          <select
            value={values.status}
            onChange={(event) => updateField('status', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
          >
            {statusOptions.map((status) => (
              <option key={status} value={status}>
                {status}
              </option>
            ))}
          </select>
          {errors.status && <FieldError>{errors.status}</FieldError>}
        </Field>

        <Field label="Code">
          <input
            value={values.code}
            onChange={(event) => updateField('code', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
            placeholder="e.g. MATH"
          />
        </Field>

        <Field label="Section ID">
          <input
            type="number"
            min="1"
            value={values.section_id}
            onChange={(event) => updateField('section_id', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
          />
          {errors.section_id && <FieldError>{errors.section_id}</FieldError>}
        </Field>
      </div>

      <div className="flex justify-end">
        <button
          type="submit"
          disabled={isSubmitting}
          className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
        >
          {isSubmitting ? 'Saving...' : submitLabel}
        </button>
      </div>
    </form>
  );
}

function Field({ label, children }: { label: string; children: React.ReactNode }) {
  return (
    <label className="block text-sm font-medium text-slate-700">
      <span className="mb-2 block">{label}</span>
      {children}
    </label>
  );
}

function FieldError({ children }: { children: React.ReactNode }) {
  return <span className="mt-1 block text-xs text-red-600">{children}</span>;
}
