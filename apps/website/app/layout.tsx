import './globals.css';
import type { Metadata } from 'next';
import { siteConfig } from '../lib/config/site';

export const metadata: Metadata = {
  title: siteConfig.name,
  description: siteConfig.description,
  metadataBase: new URL(siteConfig.url),
  applicationName: siteConfig.name,
  openGraph: { title: siteConfig.name, description: siteConfig.description, type: 'website', siteName: siteConfig.name },
  robots: { index: true, follow: true },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
