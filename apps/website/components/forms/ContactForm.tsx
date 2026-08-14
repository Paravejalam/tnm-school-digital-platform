'use client';

import React from 'react';
import type { ContactFormValues } from '../../types/forms';

const initialValues: ContactFormValues = { name: '', email: '', message: '' };

export default function ContactForm() {
  const [values, setValues] = React.useState<ContactFormValues>(initialValues);
  const [errors, setErrors] = React.useState<Partial<Record<keyof ContactFormValues, string>>>({});
  const [notice, setNotice] = React.useState<string | null>(null);

  function update(field: keyof ContactFormValues, value: string) {
    setValues((current) => ({ ...current, [field]: value }));
    setErrors((current) => ({ ...current, [field]: undefined }));
    setNotice(null);
  }

  function submit(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const nextErrors: Partial<Record<keyof ContactFormValues, string>> = {};
    if (!values.name.trim()) nextErrors.name = 'Please enter your name.';
    if (!/^\S+@\S+\.\S+$/.test(values.email.trim())) nextErrors.email = 'Please enter a valid email address.';
    if (!values.message.trim()) nextErrors.message = 'Please enter a message.';
    setErrors(nextErrors);
    if (Object.keys(nextErrors).length > 0) return;
    setNotice('This message was not sent. The public contact service has not yet been approved or connected.');
  }

  return <form onSubmit={submit} noValidate className="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-soft sm:p-8"><div><h2 className="text-2xl font-bold text-ink">Send an enquiry</h2><p className="mt-2 text-sm leading-6 text-slate-600">The form is ready for a future approved contact endpoint. No information is transmitted from this page.</p></div>{notice && <div role="status" className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">{notice}</div>}<div className="grid gap-5 sm:grid-cols-2"><Field id="contact-name" label="Your name" error={errors.name}><input id="contact-name" value={values.name} onChange={(event) => update('name', event.target.value)} aria-invalid={Boolean(errors.name)} className={inputClass(Boolean(errors.name))} /></Field><Field id="contact-email" label="Email address" error={errors.email}><input id="contact-email" type="email" value={values.email} onChange={(event) => update('email', event.target.value)} aria-invalid={Boolean(errors.email)} className={inputClass(Boolean(errors.email))} /></Field></div><Field id="contact-message" label="Message" error={errors.message}><textarea id="contact-message" rows={5} value={values.message} onChange={(event) => update('message', event.target.value)} aria-invalid={Boolean(errors.message)} className={inputClass(Boolean(errors.message))} /></Field><button type="submit" className="rounded-full bg-navy px-5 py-3 text-sm font-bold text-white transition hover:bg-ink focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">Prepare enquiry</button></form>;
}

function inputClass(hasError: boolean) { return `w-full rounded-xl border ${hasError ? 'border-red-400' : 'border-slate-200'} bg-slate-50 px-4 py-3 text-sm text-ink outline-none transition focus:border-teal-700 focus:bg-white focus:ring-2 focus:ring-teal-700/10`; }
function Field({ id, label, error, children }: { id: string; label: string; error?: string; children: React.ReactNode }) { return <div><label htmlFor={id} className="block text-sm font-bold text-ink">{label}</label><div className="mt-2">{children}</div>{error && <p className="mt-1 text-xs text-red-700">{error}</p>}</div>; }
