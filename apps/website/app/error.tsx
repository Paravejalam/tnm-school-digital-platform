'use client';

export default function Error({ reset }: { error: Error & { digest?: string }; reset: () => void }) {
  return <main className="flex min-h-screen items-center justify-center bg-sand px-6"><div className="max-w-lg text-center"><p className="text-sm font-bold uppercase tracking-[0.2em] text-teal-700">Something went wrong</p><h1 className="mt-4 text-4xl font-bold tracking-tight text-ink">The page could not be loaded.</h1><p className="mt-4 leading-7 text-slate-600">Please try again. If the problem continues, return to the school website home page.</p><button type="button" onClick={() => reset()} className="mt-8 rounded-full bg-navy px-5 py-3 text-sm font-bold text-white hover:bg-ink focus:outline-none focus:ring-2 focus:ring-teal-700 focus:ring-offset-2">Try again</button></div></main>;
}
