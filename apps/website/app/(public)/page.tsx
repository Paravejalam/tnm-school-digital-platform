'use client';

export default function HomePage() {
  return (
    <main className="min-h-screen bg-slate-50 px-6 py-16 text-slate-900">
      <div className="mx-auto flex max-w-5xl flex-col gap-6">
        <span className="w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-sm font-medium text-slate-600">
          T.N. Memorial Public School
        </span>
        <h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">
          Public website shell is ready for implementation.
        </h1>
        <p className="max-w-2xl text-lg text-slate-600">
          This bootstrap provides the initial app router shell, theme foundation, and branding-aware structure for the public site.
        </p>
      </div>
    </main>
  );
}
