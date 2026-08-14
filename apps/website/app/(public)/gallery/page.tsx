import type { Metadata } from 'next';
import { Breadcrumbs, EmptyState, PageIntro } from '../../../components/ui/PublicBlocks';
import { publicContent } from '../../../lib/config/content';
import { siteConfig } from '../../../lib/config/site';

export const metadata: Metadata = { title: `Gallery | ${siteConfig.name}`, description: `Approved school imagery and community moments from ${siteConfig.name}.` };

export default function GalleryPage() {
  return <>
    <PageIntro eyebrow="Gallery" title="A visual record built from approved school media." description="No school photographs or media assets are currently available in the repository. Unrelated stock imagery is intentionally not shown." />
    <Breadcrumbs current="Gallery" />
    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24">{publicContent.gallery.length ? <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">{publicContent.gallery.map((item) => <article key={item.title} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><h2 className="text-xl font-bold text-ink">{item.title}</h2><p className="mt-3 text-sm leading-7 text-slate-600">{item.description}</p></article>)}</div> : <EmptyState title="Gallery information will be updated by the school office." description="Approved images and captions will be added when the school’s media collection is ready." />}</section>
  </>;
}
