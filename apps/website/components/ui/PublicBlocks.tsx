import Link from 'next/link';
import type { FeatureItem, GalleryItem, LeadershipItem, NoticeItem } from '../../types/content';

export function SectionHeading({ eyebrow, title, description, align = 'left' }: { eyebrow: string; title: string; description?: string; align?: 'left' | 'center' }) {
  return <div className={align === 'center' ? 'mx-auto max-w-2xl text-center' : 'max-w-2xl'}><p className="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">{eyebrow}</p><h2 className="mt-3 text-3xl font-bold tracking-tight text-ink sm:text-4xl">{title}</h2>{description && <p className="mt-4 text-base leading-7 text-slate-600">{description}</p>}</div>;
}

export function PageIntro({ eyebrow, title, description }: { eyebrow: string; title: string; description: string }) {
  return <section className="border-b border-slate-200 bg-sand"><div className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20"><div className="max-w-3xl"><p className="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">{eyebrow}</p><h1 className="mt-4 text-4xl font-bold tracking-tight text-ink sm:text-6xl">{title}</h1><p className="mt-5 max-w-2xl text-lg leading-8 text-slate-600">{description}</p></div></div></section>;
}

export function CTASection({ eyebrow, title, description, primaryHref, primaryLabel, secondaryHref, secondaryLabel }: { eyebrow: string; title: string; description: string; primaryHref: string; primaryLabel: string; secondaryHref?: string; secondaryLabel?: string }) {
  return <section className="mx-auto max-w-7xl px-5 py-16 sm:px-8"><div className="overflow-hidden rounded-[2rem] bg-navy px-6 py-10 text-white shadow-soft sm:px-12 sm:py-14"><div className="max-w-2xl"><p className="text-xs font-bold uppercase tracking-[0.2em] text-teal-200">{eyebrow}</p><h2 className="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{title}</h2><p className="mt-4 leading-7 text-slate-300">{description}</p><div className="mt-7 flex flex-col gap-3 sm:flex-row"><Link href={primaryHref} className="rounded-full bg-white px-5 py-3 text-center text-sm font-bold text-navy transition hover:bg-sand focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-navy">{primaryLabel} <span aria-hidden="true">→</span></Link>{secondaryHref && secondaryLabel && <Link href={secondaryHref} className="rounded-full border border-white/30 px-5 py-3 text-center text-sm font-bold text-white transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-navy">{secondaryLabel}</Link>}</div></div></div></section>;
}

export function FeatureCard({ item }: { item: FeatureItem }) {
  return <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft transition hover:-translate-y-0.5"><p className="text-xs font-bold uppercase tracking-[0.18em] text-teal-700">{item.eyebrow}</p><h3 className="mt-4 text-xl font-bold text-ink">{item.title}</h3><p className="mt-3 text-sm leading-7 text-slate-600">{item.description}</p></article>;
}

export function LeadershipCard({ item }: { item: LeadershipItem }) {
  return <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-sand text-xl font-bold text-navy" aria-hidden="true">TN</div><p className="mt-6 text-xs font-bold uppercase tracking-[0.18em] text-teal-700">{item.role}</p><h3 className="mt-3 text-xl font-bold text-ink">{item.name}</h3><p className="mt-3 text-sm leading-7 text-slate-600">{item.description}</p></article>;
}

export function NoticeCard({ item }: { item: NoticeItem }) {
  return <article className="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><p className="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{item.date}</p><h3 className="mt-3 text-xl font-bold text-ink">{item.title}</h3><p className="mt-3 text-sm leading-7 text-slate-600">{item.description}</p></article>;
}

export function GalleryCard({ item }: { item: GalleryItem }) {
  return <article className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-soft"><div className="flex aspect-[4/3] items-center justify-center bg-sand p-6 text-center"><div><div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full border border-navy/20 text-sm font-bold text-navy" aria-hidden="true">TN</div><p className="mt-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{item.imageAvailable ? 'School image' : 'Image to be provided by the school'}</p></div></div><div className="p-5"><h3 className="font-bold text-ink">{item.title}</h3><p className="mt-2 text-sm leading-6 text-slate-600">{item.description}</p></div></article>;
}

export function EmptyState({ title, description }: { title: string; description: string }) {
  return <div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"><div className="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-white text-lg font-bold text-teal-700 shadow-sm" aria-hidden="true">—</div><h3 className="mt-4 text-lg font-bold text-ink">{title}</h3><p className="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-600">{description}</p></div>;
}

export function ContactCard({ label, value }: { label: string; value: string }) {
  return <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-soft"><p className="text-xs font-bold uppercase tracking-[0.16em] text-teal-700">{label}</p><p className="mt-3 break-words text-base font-semibold text-ink">{value}</p></div>;
}

export function Breadcrumbs({ current }: { current: string }) {
  return <nav aria-label="Breadcrumb" className="mx-auto max-w-7xl px-5 pt-6 text-sm text-slate-500 sm:px-8"><ol className="flex flex-wrap items-center gap-2"><li><Link href="/" className="font-semibold text-teal-700 hover:text-navy focus:outline-none focus:ring-2 focus:ring-teal-700">Home</Link></li><li aria-hidden="true">/</li><li aria-current="page">{current}</li></ol></nav>;
}
