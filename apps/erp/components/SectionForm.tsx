'use client';

import React from 'react';
import { isSectionStatus, listSectionClasses, sectionErrorMessage } from '../services/section';
import type { SectionClassOption, SectionFormValues, SectionRecord, SectionStatus } from '../types/section';

const statusOptions: SectionStatus[] = ['active', 'inactive'];
const emptyValues: SectionFormValues = { section_name: '', class_id: '', capacity: '', status: 'active' };

export function toSectionFormValues(value: Partial<SectionFormValues> | Partial<SectionRecord> | null | undefined): SectionFormValues {
  return { section_name: String(value?.section_name ?? ''), class_id: String(value?.class_id ?? ''), capacity: String(value?.capacity ?? ''), status: isSectionStatus(String(value?.status ?? '')) ? String(value?.status) as SectionStatus : 'active' };
}

export function validateSectionForm(values: SectionFormValues, mode: 'create' | 'edit'): Record<string, string> {
  const errors: Record<string, string> = {};
  if (!values.section_name.trim()) errors.section_name = 'Section name is required.';
  if (!validId(values.class_id)) errors.class_id = 'Select a valid academic class.';
  if (mode === 'create' && values.capacity.trim() !== '' && !validCapacity(values.capacity)) errors.capacity = 'Capacity must be a non-negative whole number.';
  if (!statusOptions.includes(values.status)) errors.status = 'Status must be active or inactive.';
  return errors;
}

export function useSectionClasses() {
  const [classes, setClasses] = React.useState<SectionClassOption[] | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const loadClasses = React.useCallback(async () => { setLoading(true); setError(null); try { setClasses(await listSectionClasses()); } catch (requestError: unknown) { setClasses(null); setError(sectionErrorMessage(requestError, 'Unable to load academic classes.')); } finally { setLoading(false); } }, []);
  React.useEffect(() => { void loadClasses(); }, [loadClasses]);
  return { classes, loading, error, reload: loadClasses };
}

export default function SectionForm({ mode, initialValues, classes, classesLoading, classesError, onRetryClasses, serverErrors = {}, submitLabel, isSubmitting, onSubmit }: {
  mode: 'create' | 'edit';
  initialValues?: Partial<SectionFormValues> | Partial<SectionRecord> | null;
  classes: SectionClassOption[] | null;
  classesLoading: boolean;
  classesError: string | null;
  onRetryClasses: () => void;
  serverErrors?: Record<string, string>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: SectionFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<SectionFormValues>(() => toSectionFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  React.useEffect(() => { setValues(toSectionFormValues(initialValues ?? emptyValues)); setErrors({}); }, [initialValues]);
  function updateField(field: keyof SectionFormValues, value: string) { setValues((current) => ({ ...current, [field]: value })); setErrors((current) => ({ ...current, [field]: '' })); }
  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) { event.preventDefault(); const nextErrors = validateSectionForm(values, mode); setErrors(nextErrors); if (Object.keys(nextErrors).length > 0 || !classes) return; await onSubmit(values); }
  const formErrors = { ...serverErrors, ...errors };
  const disabled = isSubmitting || classesLoading || !classes;
  return <form onSubmit={handleSubmit} className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    {classesError && <div className="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{classesError}</span><button type="button" onClick={onRetryClasses} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold text-red-700 hover:bg-red-100">Retry classes</button></div>}
    <div className="grid gap-5 md:grid-cols-2">
      <Field id="section_name" label="Section name" error={formErrors.section_name}><input id="section_name" value={values.section_name} onChange={(event) => updateField('section_name', event.target.value)} aria-invalid={Boolean(formErrors.section_name)} maxLength={50} disabled={isSubmitting} className={inputClass(Boolean(formErrors.section_name))} /></Field>
      <Field id="class_id" label="Academic class" error={formErrors.class_id}><select id="class_id" value={values.class_id} onChange={(event) => updateField('class_id', event.target.value)} aria-invalid={Boolean(formErrors.class_id)} disabled={disabled} className={inputClass(Boolean(formErrors.class_id))}><option value="">{classesLoading ? 'Loading classes…' : 'Select academic class'}</option>{classes?.map((item) => <option key={item.id} value={item.id}>{item.class_name ?? `Class #${item.id}`}</option>)}</select></Field>
      {mode === 'create' && <Field id="capacity" label="Capacity (optional)" error={formErrors.capacity}><input id="capacity" type="number" min="0" step="1" value={values.capacity} onChange={(event) => updateField('capacity', event.target.value)} aria-invalid={Boolean(formErrors.capacity)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.capacity))} /></Field>}
      <Field id="status" label="Status" error={formErrors.status}><select id="status" value={values.status} onChange={(event) => updateField('status', event.target.value)} aria-invalid={Boolean(formErrors.status)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.status))}>{statusOptions.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}</select></Field>
    </div>
    {mode === 'edit' && <p className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Capacity is stored by the backend but omitted from the current Section response. Edit sends only exposed fields so the stored capacity remains unchanged.</p>}
    <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-xs text-slate-500">Fields follow the Section API validation contract.</p><button type="submit" disabled={disabled} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">{isSubmitting ? 'Saving…' : submitLabel}</button></div>
  </form>;
}

function validId(value: string): boolean { const number = Number(value); return Number.isInteger(number) && number > 0; }
function validCapacity(value: string): boolean { const number = Number(value); return Number.isInteger(number) && number >= 0; }
function inputClass(hasError: boolean): string { return `w-full rounded-xl border ${hasError ? 'border-red-300' : 'border-slate-200'} bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white disabled:cursor-not-allowed disabled:opacity-60`; }
function statusLabel(status: SectionStatus): string { return status.charAt(0).toUpperCase() + status.slice(1); }
function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) { return <div><label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label><div className="mt-2">{children}</div>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</div>; }
