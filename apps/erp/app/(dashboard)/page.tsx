'use client';

import React from 'react';
import Layout from '../../components/Layout';
import ProtectedRoute from '../../components/ProtectedRoute';
import { useAuth } from '../../hooks/useAuth';

function StatusCard({ title, value, detail }: { title: string; value: string; detail: string }) {
  return (
    <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{title}</p>
      <div className="mt-3 text-2xl font-semibold text-slate-900">{value}</div>
      <p className="mt-2 text-sm text-slate-600">{detail}</p>
    </div>
  );
}

export default function DashboardPage() {
  const { user, loading } = useAuth();

  return (
    <ProtectedRoute>
      <Layout>
        <div className="space-y-6">
          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Overview</p>
                <h1 className="mt-2 text-3xl font-semibold text-slate-900">
                  Welcome back{user ? `, ${user.name}` : ''}
                </h1>
              </div>
              <div className="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.14em] text-emerald-700">
                Protected access enabled
              </div>
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <StatusCard
              title="Authentication"
              value="Active"
              detail="The existing ERP auth flow remains in use and protected routes are enforced."
            />
            <StatusCard
              title="Dashboard shell"
              value="Ready"
              detail="A professional administrative shell is in place for module integration later."
            />
            <StatusCard
              title="Navigation"
              value="Responsive"
              detail="Desktop and mobile layouts share the same protected ERP shell structure."
            />
          </div>

          <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex items-center justify-between gap-3">
              <h2 className="text-xl font-semibold text-slate-900">System status</h2>
              {loading ? <span className="text-sm text-slate-500">Checking session…</span> : <span className="text-sm text-emerald-600">Session active</span>}
            </div>

            <div className="mt-5 grid gap-4 md:grid-cols-2">
              <div className="rounded-xl bg-slate-50 p-4">
                <p className="text-sm font-semibold text-slate-800">Authentication</p>
                <p className="mt-2 text-sm text-slate-600">
                  The school ERP uses the existing backend login, refresh, and logout flow without introducing a parallel system.
                </p>
              </div>

              <div className="rounded-xl bg-slate-50 p-4">
                <p className="text-sm font-semibold text-slate-800">Module readiness</p>
                <p className="mt-2 text-sm text-slate-600">
                  The dashboard shell is ready for future module integration, while module pages remain intentionally out of scope for this phase.
                </p>
              </div>
            </div>
          </div>
        </div>
      </Layout>
    </ProtectedRoute>
  );
}
