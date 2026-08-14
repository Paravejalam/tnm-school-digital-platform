import './globals.css';
import type { Metadata } from 'next';
import { AuthProvider } from '../hooks/useAuth';

export const metadata: Metadata = {
  title: 'T.N. Memorial ERP',
  description: 'ERP application',
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en">
      <body className="font-sans antialiased">
        <AuthProvider>{children}</AuthProvider>
      </body>
    </html>
  );
}
