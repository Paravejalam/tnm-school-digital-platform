import type { Metadata } from 'next';
import { Breadcrumbs, CTASection, FeatureCard, PageIntro, SectionHeading } from '../../../components/ui/PublicBlocks';
import { publicContent } from '../../../lib/config/content';
import { siteConfig } from '../../../lib/config/site';

export const metadata: Metadata = { title: `About | ${siteConfig.name}`, description: `Learn about the public information and community focus of ${siteConfig.name}.` };

export default function AboutPage() {
  return <>
    <PageIntro eyebrow="About the school" title="A clear public introduction to the school community." description="This page brings together the approved public overview of T.N. Memorial Public School. Additional history, facilities, and programme details will be published after school-office confirmation." />
    <Breadcrumbs current="About" />
    <section className="mx-auto grid max-w-7xl gap-10 px-5 py-16 sm:px-8 sm:py-24 lg:grid-cols-[0.85fr_1.15fr]"><SectionHeading eyebrow={publicContent.introduction.eyebrow} title={publicContent.introduction.title} /><div className="space-y-5 text-base leading-8 text-slate-600">{publicContent.introduction.paragraphs.map((paragraph) => <p key={paragraph}>{paragraph}</p>)}</div></section>
    <section className="bg-sand"><div className="mx-auto max-w-7xl px-5 py-16 sm:px-8 sm:py-24"><SectionHeading eyebrow="What this website shares" title="Useful information, presented with care." description="The public website will grow with verified content from the school office. Until then, it keeps the information boundary clear." /><div className="mt-10 grid gap-5 md:grid-cols-3">{publicContent.highlights.map((item) => <FeatureCard key={item.title} item={item} />)}</div></div></section>
    <CTASection eyebrow="Continue exploring" title="Find the information you need." description="Review the current admissions guidance or use the contact page for the school’s approved enquiry channel when it is available." primaryHref="/admissions" primaryLabel="Admissions" secondaryHref="/contact" secondaryLabel="Contact" />
  </>;
}
