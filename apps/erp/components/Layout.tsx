'use client';

import React from 'react';
import Header from './Header';
import Sidebar from './Sidebar';

export default function Layout({ children }: { children: React.ReactNode }) {
  const [mobileOpen, setMobileOpen] = React.useState(false);

  return (
    <div className="min-h-screen bg-slate-100 text-slate-800">
      <Header onMenuToggle={() => setMobileOpen((value) => !value)} mobileMenuOpen={mobileOpen} />

      <div className="mx-auto flex min-h-[calc(100vh-73px)] max-w-[1600px]">
        <Sidebar isMobileOpen={mobileOpen} onNavigate={() => setMobileOpen(false)} />

        <main className="flex-1 p-4 sm:p-6 lg:p-8">
          <div className="mx-auto max-w-7xl">{children}</div>
        </main>
      </div>
    </div>
  );
}
