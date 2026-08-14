import type { Metadata } from 'next';
import { Breadcrumbs, CTASection, EmptyState, PageIntro } from '../../../components/ui/PublicBlocks';
import { siteConfig } from '../../../lib/config/site';

export const metadata: Metadata = { title: `Leadership | ${siteConfig.name}`, description: `Public leadership information for ${siteConfig.name}, subject to school-office approval.` };

export default function LeadershipPage() {
  return <>
    <PageIntro eyebrow="Leadership" title="The people who guide the school community." description="Leadership profiles will be published here only after names, roles, and biographies have been verified and approved for public display." />
    <Breadcrumbs current="Leadership" />
    <section className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24"><EmptyState title="Leadership information will be updated by the school office." description="No approved leadership profiles are currently available in the repository. No names or biographies are being inferred." /></section>
    <CTASection eyebrow="Stay connected" title="Explore the rest of the public website." description="Learn about the school’s public academic information or review the current admissions guidance." primaryHref="/academics" primaryLabel="Academics" secondaryHref="/admissions" secondaryLabel="Admissions" />
  </>;
}
