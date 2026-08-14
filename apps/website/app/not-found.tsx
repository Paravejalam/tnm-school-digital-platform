import Link from 'next/link';

export default function NotFound() {
  return <main className="flex min-h-screen items-center justify-center bg-sand px-6"><div className="max-w-lg text-center"><p className="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">404</p><h1 className="mt-4 text-4xl font-bold tracking-tight text-ink">This page could not be found.</h1><p className="mt-4 leading-7 text-slate-600">The page may have moved or may not be part of the public website.</p><Link href="/" className="mt-8 inline-flex rounded-full bg-navy px-5 py-3 text-sm font-bold text-white hover:bg-ink focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">Return home</Link></div></main>;
}
