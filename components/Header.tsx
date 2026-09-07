import Link from "next/link";
import { SITE } from "@/lib/site";

export function Header() {
  const admin=(process.env.NEXT_PUBLIC_BACKEND_URL||"").replace(/\/$/,"");
  return <header className="topbar"><Link href="/" className="brand"><span className="brandmark">↗</span>{SITE.name}</Link><nav className="nav"><Link href="/articles">Guides</Link>{admin?<a href={`${admin}/admin`}>Admin</a>:<Link href="/setup">Setup</Link>}</nav></header>;
}
