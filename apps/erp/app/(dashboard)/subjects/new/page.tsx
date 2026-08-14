'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import SubjectForm, { validateSubjectForm } from '../../../../components/SubjectForm';
import { createSubject } from '../../../../services/subject';
import type { SubjectFormValues } from '../../../../types/subject';

export default function SubjectCreatePage() {
  const router = useRouter();
  const [submitting, setSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  async function handleSubmit(values: SubjectFormValues) {
    const nextErrors = validateSubjectForm(values);
    if (Object.keys(nextErrors).length > 0) {
      setError('Please correct the highlighted fields.');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const created = await createSubject(values);
      router.push(`/subjects/${created.id}`);
    } catch (err: any) {
      setError(err?.message || 'Unable to create subject.');
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <ProtectedRoute>
      <Layout>
        <div className="space-y-6">
          <div className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Subject management</p>
              <h1 className="mt-2 text-3xl font-semibold text-slate-900">Create subject</h1>
            </div>
            <button type="button" onClick={() => router.push('/subjects')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
              Back to list
            </button>
          </div>

          {error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}

          <SubjectForm submitLabel="Create subject" isSubmitting={submitting} onSubmit={handleSubmit} />
        </div>
      </Layout>
    </ProtectedRoute>
  );
}
