'use client';

import React from 'react';
import type { TeacherFormValues, TeacherRecord, TeacherStatus } from '../types/teacher';

const statusOptions: TeacherStatus[] = ['active', 'inactive'];

const emptyValues: TeacherFormValues = {
  employee_id: '',
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  department: '',
  designation: '',
  status: 'active',
};

export function toFormValues(value: Partial<TeacherFormValues> | Partial<TeacherRecord> | null | undefined): TeacherFormValues {
  return {
    employee_id: String(value?.employee_id ?? ''),
    first_name: String(value?.first_name ?? ''),
    last_name: String(value?.last_name ?? ''),
    email: String(value?.email ?? ''),
    phone: String(value?.phone ?? ''),
    department: String(value?.department ?? ''),
    designation: String(value?.designation ?? ''),
    status: (value?.status as TeacherStatus) ?? 'active',
  };
}

export function validateTeacherForm(values: TeacherFormValues): Record<string, string> {
  const errors: Record<string, string> = {};

  if (!values.employee_id.trim()) errors.employee_id = 'Employee ID is required.';
  if (!values.first_name.trim()) errors.first_name = 'First name is required.';
  if (!values.last_name.trim()) errors.last_name = 'Last name is required.';
  if (!values.department.trim()) errors.department = 'Department is required.';

  if (values.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(values.email)) {
    errors.email = 'Email must be valid.';
  }

  if (!statusOptions.includes(values.status)) {
    errors.status = 'Status is invalid.';
  }

  return errors;
}

export default function TeacherForm({
  initialValues,
  submitLabel,
  isSubmitting,
  onSubmit,
}: {
  initialValues?: Partial<TeacherFormValues> | null;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: TeacherFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<TeacherFormValues>(() => toFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    setValues(toFormValues(initialValues ?? emptyValues));
  }, [initialValues]);

  function updateField(field: keyof TeacherFormValues, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const nextErrors = validateTeacherForm(values);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;
    await onSubmit(values);
  }

  return (
    <form onSubmit={handleSubmit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="grid gap-5 md:grid-cols-2">
        <Field label="Employee ID">
          <input
            value={values.employee_id}
            onChange={(event) => updateField('employee_id', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
            placeholder="e.g. TNM-T-001"
          />
          {errors.employee_id && <FieldError>{errors.employee_id}</FieldError>}
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

        <Field label="First name">
          <input
            value={values.first_name}
            onChange={(event) => updateField('first_name', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
          />
          {errors.first_name && <FieldError>{errors.first_name}</FieldError>}
        </Field>

        <Field label="Last name">
          <input
            value={values.last_name}
            onChange={(event) => updateField('last_name', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
          />
          {errors.last_name && <FieldError>{errors.last_name}</FieldError>}
        </Field>

        <Field label="Email">
          <input
            type="email"
            value={values.email}
            onChange={(event) => updateField('email', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
            placeholder="teacher@example.com"
          />
          {errors.email && <FieldError>{errors.email}</FieldError>}
        </Field>

        <Field label="Phone">
          <input
            value={values.phone}
            onChange={(event) => updateField('phone', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
            placeholder="+91 98765 43210"
          />
        </Field>

        <Field label="Department">
          <input
            value={values.department}
            onChange={(event) => updateField('department', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
            placeholder="Mathematics"
          />
          {errors.department && <FieldError>{errors.department}</FieldError>}
        </Field>

        <Field label="Designation">
          <input
            value={values.designation}
            onChange={(event) => updateField('designation', event.target.value)}
            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
            placeholder="Senior Teacher"
          />
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
