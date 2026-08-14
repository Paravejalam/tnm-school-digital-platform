'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../hooks/useAuth';

export default function Header({
  onMenuToggle,
  mobileMenuOpen,
}: {
  onMenuToggle: () => void;
  mobileMenuOpen: boolean;
}) {
  const router = useRouter();
  const { user, signout } = useAuth();

  async function handleLogout() {
    await signout();
    router.push('/login');
  }

  return (
    <header className="border-b border-slate-200 bg-white/95 backdrop-blur-sm">
      <div className="mx-auto flex max-w-[1600px] items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
        <div className="flex items-center gap-3">
          <button
            type="button"
            aria-label={mobileMenuOpen ? 'Close navigation menu' : 'Open navigation menu'}
            onClick={onMenuToggle}
            className="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 md:hidden"
          >
            <span className="sr-only">Toggle navigation</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" className="h-5 w-5 fill-current">
              <path d="M3 6h18v2H3zm0 5h18v2H3zm0 5h18v2H3z" />
            </svg>
          </button>

          <div>
            <div className="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">T.N. Memorial</div>
            <div className="text-lg font-semibold text-slate-900">School ERP</div>
          </div>
        </div>

        <div className="flex items-center gap-3 sm:gap-4">
          <div className="hidden flex-col items-end text-right sm:flex">
            <span className="text-[10px] font-medium uppercase tracking-[0.16em] text-slate-500">Signed in</span>
            <span className="text-sm font-semibold text-slate-800">{user ? user.name : 'Guest'}</span>
          </div>

          {user ? (
            <button
              type="button"
              onClick={handleLogout}
              className="inline-flex items-center justify-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 transition hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            >
              Logout
            </button>
          ) : (
            <span className="text-sm font-medium text-slate-500">Not signed in</span>
          )}
        </div>
      </div>
    </header>
  );
}
