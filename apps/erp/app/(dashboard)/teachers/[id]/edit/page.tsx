'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../../components/Layout';
import ProtectedRoute from '../../../../../components/ProtectedRoute';
import TeacherForm, { toFormValues } from '../../../../../components/TeacherForm';
import { getTeacherById, updateTeacher } from '../../../../../services/teacher';
import type { TeacherFormValues, TeacherRecord } from '../../../../../types/teacher';

export default function TeacherEditPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const [teacher, setTeacher] = React.useState<TeacherRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [submitting, setSubmitting] = React.useState(false);

  React.useEffect(() => {
    if (!params?.id) return;

    async function loadTeacher() {
      setLoading(true);
      setError(null);

      try {
        const result = await getTeacherById(Number(params.id));
        setTeacher(result);
      } catch (err: any) {
        setError(err?.message || 'Teacher not found.');
      } finally {
        setLoading(false);
      }
    }

    void loadTeacher();
  }, [params?.id]);

  async function handleSubmit(values: TeacherFormValues) {
    if (!teacher) return;

    setSubmitting(true);
    setError(null);

    try {
      await updateTeacher(teacher.id, values);
      router.push(`/teachers/${teacher.id}`);
    } catch (err: any) {
      setError(err?.message || 'Unable to update teacher.');
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
              <h1 className="mt-2 text-3xl font-semibold text-slate-900">Edit teacher</h1>
            </div>
            <button type="button" onClick={() => router.push(`/teachers/${teacher?.id ?? ''}`)} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
              Back to detail
            </button>
          </div>

          {error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}

          {loading ? (
            <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading teacher…</div>
          ) : teacher ? (
            <TeacherForm
              initialValues={toFormValues(teacher)}
              submitLabel="Save changes"
              isSubmitting={submitting}
              onSubmit={handleSubmit}
            />
          ) : null}
        </div>
      </Layout>
    </ProtectedRoute>
  );
}
