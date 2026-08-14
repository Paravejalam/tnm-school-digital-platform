'use client';

import React from 'react';
import Link from 'next/link';
import { useAuth } from '../hooks/useAuth';

type NavItem = {
  label: string;
  href?: string;
};

const navigationGroups: Array<{ title: string; items: NavItem[] }> = [
  {
    title: 'Overview',
    items: [{ label: 'Dashboard', href: '/' }],
  },
  {
    title: 'Academic',
    items: [
      { label: 'Academic Sessions', href: '/academic-sessions' },
      { label: 'Academic Classes', href: '/classes' },
      { label: 'Sections', href: '/sections' },
      { label: 'Subjects', href: '/subjects' },
    ],
  },
  {
    title: 'People',
    items: [{ label: 'Students', href: '/students' }, { label: 'Teachers', href: '/teachers' }],
  },
  {
    title: 'Attendance',
    items: [{ label: 'Attendance', href: '/attendance' }, { label: 'Attendance Records', href: '/attendance-records' }],
  },
  {
    title: 'Schedule',
    items: [{ label: 'Timetable', href: '/timetables' }, { label: 'Periods', href: '/periods' }],
  },
  {
    title: 'Calendar',
    items: [{ label: 'Holiday Calendars', href: '/holiday-calendars' }],
  },
  {
    title: 'Administration',
    items: [
      { label: 'Users' },
      { label: 'Roles' },
      { label: 'Permissions', href: '/permissions' },
      { label: 'System Settings' },
      { label: 'Audit Logs' },
    ],
  },
];

export default function Sidebar({
  isMobileOpen,
  onNavigate,
}: {
  isMobileOpen: boolean;
  onNavigate: () => void;
}) {
  const { user } = useAuth();

  return (
    <>
      <button
        type="button"
        aria-label="Close sidebar"
        onClick={onNavigate}
        className={isMobileOpen ? 'fixed inset-0 z-30 bg-slate-900/40 md:hidden' : 'hidden'}
      />

      <aside
        className={[
          'fixed inset-y-0 left-0 z-40 w-72 shrink-0 border-r border-slate-200 bg-white p-4 shadow-lg transition-transform duration-200 md:static md:translate-x-0 md:shadow-none',
          isMobileOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        ].join(' ')}
      >
        <div className="mb-8 flex items-center justify-between border-b border-slate-200 pb-4">
          <div>
            <div className="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">School</div>
            <div className="text-lg font-semibold text-slate-900">ERP Portal</div>
          </div>
          <button
            type="button"
            onClick={onNavigate}
            className="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 hover:bg-slate-100 md:hidden"
            aria-label="Close navigation"
          >
            ×
          </button>
        </div>

        <div className="mb-6 rounded-xl bg-slate-50 p-3">
          <p className="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Active user</p>
          <p className="mt-1 text-sm font-semibold text-slate-800">{user ? user.name : 'Guest'}</p>
        </div>

        <nav aria-label="Primary navigation" className="space-y-5">
          {navigationGroups.map((group) => (
            <div key={group.title}>
              <p className="mb-2 px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">{group.title}</p>
              <ul className="space-y-1">
                {group.items.map((item) => {
                  const content = (
                    <span className="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">
                      {item.label}
                    </span>
                  );

                  if (item.href) {
                    return (
                      <li key={item.label}>
                        <Link href={item.href} onClick={onNavigate} className="block">
                          {content}
                        </Link>
                      </li>
                    );
                  }

                  return (
                    <li key={item.label}>
                      <span aria-disabled="true" className="block cursor-default rounded-lg px-3 py-2 text-sm font-medium text-slate-400">
                        {item.label}
                      </span>
                    </li>
                  );
                })}
              </ul>
            </div>
          ))}
        </nav>
      </aside>
    </>
  );
}
