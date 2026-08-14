export const siteConfig = {
  name: 'T.N. Memorial Public School',
  shortName: 'T.N.M.P School',
  description: 'The public information website for T.N. Memorial Public School.',
  url: process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000',
  erpLoginPath: '/erp/login',
  unavailable: 'To be provided by the school',
};

export const publicNavigation = [
  { label: 'Home', href: '/' },
  { label: 'About', href: '/about' },
  { label: 'Leadership', href: '/leadership' },
  { label: 'Academics', href: '/academics' },
  { label: 'Admissions', href: '/admissions' },
  { label: 'Notices', href: '/notices' },
  { label: 'Gallery', href: '/gallery' },
  { label: 'Contact', href: '/contact' },
] as const;
