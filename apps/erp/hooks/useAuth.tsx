'use client';

import React from 'react';
import authService from '../services/auth';
import api, { setAccessToken } from '../services/api';

type User = { id: number; name: string; email: string } | null;

export function useProvideAuth() {
  const [user, setUser] = React.useState<User>(null);
  const [loading, setLoading] = React.useState(true);

  React.useEffect(() => {
    let mounted = true;

    (async () => {
      try {
        const u = await authService.refresh();
        if (!mounted) return;
        setUser(u);
      } catch (e) {
        setUser(null);
      } finally {
        if (mounted) setLoading(false);
      }
    })();

    return () => {
      mounted = false;
    };
  }, []);

  async function signin(email: string, password: string) {
    setLoading(true);
    try {
      const u = await authService.login(email, password);
      setUser(u);
      return u;
    } finally {
      setLoading(false);
    }
  }

  async function signout() {
    setLoading(true);
    try {
      await authService.logout();
      setUser(null);
    } finally {
      setLoading(false);
    }
  }

  return { user, loading, signin, signout };
}

const AuthContext = React.createContext<ReturnType<typeof useProvideAuth> | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const auth = useProvideAuth();
  return <AuthContext.Provider value={auth}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = React.useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
