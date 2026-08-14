'use client';

import React from 'react';
import { holidayCalendarErrorMessage, listHolidayCalendarSessions } from '../services/holidayCalendar';
import type {
  HolidayCalendarFormValues,
  HolidayCalendarRecord,
  HolidayCalendarSessionOption,
  HolidayCalendarStatus,
  HolidayCalendarType,
} from '../types/holidayCalendar';

const statusOptions: HolidayCalendarStatus[] = ['active', 'inactive'];
const holidayTypeOptions: HolidayCalendarType[] = ['national', 'regional', 'school', 'religious', 'other'];

const emptyValues: HolidayCalendarFormValues = {
  holiday_name: '',
  academic_session_id: '',
  holiday_date: '',
  holiday_type: 'school',
  is_recurring: false,
  description: '',
  status: 'active',
};

export function toFormValues(value: Partial<HolidayCalendarFormValues> | Partial<HolidayCalendarRecord> | null | undefined): HolidayCalendarFormValues {
  const candidate = value as Partial<HolidayCalendarFormValues> | undefined;
  return {
    holiday_name: String(value?.holiday_name ?? ''),
    academic_session_id: String(value?.academic_session_id ?? ''),
    holiday_date: String(candidate?.holiday_date ?? ''),
    holiday_type: holidayTypeOptions.includes(candidate?.holiday_type as HolidayCalendarType) ? candidate?.holiday_type as HolidayCalendarType : 'school',
    is_recurring: (candidate?.is_recurring as unknown) === true || (candidate?.is_recurring as unknown) === 1 || (candidate?.is_recurring as unknown) === '1',
    description: String(candidate?.description ?? ''),
    status: value?.status === 'inactive' ? 'inactive' : 'active',
  };
}

function isValidIsoDate(value: string): boolean {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return false;
  const [year, month, day] = value.split('-').map(Number);
  const date = new Date(Date.UTC(year, month - 1, day));
  return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day;
}

export function validateHolidayCalendarForm(values: HolidayCalendarFormValues, mode: 'create' | 'edit' = 'create', sessions?: HolidayCalendarSessionOption[] | null): Record<string, string> {
  const errors: Record<string, string> = {};
  const sessionId = Number(values.academic_session_id);

  if (!values.holiday_name.trim()) errors.holiday_name = 'Holiday name is required.';
  if (!values.academic_session_id.trim()) errors.academic_session_id = 'Academic session is required.';
  else if (!Number.isInteger(sessionId) || sessionId < 1) errors.academic_session_id = 'Select a valid academic session.';
  else if (sessions && !sessions.some((session) => session.id === sessionId)) errors.academic_session_id = 'Select an available academic session.';
  if (!statusOptions.includes(values.status)) errors.status = 'Status must be active or inactive.';

  if (mode === 'create') {
    if (!values.holiday_date.trim()) errors.holiday_date = 'Holiday date is required.';
    else if (!isValidIsoDate(values.holiday_date)) errors.holiday_date = 'Enter a valid date.';
    if (!holidayTypeOptions.includes(values.holiday_type)) errors.holiday_type = 'Select a valid holiday type.';
  }

  return errors;
}

export function useHolidayCalendarSessions() {
  const [sessions, setSessions] = React.useState<HolidayCalendarSessionOption[] | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  const loadSessions = React.useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      setSessions(await listHolidayCalendarSessions());
    } catch (requestError: unknown) {
      setSessions(null);
      setError(holidayCalendarErrorMessage(requestError, 'Unable to load academic sessions.'));
    } finally {
      setLoading(false);
    }
  }, []);

  React.useEffect(() => { void loadSessions(); }, [loadSessions]);
  return { sessions, loading, error, reload: loadSessions };
}

export default function HolidayCalendarForm({
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
  initialValues?: Partial<HolidayCalendarFormValues> | null;
  sessions: HolidayCalendarSessionOption[] | null;
  sessionsLoading: boolean;
  sessionsError: string | null;
  onRetrySessions: () => void;
  serverErrors?: Record<string, string>;
  submitLabel: string;
  isSubmitting: boolean;
  onSubmit: (values: HolidayCalendarFormValues) => Promise<void> | void;
}) {
  const [values, setValues] = React.useState<HolidayCalendarFormValues>(() => toFormValues(initialValues ?? emptyValues));
  const [errors, setErrors] = React.useState<Record<string, string>>({});

  React.useEffect(() => {
    setValues(toFormValues(initialValues ?? emptyValues));
    setErrors({});
  }, [initialValues]);

  function updateTextField(field: Exclude<keyof HolidayCalendarFormValues, 'is_recurring'>, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: '' }));
  }

  function updateRecurring(value: boolean) {
    setValues((current) => ({ ...current, is_recurring: value }));
    setErrors((current) => ({ ...current, is_recurring: '' }));
  }

  async function handleSubmit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors = validateHolidayCalendarForm(values, mode, sessions);
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0 || !sessions) return;
    await onSubmit(values);
  }

  const formErrors = { ...serverErrors, ...errors };
  const disabled = isSubmitting || sessionsLoading || !sessions;

  return (
    <form onSubmit={handleSubmit} noValidate className="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      {sessionsError && <div className="flex flex-col gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 sm:flex-row sm:items-center sm:justify-between"><span>{sessionsError}</span><button type="button" onClick={onRetrySessions} className="rounded-lg border border-red-200 bg-white px-3 py-2 font-semibold text-red-700 hover:bg-red-100">Retry sessions</button></div>}

      <div className="grid gap-5 md:grid-cols-2">
        <Field id="holiday_name" label="Holiday name" error={formErrors.holiday_name}><input id="holiday_name" value={values.holiday_name} onChange={(event) => updateTextField('holiday_name', event.target.value)} aria-invalid={Boolean(formErrors.holiday_name)} maxLength={200} disabled={isSubmitting} placeholder="e.g. Republic Day" className={inputClass(Boolean(formErrors.holiday_name))} /></Field>
        <Field id="status" label="Status" error={formErrors.status}><select id="status" value={values.status} onChange={(event) => updateTextField('status', event.target.value)} aria-invalid={Boolean(formErrors.status)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.status))}>{statusOptions.map((status) => <option key={status} value={status}>{statusLabel(status)}</option>)}</select></Field>
        <Field id="academic_session_id" label="Academic session" error={formErrors.academic_session_id}><select id="academic_session_id" value={values.academic_session_id} onChange={(event) => updateTextField('academic_session_id', event.target.value)} aria-invalid={Boolean(formErrors.academic_session_id)} disabled={disabled} className={inputClass(Boolean(formErrors.academic_session_id))}><option value="">{sessionsLoading ? 'Loading sessions…' : 'Select academic session'}</option>{sessions?.map((session) => <option key={session.id} value={session.id}>{session.session_name ?? `Session #${session.id}`}</option>)}</select></Field>
      </div>

      {mode === 'create' ? <div className="space-y-5 border-t border-slate-200 pt-5"><div><h2 className="text-sm font-semibold text-slate-900">Holiday details</h2><p className="mt-1 text-xs text-slate-500">These values follow the Holiday Calendar database contract.</p></div><div className="grid gap-5 md:grid-cols-2"><Field id="holiday_date" label="Holiday date" error={formErrors.holiday_date}><input id="holiday_date" type="date" value={values.holiday_date} onChange={(event) => updateTextField('holiday_date', event.target.value)} aria-invalid={Boolean(formErrors.holiday_date)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.holiday_date))} /></Field><Field id="holiday_type" label="Holiday type" error={formErrors.holiday_type}><select id="holiday_type" value={values.holiday_type} onChange={(event) => updateTextField('holiday_type', event.target.value)} aria-invalid={Boolean(formErrors.holiday_type)} disabled={isSubmitting} className={inputClass(Boolean(formErrors.holiday_type))}>{holidayTypeOptions.map((type) => <option key={type} value={type}>{typeLabel(type)}</option>)}</select></Field><div className="md:col-span-2"><label htmlFor="is_recurring" className="flex items-center gap-3 text-sm font-medium text-slate-700"><input id="is_recurring" type="checkbox" checked={values.is_recurring} onChange={(event) => updateRecurring(event.target.checked)} disabled={isSubmitting} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />Recurring holiday</label></div><Field id="description" label="Description"><textarea id="description" value={values.description} onChange={(event) => updateTextField('description', event.target.value)} disabled={isSubmitting} rows={3} className={inputClass(false)} placeholder="Optional description" /></Field></div></div> : <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">The current Holiday Calendar response exposes only the name, academic session and status. Those are the fields available for editing.</div>}

      <div className="flex flex-col gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:items-center sm:justify-between"><p className="text-xs text-slate-500">Academic session is required for every Holiday Calendar record.</p><button type="submit" disabled={disabled} className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300">{isSubmitting ? 'Saving…' : submitLabel}</button></div>
    </form>
  );
}

function inputClass(hasError: boolean): string { return `w-full rounded-xl border ${hasError ? 'border-red-300' : 'border-slate-200'} bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white disabled:cursor-not-allowed disabled:opacity-60`; }
function statusLabel(status: HolidayCalendarStatus): string { return status.charAt(0).toUpperCase() + status.slice(1); }
function typeLabel(type: HolidayCalendarType): string { return type.charAt(0).toUpperCase() + type.slice(1); }
function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) { return <div><label htmlFor={id} className="block text-sm font-medium text-slate-700">{label}</label><div className="mt-2">{children}</div>{error && <p className="mt-1 text-xs text-red-600">{error}</p>}</div>; }
