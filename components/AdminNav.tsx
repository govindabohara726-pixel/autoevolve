import Link from "next/link";

export function AdminNav() {
  return <aside className="sidebar">
    <div className="side-title">Evolution OS</div>
    <Link href="/admin">Overview</Link>
    <Link href="/admin/content">Content</Link>
    <Link href="/admin/evolution">AI Activity</Link>
    <Link href="/admin/opportunities">Opportunities</Link>
    <Link href="/admin/settings">Settings</Link>
    <Link href="/">View site ↗</Link>
  </aside>;
}
