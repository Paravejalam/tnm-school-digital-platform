'use client';

import React from 'react';
import { academicClassErrorMessage, isAcademicClassStatus, listAcademicClassSessions } from '../services/academicClass';
import type {
  AcademicClassFormValues,
  AcademicClassRecord,
  AcademicClassSessionOption,
  AcademicClassStatus,
} from '../types/academicClass';

const statusOptions: AcademicClassStatus[] = ['active', 'inactive'];
const emptyValues: AcademicClassFormValues = { class_name: '', academic_session_id: '', grade_level: '', status: 'active' };

export function toAcademicClassFormValues(value: Partial<AcademicClassFormValues> | Partial<AcademicClassRecord> | null | undefined): AcademicClassFormValues {
  return {
    class_name: String(value?.class_name ?? ''),
    academic_session_id: String(value?.academic_session_id ?? ''),
    grade_level: String(value?.grade_level ?? ''),
    status: isAcademicClassStatus(String(value?.status ?? '')) ? String(value?.status) as AcademicClassStatus : 'active',
  };
}

export function validateAcademicClassForm(values: AcademicClassFormValues, mode: 'create' | 'edit'): Record<string, string> {
  const errors: Record<string, string> = {};
  if (!values.class_name.trim()) errors.class_name = 'Class name is required.';
  if (!validId(values.academic_session_id)) errors.academic_session_id = 'Select a valid academic session.';
  if (mode === 'create' && values.grade_level.trim() !== '' && !validGrade(values.grade_level)) errors.grade_level = 'Grade level must be a non-negative whole number.';
  if (!statusOptions.includes(values.status)) errors.status = 'Status must be active or inactive.';
  return errors;
}

export function useAcademicClassSessions() {
  const [sessions, setSessions] = React.useState<AcademicClassSessionOption[] | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const loadSessions = React.useCallback(async () => {
    setLoading(true); setError(null);
    try { setSessions(await listAcademicClassSessions()); }
    catch (requestError: unknown) { setSessions(null); setError(academicClassErrorMessage(requestError, 'Unable to load academic sessions.')); }
    finally { setLoading(false); }
  }, []);
  React.useEffect(() => { void loadSessions(); }, [loadSessions]);
  return { sessions, loading, error, reload: loadSessions };
}

export default function AcademicClassForm({
  mode,
  initialValues,
  sessions,
  sessionsLoading,
  sessionsError,
  onRetrySessions,
  serverErrors = {},
  submitLabel,
  isSubmitting,
  onSubmit,
}: {
  mode: 'create' | 'edit';
  initialValues?: Partial<AcademicClassFormValues> | Partial<AcademicClassRecord> | null;
  sessions: AcademicClassSessionOption[] | null;
  sessionsLoading: boolean;
  sessionsError: string | null;
  onRetrySessions: () => void;
  serverErrors?: Record<string, string>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: AcademicClassFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<AcademicClassFormValues>(() => toAcademicClassFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  React.useEffect(() => { setValues(toAcademicClassFormValues(initialValues ?? emptyValues)); setErrors({}); }, [initialValues]);

  function updateField(field: keyof AcademicClassFormValues, value: string) { setValues((current) => ({ ...current, [field]: value })); setErrors((current) => ({ ...current, [field]: '' })); }
  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors = validateAcademicClassForm(values, mode);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0 || !sessions) return;
    await onSubmit(values);
  }

  const formErrors = { ...serverErrors, ...errors };
  const disabled = isSubmitting || sessionsLoading || !sessions;
  return <form onSubmit={handleSubmit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    {sessionsError && <div className="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{sessionsError}</span><button type="button" onClick={onRetrySessions} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold text-red-700 hover:bg-red-100">Retry sessions</button></div>}
    <div className="grid gap-5 md:grid-cols-2">
      <Field id="class_name" label="Class name" error={formErrors.class_name}><input id="class_name" value={values.class_name} onChange={(event) => updateField('class_name', event.target.value)} aria-invalid={Boolean(formErrors.class_name)} maxLength={100} disabled={isSubmitting} className={inputClass(Boolean(formErrors.class_name))} /></Field>
      <Field id="academic_session_id" label="Academic session" error={formErrors.academic_session_id}><select id="academic_session_id" value={values.academic_session_id} onChange={(event) => updateField('academic_session_id', event.target.value)} aria-invalid={Boolean(formErrors.academic_session_id)} disabled={disabled} className={inputClass(Boolean(formErrors.academic_session_id))}><option value="">{sessionsLoading ? 'Loading sessions…' : 'Select academic session'}</option>{sessions?.map((session) => <option key={session.id} value={session.id}>{session.session_name ?? `Session #${session.id}`}</option>)}</select></Field>
      {mode === 'create' && <Field id="grade_level" label="Grade level (optional)" error={formErrors.grade_level}><input id="grade_level" type="number" min="0" step="1" value={values.grade_level} onChange={(event) => updateField('grade_level', event.target.value)} aria-invalid={Boolean(formErrors.grade_level)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.grade_level))} /></Field>}
      <Field id="status" label="Status" error={formErrors.status}><select id="status" value={values.status} onChange={(event) => updateField('status', event.target.value)} aria-invalid={Boolean(formErrors.status)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.status))}>{statusOptions.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}</select></Field>
    </div>
    {mode === 'edit' && <p className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Grade level is stored by the backend but omitted from the current read response. Edit sends only exposed fields so the stored grade level remains unchanged.</p>}
    <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-xs text-slate-500">Fields follow the AcademicClass API validation contract.</p><button type="submit" disabled={disabled} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">{isSubmitting ? 'Saving…' : submitLabel}</button></div>
  </form>;
}

function validId(value: string): boolean { const number = Number(value); return Number.isInteger(number) && number > 0; }
function validGrade(value: string): boolean { const number = Number(value); return Number.isInteger(number) && number >= 0; }
function statusLabel(status: AcademicClassStatus): string { return status.charAt(0).toUpperCase() + status.slice(1); }
function inputClass(hasError: boolean): string { return `w-full rounded-xl border ${hasError ? 'border-red-300' : 'border-slate-200'} bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white disabled:cursor-not-allowed disabled:opacity-60`; }
function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) { return <div><label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label><div className="mt-2">{children}</div>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</div>; }
