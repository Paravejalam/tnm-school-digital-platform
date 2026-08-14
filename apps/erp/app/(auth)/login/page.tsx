'use client';

import React from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../../../hooks/useAuth';

export default function LoginPage() {
  const router = useRouter();
  const { signin, loading, user } = useAuth();
  const [email, setEmail] = React.useState('');
  const [password, setPassword] = React.useState('');
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    if (user) router.push('/');
  }, [user, router]);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setError(null);
    try {
      await signin(email, password);
      router.push('/');
    } catch (err: any) {
      setError(err?.message || 'Login failed');
    }
  }

  return (
    <main className="flex min-h-screen items-center justify-center bg-slate-50 px-6">
      <div className="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 className="text-2xl font-semibold text-slate-900">ERP Login</h1>
        <form onSubmit={handleSubmit} className="mt-4 flex flex-col gap-3">
          <input value={email} onChange={(e) => setEmail(e.target.value)} placeholder="Email" className="w-full p-2 border rounded" />
          <input value={password} onChange={(e) => setPassword(e.target.value)} type="password" placeholder="Password" className="w-full p-2 border rounded" />
          <button disabled={loading} className="mt-2 rounded bg-blue-600 px-4 py-2 text-white">{loading ? 'Signing in...' : 'Sign in'}</button>
          {error && <div className="text-sm text-red-600">{error}</div>}
        </form>
      </div>
    </main>
  );
}
