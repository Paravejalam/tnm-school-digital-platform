import Link from 'next/link';
import { publicNavigation, siteConfig } from '../../lib/config/site';
import { publicContact } from '../../lib/config/content';

export default function PublicFooter() {
  return (
    <footer className="border-t border-slate-200 bg-navy text-white">
      <div className="mx-auto grid max-w-7xl gap-10 px-5 py-12 sm:px-8 md:grid-cols-[1.4fr_1fr_1fr]">
        <div><div className="flex items-center gap-3"><span className="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-sm font-bold tracking-[0.12em] text-navy">TN</span><span className="font-bold">{siteConfig.name}</span></div><p className="mt-5 max-w-sm text-sm leading-7 text-slate-300">A clear, accessible public information space for the school community.</p></div>
        <div><h2 className="text-sm font-bold uppercase tracking-[0.16em] text-slate-300">Explore</h2><nav aria-label="Footer navigation" className="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 text-sm text-slate-300">{publicNavigation.slice(1, 7).map((item) => <Link key={item.href} href={item.href} className="transition hover:text-white focus:outline-none focus:ring-2 focus:ring-white">{item.label}</Link>)}</nav></div>
        <div><h2 className="text-sm font-bold uppercase tracking-[0.16em] text-slate-300">Contact</h2><div className="mt-4 space-y-3 text-sm text-slate-300"><p>{publicContact.email}</p><p>{publicContact.phone}</p><p>{publicContact.address}</p></div></div>
      </div>
      <div className="border-t border-white/10"><div className="mx-auto flex max-w-7xl flex-col gap-2 px-5 py-5 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between sm:px-8"><p>© {new Date().getFullYear()} {siteConfig.name}. Public information is subject to school approval.</p><Link href={siteConfig.erpLoginPath} className="font-semibold text-slate-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-white">ERP Login →</Link></div></div>
    </footer>
  );
}
