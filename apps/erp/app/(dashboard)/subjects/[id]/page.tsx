'use client';

import React from 'react';
import { useParams, useRouter } from 'next/navigation';
import Layout from '../../../../components/Layout';
import ProtectedRoute from '../../../../components/ProtectedRoute';
import { getSubjectById } from '../../../../services/subject';
import type { SubjectRecord } from '../../../../types/subject';

export default function SubjectDetailPage() {
  const router = useRouter();
  const params = useParams<{ id: string }>();
  const [item, setItem] = React.useState<SubjectRecord | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (!params?.id) return;

    async function loadSubject() {
      setLoading(true);
      setError(null);

      try {
        const result = await getSubjectById(Number(params.id));
        setItem(result);
      } catch (err: any) {
        setError(err?.message || 'Subject not found.');
        setItem(null);
      } finally {
        setLoading(false);
      }
    }

    void loadSubject();
  }, [params?.id]);

  return (
    <ProtectedRoute>
      <Layout>
        <div className="space-y-6">
          <div className="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Subject management</p>
              <h1 className="mt-2 text-3xl font-semibold text-slate-900">Subject details</h1>
            </div>
            <div className="flex items-center gap-2">
              <button type="button" onClick={() => router.push('/subjects')} className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                Back to list
              </button>
              {item && (
                <button type="button" onClick={() => router.push(`/subjects/${item.id}/edit`)} className="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                  Edit
                </button>
              )}
            </div>
          </div>

          {error && <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>}

          {loading ? (
            <div className="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500 shadow-sm">Loading subject…</div>
          ) : item ? (
            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
              <div className="grid gap-6 md:grid-cols-2">
                <Info label="Subject name" value={item.subject_name ?? '—'} />
                <Info label="Code" value={item.code ?? '—'} />
                <Info label="Section ID" value={String(item.section_id ?? '—')} />
                <Info label="Status" value={item.status ?? '—'} />
              </div>
            </div>
          ) : null}
        </div>
      </Layout>
    </ProtectedRoute>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  return (
    <div className="rounded-xl border border-slate-200 bg-slate-50 p-4">
      <p className="text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">{label}</p>
      <p className="mt-2 text-base font-semibold text-slate-900">{value}</p>
    </div>
  );
}
