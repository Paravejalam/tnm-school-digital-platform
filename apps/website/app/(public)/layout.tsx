import PublicFooter from '../../components/layout/PublicFooter';
import PublicHeader from '../../components/layout/PublicHeader';

export default function PublicLayout({ children }: { children: React.ReactNode }) {
  return <div className="flex min-h-screen flex-col"><PublicHeader /><main className="flex-1">{children}</main><PublicFooter /></div>;
}
