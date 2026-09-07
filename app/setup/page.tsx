import type { Metadata } from "next";
import Link from "next/link";
import { backendUrl } from "@/lib/backend";

export const metadata: Metadata = { title: "Deployment setup", robots: { index: false, follow: false } };
function status(ok:boolean){return ok?"✓ Ready":"✕ Missing"}
export const dynamic="force-dynamic";

export default async function SetupPage(){
  const base=backendUrl();
  let reachable=false; let health:{database?:boolean;ai?:boolean}|null=null;
  if(base){try{const r=await fetch(`${base}/api/health`,{cache:"no-store"});if(r.ok){health=await r.json();reachable=true}}catch{}}
  const checks=[["Laravel backend URL",Boolean(base)],["Laravel backend reachable",reachable],["MySQL database",Boolean(health?.database)],["AI provider",Boolean(health?.ai)]] as const;
  const ready=checks.every(([,ok])=>ok);
  return <main className="container" style={{paddingTop:70,paddingBottom:70,maxWidth:900}}><span className="eyebrow">DEPLOYMENT HEALTH</span><h1>{ready?"AutoEvolve is connected":"Finish connecting AutoEvolve"}</h1><p className="muted">The public website stays online if the Laravel backend is unavailable. Secrets and database credentials never reach the browser.</p><div className="panel" style={{marginTop:30}}><h2>System checks</h2><div style={{display:"grid",gap:12,marginTop:18}}>{checks.map(([name,ok])=><div key={name} style={{display:"flex",justifyContent:"space-between",gap:20,borderBottom:"1px solid rgba(255,255,255,.08)",paddingBottom:10}}><code>{name}</code><strong>{status(ok)}</strong></div>)}</div></div><div className="panel" style={{marginTop:24}}><h2>Architecture</h2><ol style={{lineHeight:1.9}}><li>Next.js remains the public Vercel website.</li><li>Laravel owns the complete administrator panel and autonomous engine.</li><li>MySQL is the production source of truth for all persistent application data.</li><li>Set <code>BACKEND_URL</code> and <code>NEXT_PUBLIC_BACKEND_URL</code> in Vercel to the Laravel backend URL.</li></ol></div><p style={{marginTop:28}}><Link className="button" href="/">Open website</Link>{base&&<a style={{marginLeft:18}} href={`${base}/admin/login`}>Laravel admin →</a>}</p></main>;
}
