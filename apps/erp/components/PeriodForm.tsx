'use client';

import React from 'react';
import { listTimetables } from '../services/timetable';
import { periodErrorMessage } from '../services/period';
import type { PeriodDay, PeriodFormValues, PeriodRecord, PeriodStatus } from '../types/period';
import type { TimetableRecord } from '../types/timetable';

const statusOptions: PeriodStatus[] = ['active', 'inactive'];
const dayOptions: PeriodDay[] = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
const emptyValues: PeriodFormValues = { period_name: '', timetable_id: '', day_of_week: '', start_time: '', end_time: '', period_order: '', status: 'active' };

export function toFormValues(value: Partial<PeriodFormValues> | Partial<PeriodRecord> | null | undefined): PeriodFormValues {
  const formValue = value as Partial<PeriodFormValues> | undefined;
  return {
    period_name: String(value?.period_name ?? ''),
    timetable_id: String(value?.timetable_id ?? ''),
    day_of_week: dayOptions.includes(formValue?.day_of_week as PeriodDay) ? formValue?.day_of_week as PeriodDay : '',
    start_time: String(formValue?.start_time ?? ''),
    end_time: String(formValue?.end_time ?? ''),
    period_order: String(formValue?.period_order ?? ''),
    status: value?.status === 'inactive' ? 'inactive' : 'active',
  };
}

export function validatePeriodForm(values: PeriodFormValues, mode: 'create' | 'edit', timetables?: TimetableRecord[] | null): Record<string, string> {
  const errors: Record<string, string> = {};
  const timetableId = Number(values.timetable_id);

  if (!values.period_name.trim()) errors.period_name = 'Period name is required.';
  if (!values.timetable_id.trim()) errors.timetable_id = 'Timetable is required.';
  else if (!Number.isInteger(timetableId) || timetableId < 1) errors.timetable_id = 'Select a valid timetable.';
  else if (timetables && !timetables.some((item) => item.id === timetableId)) errors.timetable_id = 'Select a valid timetable.';
  if (!statusOptions.includes(values.status)) errors.status = 'Status must be active or inactive.';

  if (mode === 'create') {
    if (!dayOptions.includes(values.day_of_week as PeriodDay)) errors.day_of_week = 'Select a valid day of the week.';
    if (!validTime(values.start_time)) errors.start_time = 'Enter a valid start time.';
    if (!validTime(values.end_time)) errors.end_time = 'Enter a valid end time.';
    if (values.period_order.trim() !== '') {
      const order = Number(values.period_order);
      if (!Number.isInteger(order) || order < 0 || order > 255) errors.period_order = 'Period order must be a whole number from 0 to 255.';
    }
  }
  return errors;
}

function validTime(value: string): boolean {
  if (!/^\d{2}:\d{2}$/.test(value)) return false;
  const [hours, minutes] = value.split(':').map(Number);
  return hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59;
}

export function usePeriodTimetables() {
  const [timetables, setTimetables] = React.useState<TimetableRecord[] | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const loadTimetables = React.useCallback(async () => {
    setLoading(true); setError(null);
    try { setTimetables((await listTimetables({ page: 1, per_page: 100 })).items); }
    catch (requestError: unknown) { setTimetables(null); setError(periodErrorMessage(requestError, 'Unable to load timetables.')); }
    finally { setLoading(false); }
  }, []);
  React.useEffect(() => { void loadTimetables(); }, [loadTimetables]);
  return { timetables, loading, error, reload: loadTimetables };
}

export default function PeriodForm({ mode, initialValues, timetables, timetablesLoading, timetablesError, onRetryTimetables, serverErrors = {}, submitLabel, isSubmitting, onSubmit }: {
  mode: 'create' | 'edit';
  initialValues?: Partial<PeriodFormValues> | null;
  timetables: TimetableRecord[] | null;
  timetablesLoading: boolean;
  timetablesError: string | null;
  onRetryTimetables: () => void;
  serverErrors?: Record<string, string>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: PeriodFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<PeriodFormValues>(() => toFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});
  React.useEffect(() => { setValues(toFormValues(initialValues ?? emptyValues)); setErrors({}); }, [initialValues]);

  function updateField(field: keyof PeriodFormValues, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors = validatePeriodForm(values, mode, timetables);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0 || !timetables) return;
    await onSubmit(values);
  }

  const formErrors = { ...serverErrors, ...errors };
  const disabled = isSubmitting || timetablesLoading || !timetables;

  return <form onSubmit={handleSubmit} noValidate className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    {timetablesError && <div className="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{timetablesError}</span><button type="button" onClick={onRetryTimetables} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold text-red-700 hover:bg-red-100">Retry timetables</button></div>}
    <div className="grid gap-5 md:grid-cols-2">
      <Field id="period_name" label="Period name" error={formErrors.period_name}><input id="period_name" value={values.period_name} onChange={(event) => updateField('period_name', event.target.value)} aria-invalid={Boolean(formErrors.period_name)} maxLength={100} disabled={isSubmitting} className={inputClass(Boolean(formErrors.period_name))} /></Field>
      <Field id="status" label="Status" error={formErrors.status}><select id="status" value={values.status} onChange={(event) => updateField('status', event.target.value)} aria-invalid={Boolean(formErrors.status)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.status))}>{statusOptions.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}</select></Field>
      <Field id="timetable_id" label="Timetable" error={formErrors.timetable_id}><select id="timetable_id" value={values.timetable_id} onChange={(event) => updateField('timetable_id', event.target.value)} aria-invalid={Boolean(formErrors.timetable_id)} disabled={disabled} className={inputClass(Boolean(formErrors.timetable_id))}><option value="">{timetablesLoading ? 'Loading timetables…' : 'Select timetable'}</option>{timetables?.length === 0 && <option disabled value="__empty_timetables">No timetables available</option>}{timetables?.map((timetable) => <option key={timetable.id} value={timetable.id}>{timetable.timetable_name ?? `Timetable #${timetable.id}`}</option>)}</select></Field>
    </div>
    {mode === 'create' ? <><div className="border-t border-slate-200 pt-5"><h2 className="text-base font-semibold text-slate-900">Period schedule</h2><p className="mt-1 text-sm text-slate-500">These fields are required by the periods database schema and are not returned by the current read response.</p></div><div className="grid gap-5 md:grid-cols-2"><Field id="day_of_week" label="Day of week" error={formErrors.day_of_week}><select id="day_of_week" value={values.day_of_week} onChange={(event) => updateField('day_of_week', event.target.value)} aria-invalid={Boolean(formErrors.day_of_week)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.day_of_week))}><option value="">Select day</option>{dayOptions.map((day) => <option key={day} value={day}>{statusLabel(day)}</option>)}</select></Field><Field id="start_time" label="Start time" error={formErrors.start_time}><input id="start_time" type="time" value={values.start_time} onChange={(event) => updateField('start_time', event.target.value)} aria-invalid={Boolean(formErrors.start_time)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.start_time))} /></Field><Field id="end_time" label="End time" error={formErrors.end_time}><input id="end_time" type="time" value={values.end_time} onChange={(event) => updateField('end_time', event.target.value)} aria-invalid={Boolean(formErrors.end_time)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.end_time))} /></Field><Field id="period_order" label="Period order (optional)" error={formErrors.period_order}><input id="period_order" type="number" min="0" max="255" step="1" value={values.period_order} onChange={(event) => updateField('period_order', event.target.value)} aria-invalid={Boolean(formErrors.period_order)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.period_order))} /></Field></div></> : <p className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">Day, start time, end time, and period order are stored by the backend but intentionally omitted from the current Period response, so edit preserves the response-backed fields only.</p>}
    <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-xs text-slate-500">The Period service requires a name and timetable assignment.</p><button type="submit" disabled={disabled} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">{isSubmitting ? 'Saving…' : submitLabel}</button></div>
  </form>;
}

function inputClass(hasError: boolean): string { return `w-full rounded-xl border ${hasError ? 'border-red-300' : 'border-slate-200'} bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white disabled:cursor-not-allowed disabled:opacity-60`; }
function statusLabel(status: string): string { return status.charAt(0).toUpperCase() + status.slice(1); }
function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) { return <div><label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label><div className="mt-2">{children}</div>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</div>; }
