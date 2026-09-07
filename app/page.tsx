import Link from "next/link";
import { Header } from "@/components/Header";
import { createClient } from "@/lib/supabase/server";

export default async function Home() {
  const supabase = await createClient();
  const { data: articles } = await supabase.from("content_items").select("id,title,slug,excerpt,primary_keyword,updated_at").eq("status","published").order("published_at",{ascending:false}).limit(6);
  return <><Header/><main className="container">
    <section className="hero"><div><span className="eyebrow">AUTONOMOUS DECISION PUBLISHING</span><h1>A website that gets better while you sleep.</h1><p>Useful guides, comparisons and decision tools powered by an internal evolution engine that audits content, improves SEO, discovers gaps and queues safe changes automatically.</p><p><Link className="button" href="/articles">Explore guides</Link></p></div>
    <div className="hero-card"><div className="muted" style={{color:'#aeb7c6',marginBottom:10}}>LIVE EVOLUTION ACTIVITY</div>{["17 pages improved","31 internal links repaired","8 opportunities discovered","4 stale pages queued"].map((x,i)=><div className="activity" key={i}><span className="dot"/><div><strong>{x}</strong><div style={{color:'#98a2b3',fontSize:13}}>Autonomous quality cycle</div></div></div>)}</div></section>
    <section><h2 className="section-title">Built around decisions, not content spam.</h2><div className="grid"><div className="card"><span className="pill">COMPARE</span><h3>Clear comparisons</h3><p>Structured pages designed to help people choose between real options.</p></div><div className="card"><span className="pill">GUIDE</span><h3>Decision guides</h3><p>Evergreen explainers that evolve when clarity, freshness or search performance drops.</p></div><div className="card"><span className="pill">EVOLVE</span><h3>Continuous improvement</h3><p>Every page keeps scores, versions, action history and approval state.</p></div></div></section>
    <section style={{marginTop:70}}><h2 className="section-title">Latest guides</h2><div className="article-list">{articles?.length?articles.map(a=><Link className="article-row" href={`/articles/${a.slug}`} key={a.id}><div><h3>{a.title}</h3><p>{a.excerpt}</p></div><span>→</span></Link>):<div className="panel">No published guides yet. Sign in to Admin and generate your first draft.</div>}</div></section>
  </main></>;
}
