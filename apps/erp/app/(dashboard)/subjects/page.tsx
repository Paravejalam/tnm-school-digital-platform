'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import Layout from '../../../components/Layout';
import ProtectedRoute from '../../../components/ProtectedRoute';
import { deleteSubject, listSubjects } from '../../../services/subject';
import type { SubjectListResponse, SubjectRecord } from '../../../types/subject';

const PER_PAGE = 10;

function getStatusTone(status: string | null | undefined) {
  switch (status) {
    case 'active':
      return 'bg-emerald-100 text-emerald-700';
    case 'inactive':
      return 'bg-slate-200 text-slate-700';
    default:
      return 'bg-slate-100 text-slate-700';
  }
}

export default function SubjectListPage() {
  const router = useRouter();
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);
  const [subjects, setSubjects] = React.useState<SubjectRecord[]>([]);
  const [page, setPage] = React.useState(1);
  const [total, setTotal] = React.useState(0);
  const [search, setSearch] = React.useState('');
  const [searchInput, setSearchInput] = React.useState('');

  const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));

  const loadSubjects = React.useCallback(async (nextPage = page, nextQuery = search) => {
    setLoading(true);
    setError(null);

    try {
      const result = (await listSubjects({
        page: nextPage,
        per_page: PER_PAGE,
        name: nextQuery,
      })) as SubjectListResponse;

      setSubjects(result.items ?? []);
      setTotal(result.pagination?.total ?? 0);
      setPage(result.pagination?.page ?? nextPage);
    } catch (err: any) {
      setError(err?.message || 'Unable to load subjects.');
      setSubjects([]);
      setTotal(0);
    } finally {
      setLoading(false);
    }
  }, [page, search]);

  React.useEffect(() => {
    void loadSubjects(page, search);
  }, [page, search, loadSubjects]);

  async function handleDelete(item: SubjectRecord) {
    const confirmed = window.confirm(`Delete subject ${item.subject_name ?? 'this entry'}?`);
    if (!confirmed) return;

    try {
      await deleteSubject(item.id);
      setSubjects((current) => current.filter((entry) => entry.id !== item.id));
      setTotal((current) => Math.max(0, current - 1));
    } catch (err: any) {
      setError(err?.message || 'Delete failed.');
    }
  }

  function handleSearch(event: React.FormEvent) {
    event.preventDefault();
    setSearch(searchInput.trim());
    setPage(1);
  }

  return (
    <ProtectedRoute>
      <Layout>
        <div className="space-y-6">
          <div className="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Subject management</p>
              <h1 className="mt-2 text-3xl font-semibold text-slate-900">Subjects</h1>
            </div>

            <button
              type="button"
              onClick={() => router.push('/subjects/new')}
              className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
            >
              Add subject
            </button>
          </div>

          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <form onSubmit={handleSearch} className="flex flex-col gap-3 md:flex-row">
              <input
                value={searchInput}
                onChange={(event) => setSearchInput(event.target.value)}
                placeholder="Search by subject name"
                className="flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-800 outline-none transition focus:border-blue-500 focus:bg-white"
              />
              <button type="submit" className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100">
                Search
              </button>
              {search && (
                <button
                  type="button"
                  onClick={() => {
                    setSearch('');
                    setSearchInput('');
                    setPage(1);
                  }}
                  className="rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100"
                >
                  Clear
                </button>
              )}
            </form>
          </div>

          {error && (
            <div className="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{error}</div>
          )}

          <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div className="overflow-x-auto">
              <table className="min-w-full text-left text-sm text-slate-700">
                <thead className="bg-slate-50 text-xs uppercase tracking-[0.14em] text-slate-500">
                  <tr>
                    <th className="px-4 py-3 font-semibold">Name</th>
                    <th className="px-4 py-3 font-semibold">Code</th>
                    <th className="px-4 py-3 font-semibold">Section</th>
                    <th className="px-4 py-3 font-semibold">Status</th>
                    <th className="px-4 py-3 font-semibold text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {!loading && subjects.length === 0 ? (
                    <tr>
                      <td colSpan={5} className="px-4 py-10 text-center text-slate-500">
                        No subjects found.
                      </td>
                    </tr>
                  ) : (
                    subjects.map((item) => (
                      <tr key={item.id} className="border-t border-slate-200 hover:bg-slate-50">
                        <td className="px-4 py-3 font-medium text-slate-800">
                          <button type="button" onClick={() => router.push(`/subjects/${item.id}`)} className="font-medium text-blue-600 hover:text-blue-700">
                            {item.subject_name ?? 'Unnamed subject'}
                          </button>
                        </td>
                        <td className="px-4 py-3">{item.code ?? '—'}</td>
                        <td className="px-4 py-3">{item.section_id ?? '—'}</td>
                        <td className="px-4 py-3">
                          <span className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${getStatusTone(item.status)}`}>
                            {item.status ?? 'active'}
                          </span>
                        </td>
                        <td className="px-4 py-3">
                          <div className="flex justify-end gap-2">
                            <button type="button" onClick={() => router.push(`/subjects/${item.id}`)} className="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-100">
                              View
                            </button>
                            <button type="button" onClick={() => router.push(`/subjects/${item.id}/edit`)} className="rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                              Edit
                            </button>
                            <button type="button" onClick={() => void handleDelete(item)} className="rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100">
                              Delete
                            </button>
                          </div>
                        </td>
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>

            {!loading && total > 0 && (
              <div className="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                <span>
                  Page {page} of {totalPages} · {total} total
                </span>

                <div className="flex gap-2">
                  <button
                    type="button"
                    onClick={() => setPage((current) => Math.max(1, current - 1))}
                    disabled={page <= 1}
                    className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
                  >
                    Previous
                  </button>
                  <button
                    type="button"
                    onClick={() => setPage((current) => Math.min(totalPages, current + 1))}
                    disabled={page >= totalPages}
                    className="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40"
                  >
                    Next
                  </button>
                </div>
              </div>
            )}
          </div>

          {loading && <div className="text-sm text-slate-500">Loading subjects…</div>}
        </div>
      </Layout>
    </ProtectedRoute>
  );
}
