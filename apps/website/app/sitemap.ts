import type { MetadataRoute } from 'next';
import { publicNavigation, siteConfig } from '../lib/config/site';

export default function sitemap(): MetadataRoute.Sitemap {
  const lastModified = new Date();
  return publicNavigation.map((item) => ({ url: new URL(item.href, siteConfig.url).toString(), lastModified, changeFrequency: 'monthly', priority: item.href === '/' ? 1 : 0.7 }));
}
