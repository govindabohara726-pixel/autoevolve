import type { Metadata } from "next";
import Link from "next/link";

export const metadata: Metadata = {
  title: "Deployment setup",
  robots: { index: false, follow: false }
};

function status(ok: boolean) {
  return ok ? "✓ Configured" : "✕ Missing";
}

export default function SetupPage() {
  const checks = [
    ["NEXT_PUBLIC_SUPABASE_URL", Boolean(process.env.NEXT_PUBLIC_SUPABASE_URL)],
    ["NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY", Boolean(process.env.NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY)],
    ["SUPABASE_SERVICE_ROLE_KEY", Boolean(process.env.SUPABASE_SERVICE_ROLE_KEY)],
    ["AI_API_KEY", Boolean(process.env.AI_API_KEY)],
    ["CRON_SECRET", Boolean(process.env.CRON_SECRET)]
  ] as const;
  const ready = checks.every(([, ok]) => ok);

  return <main className="container" style={{paddingTop:70,paddingBottom:70,maxWidth:900}}>
    <span className="eyebrow">DEPLOYMENT HEALTH</span>
    <h1>{ready ? "AutoEvolve is configured" : "Finish connecting AutoEvolve"}</h1>
    <p className="muted">The public website stays online even when backend services are not configured. This page only shows whether variables exist; it never displays secret values.</p>

    <div className="panel" style={{marginTop:30}}>
      <h2>Environment variables</h2>
      <div style={{display:"grid",gap:12,marginTop:18}}>
        {checks.map(([name, ok]) => <div key={name} style={{display:"flex",justifyContent:"space-between",gap:20,borderBottom:"1px solid rgba(255,255,255,.08)",paddingBottom:10}}><code>{name}</code><strong>{status(ok)}</strong></div>)}
      </div>
    </div>

    <div className="panel" style={{marginTop:24}}>
      <h2>To enable the full system</h2>
      <ol style={{lineHeight:1.9}}>
        <li>Create/connect your Supabase project and run <code>supabase/migrations/001_initial.sql</code>.</li>
        <li>In Vercel → Project Settings → Environment Variables, add the five variables above for Production.</li>
        <li>Set <code>NEXT_PUBLIC_SITE_URL</code> to your final domain when you attach one. Until then AutoEvolve automatically uses the Vercel deployment domain.</li>
        <li>Redeploy after changing environment variables.</li>
        <li>Create a Supabase Auth user and set that user&apos;s <code>profiles.role</code> to <code>admin</code>.</li>
      </ol>
    </div>

    <p style={{marginTop:28}}><Link className="button" href="/">Open website</Link> <Link style={{marginLeft:18}} href="/login">Admin sign in →</Link></p>
  </main>;
}
