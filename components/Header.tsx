import Link from "next/link";
import { SITE } from "@/lib/site";

export function Header() {
  return <header className="topbar">
    <Link href="/" className="brand"><span className="brandmark">↗</span>{SITE.name}</Link>
    <nav className="nav"><Link href="/articles">Guides</Link><Link href="/admin">Admin</Link></nav>
  </header>;
}
