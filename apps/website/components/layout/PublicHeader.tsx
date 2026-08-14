'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import React from 'react';
import { publicNavigation, siteConfig } from '../../lib/config/site';

export default function PublicHeader() {
  const pathname = usePathname();
  const [open, setOpen] = React.useState(false);

  React.useEffect(() => {
    setOpen(false);
  }, [pathname]);

  return (
    <header className="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
      <div className="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
        <Link href="/" className="flex min-w-0 items-center gap-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">
          <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-navy text-sm font-bold tracking-[0.12em] text-white">TN</span>
          <span className="min-w-0">
            <span className="block truncate text-sm font-bold tracking-tight text-ink sm:text-base">{siteConfig.name}</span>
            <span className="block text-[10px] font-semibold uppercase tracking-[0.18em] text-teal-700">Public school website</span>
          </span>
        </Link>

        <nav aria-label="Primary navigation" className="hidden items-center gap-1 lg:flex">
          {publicNavigation.map((item) => <NavLink key={item.href} href={item.href} active={pathname === item.href}>{item.label}</NavLink>)}
          <Link href={siteConfig.erpLoginPath} className="ml-3 rounded-full bg-navy px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ink focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">ERP Login</Link>
        </nav>

        <button type="button" aria-label={open ? 'Close navigation menu' : 'Open navigation menu'} aria-expanded={open} onClick={() => setOpen((current) => !current)} className="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-ink transition hover:bg-sand focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 lg:hidden">
          <span className="sr-only">{open ? 'Close menu' : 'Open menu'}</span>
          <span className="flex w-5 flex-col gap-1.5" aria-hidden="true"><span className="h-0.5 w-full bg-current" /><span className="h-0.5 w-full bg-current" /><span className="h-0.5 w-full bg-current" /></span>
        </button>
      </div>

      {open && <div className="border-t border-slate-200 bg-white px-5 py-4 lg:hidden"><nav aria-label="Mobile navigation" className="mx-auto flex max-w-7xl flex-col gap-1">{publicNavigation.map((item) => <NavLink key={item.href} href={item.href} active={pathname === item.href} mobile>{item.label}</NavLink>)}<Link href={siteConfig.erpLoginPath} className="mt-3 rounded-xl bg-navy px-4 py-3 text-center text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">ERP Login</Link></nav></div>}
    </header>
  );
}

function NavLink({ href, active, mobile = false, children }: { href: string; active: boolean; mobile?: boolean; children: React.ReactNode }) {
  return <Link href={href} aria-current={active ? 'page' : undefined} className={`${mobile ? 'rounded-xl px-4 py-3' : 'rounded-full px-3 py-2'} text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2 ${active ? 'bg-sand text-navy' : 'text-slate-600 hover:bg-slate-50 hover:text-navy'}`}>{children}</Link>;
}
