'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import TeacherForm, { validateTeacherForm } from '../../../../components/TeacherForm';
import { createTeacher } from '../../../../services/teacher';
import type { TeacherFormValues } from '../../../../types/teacher';

export default function TeacherCreatePage() {
  const router = useRouter();
  const [submitting, setSubmitting] = React.useState(false);
  const [error, setError] = React.useState<string | null>(null);

  async function handleSubmit(values: TeacherFormValues) {
    const nextErrors = validateTeacherForm(values);
    if (Object.keys(nextErrors).length > 0) {
      setError('Please correct the highlighted fields.');
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const created = await createTeacher(values);
      router.push(`/teachers/${created.id}`);
    } catch (err: any) {
      setError(err?.message || 'Unable to create teacher.');
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
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Teacher management</p>
              <h1 className="mt-2 text-3xl font-semibold text-slate-900">Create teacher</h1>
            </div>
            <button type="button" onClick={() => router.push('/teachers')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
              Back to list
            </button>
          </div>

          {error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}

          <TeacherForm submitLabel="Create teacher" isSubmitting={submitting} onSubmit={handleSubmit} />
        </div>
      </Layout>
    </ProtectedRoute>
  );
}
