import { Header } from "@/components/Header";
import { backendFetch, type ContentSummary } from "@/lib/backend";
import Link from "next/link";

export const metadata = { title: "Guides" };
export default async function ArticlesPage(){const data=await backendFetch<ContentSummary[]>("/content?limit=100",[]);return <><Header/><main className="container"><h1 className="section-title">Decision guides</h1><p className="muted">Pages continuously audited for usefulness, freshness and SEO health by the Laravel evolution engine.</p><div className="article-list" style={{marginTop:30}}>{data.length?data.map(a=><Link className="article-row" href={`/articles/${a.slug}`} key={a.id}><div><h2>{a.title}</h2><p>{a.excerpt}</p></div><span>→</span></Link>):<div className="panel">No published guides yet.</div>}</div></main></>}
