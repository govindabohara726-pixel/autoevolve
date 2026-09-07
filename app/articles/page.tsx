import { Header } from "@/components/Header";
import { createClient } from "@/lib/supabase/server";
import Link from "next/link";

export const metadata = { title: "Guides" };
export default async function ArticlesPage(){const db=await createClient();const {data}=await db.from("content_items").select("id,title,slug,excerpt,primary_keyword,updated_at").eq("status","published").order("published_at",{ascending:false});return <><Header/><main className="container"><h1 className="section-title">Decision guides</h1><p className="muted">Pages continuously audited for usefulness, freshness and SEO health.</p><div className="article-list" style={{marginTop:30}}>{data?.map(a=><Link className="article-row" href={`/articles/${a.slug}`} key={a.id}><div><h2>{a.title}</h2><p>{a.excerpt}</p></div><span>→</span></Link>)}</div></main></>}
