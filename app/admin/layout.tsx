import { requireAdmin } from "@/lib/auth";
import { AdminNav } from "@/components/AdminNav";

export const dynamic = "force-dynamic";

export default async function AdminLayout({children}:{children:React.ReactNode}){
  await requireAdmin();
  return <div className="admin-shell"><AdminNav/><main className="admin-main">{children}</main></div>;
}
