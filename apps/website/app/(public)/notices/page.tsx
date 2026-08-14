import type { Metadata } from 'next';
import { Breadcrumbs, EmptyState, PageIntro } from '../../../components/ui/PublicBlocks';
import { publicContent } from '../../../lib/config/content';
import { siteConfig } from '../../../lib/config/site';

export const metadata: Metadata = { title: `Notices | ${siteConfig.name}`, description: `Approved public notices from ${siteConfig.name}.` };

export default function NoticesPage() {
  return <>
    <PageIntro eyebrow="Notices" title="School updates, when approved for publication." description="This page is the public home for school notices. It will show only notices supplied and approved by the school office." />
    <Breadcrumbs current="Notices" />
    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">{publicContent.notices.length ? <div className="grid gap-5 md:grid-cols-2">{publicContent.notices.map((notice) => <article key={`${notice.date}-${notice.title}`} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><p className="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{notice.date}</p><h2 className="mt-3 text-xl font-bold text-ink">{notice.title}</h2><p className="mt-3 text-sm leading-7 text-slate-600">{notice.description}</p></article>)}</div> : <EmptyState title="No approved notices are available." description="The school office has not yet provided public notices for this website." />}</section>
  </>;
}
