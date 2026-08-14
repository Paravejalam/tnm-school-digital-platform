import type { Metadata } from 'next';
import { Breadcrumbs, CTASection, FeatureCard, PageIntro, SectionHeading } from '../../../components/ui/PublicBlocks';
import { publicContent } from '../../../lib/config/content';
import { siteConfig } from '../../../lib/config/site';

export const metadata: Metadata = { title: `Academics | ${siteConfig.name}`, description: `Public academic information for ${siteConfig.name}, with further details to be confirmed by the school office.` };

export default function AcademicsPage() {
  return <>
    <PageIntro eyebrow="Academics" title="A family-friendly guide to learning information." description="The repository contains academic administration modules, but no approved public curriculum or programme content. This page therefore presents only the public information structure awaiting school confirmation." />
    <Breadcrumbs current="Academics" />
    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24"><SectionHeading eyebrow="Academic information" title="The details will be added carefully." description="Class, subject, and calendar information can be published here once a safe public source and approved wording are available." /><div className="mt-10 grid gap-5 md:grid-cols-3">{publicContent.academics.map((item) => <FeatureCard key={item.title} item={item} />)}</div></section>
    <section className="bg-sand"><div className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-20"><div className="rounded-2xl border border-teal-900/10 bg-white p-6 shadow-soft sm:p-8"><p className="text-xs font-bold uppercase tracking-[0.18em] text-teal-700">Public data boundary</p><h2 className="mt-3 text-2xl font-bold text-ink">Operational ERP records remain private.</h2><p className="mt-3 max-w-2xl text-sm leading-7 text-slate-600">The public website does not call protected student, attendance, teacher, or internal academic APIs. Only approved public projections may be connected here in the future.</p></div></div></section>
    <CTASection eyebrow="Next step" title="Considering the school for your family?" description="Visit admissions for the current public guidance and enquiry foundation." primaryHref="/admissions" primaryLabel="View admissions" secondaryHref="/contact" secondaryLabel="Contact" />
  </>;
}
