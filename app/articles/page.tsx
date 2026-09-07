import { Header } from "@/components/Header";
import { createClient } from "@/lib/supabase/server";
import { hasSupabasePublicConfig } from "@/lib/config";
import Link from "next/link";

export const metadata = { title: "Guides" };

type ArticlePreview = {
  id: string;
  title: string;
  slug: string;
  excerpt: string | null;
};

async function getArticles(): Promise<ArticlePreview[]> {
  if (!hasSupabasePublicConfig()) return [];
  try {
    const db = await createClient();
    const { data, error } = await db
      .from("content_items")
      .select("id,title,slug,excerpt")
      .eq("status", "published")
      .order("published_at", { ascending: false });
    if (error) return [];
    return (data || []) as ArticlePreview[];
  } catch {
    return [];
  }
}

export default async function ArticlesPage() {
  const data = await getArticles();
  return <><Header/><main className="container"><h1 className="section-title">Decision guides</h1><p className="muted">Pages continuously audited for usefulness, freshness and SEO health.</p><div className="article-list" style={{marginTop:30}}>{data.length ? data.map(a=><Link className="article-row" href={`/articles/${a.slug}`} key={a.id}><div><h2>{a.title}</h2><p>{a.excerpt}</p></div><span>→</span></Link>) : <div className="panel">No published guides yet.</div>}</div></main></>;
}
