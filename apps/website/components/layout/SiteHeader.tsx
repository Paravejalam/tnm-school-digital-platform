export function SiteHeader() {
  return (
    <header className="border-b border-slate-200 bg-white/90 backdrop-blur">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <div>
          <p className="text-lg font-semibold text-slate-900">T.N. Memorial Public School</p>
          <p className="text-sm text-slate-600">Digital platform foundation</p>
        </div>
        <nav className="flex gap-4 text-sm text-slate-600">
          <a href="#" className="transition hover:text-blue-600">Home</a>
          <a href="#" className="transition hover:text-blue-600">About</a>
          <a href="#" className="transition hover:text-blue-600">Contact</a>
        </nav>
      </div>
    </header>
  );
}
