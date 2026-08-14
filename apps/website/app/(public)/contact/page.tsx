import type { Metadata } from 'next';
import ContactForm from '../../../components/forms/ContactForm';
import { Breadcrumbs, ContactCard, PageIntro } from '../../../components/ui/PublicBlocks';
import { publicContact } from '../../../lib/config/content';
import { siteConfig } from '../../../lib/config/site';

export const metadata: Metadata = { title: `Contact | ${siteConfig.name}`, description: `Public contact information and enquiry foundation for ${siteConfig.name}.` };

export default function ContactPage() {
  return <>
    <PageIntro eyebrow="Contact" title="A clear place to plan your enquiry." description="Official phone, email, address, and office-hour details will be published after confirmation by the school office." />
    <Breadcrumbs current="Contact" />
    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24"><div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><ContactCard label="Email" value={publicContact.email} /><ContactCard label="Phone" value={publicContact.phone} /><ContactCard label="Address" value={publicContact.address} /><ContactCard label="Office hours" value={publicContact.officeHours} /></div><div className="mt-10 grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start"><div><p className="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">Before you write</p><h2 className="mt-3 text-3xl font-bold tracking-tight text-ink">Information will be updated by the school office.</h2><p className="mt-4 leading-7 text-slate-600">The contact form below provides an accessible enquiry foundation. It performs local validation only because no approved public contact endpoint is available.</p></div><ContactForm /></div></section>
  </>;
}
