import type { Metadata } from 'next';
import AdmissionEnquiryForm from '../../../components/forms/AdmissionEnquiryForm';
import { Breadcrumbs, CTASection, PageIntro } from '../../../components/ui/PublicBlocks';
import { publicContent } from '../../../lib/config/content';
import { siteConfig } from '../../../lib/config/site';

export const metadata: Metadata = { title: `Admissions | ${siteConfig.name}`, description: `Public admissions guidance and enquiry foundation for ${siteConfig.name}.` };

export default function AdmissionsPage() {
  return <>
    <PageIntro eyebrow="Admissions" title="Start with clear, confirmed guidance." description="Admission requirements, fees, dates, and the official process are not yet available as approved public data. This page will be updated by the school office." />
    <Breadcrumbs current="Admissions" />
    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24"><div className="grid gap-6 md:grid-cols-3">{publicContent.admissionSteps.map((step) => <article key={step.number} className="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft"><p className="text-3xl font-bold text-teal-700">{step.number}</p><h2 className="mt-5 text-xl font-bold text-ink">{step.title}</h2><p className="mt-3 text-sm leading-7 text-slate-600">{step.description}</p></article>)}</div></section>
    <section className="bg-sand"><div className="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[0.8fr_1.2fr] lg:items-start"><div><p className="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Enquiry foundation</p><h2 className="mt-3 text-3xl font-bold tracking-tight text-ink sm:text-4xl">Prepare a question for the school.</h2><p className="mt-4 leading-7 text-slate-600">The form performs local validation only. It does not send or store information because no approved public admissions endpoint exists.</p></div><AdmissionEnquiryForm /></div></section>
    <CTASection eyebrow="Need another route?" title="The public contact page is available too." description="Official contact details will appear after school-office confirmation." primaryHref="/contact" primaryLabel="Contact" secondaryHref={siteConfig.erpLoginPath} secondaryLabel="ERP Login" />
  </>;
}
