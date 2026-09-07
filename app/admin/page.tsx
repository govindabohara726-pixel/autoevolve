import { redirect } from "next/navigation";
import { StatCard } from "@/components/StatCard";
import { RunEvolutionButton } from "@/components/AdminActions";
import { createAdminClient } from "@/lib/supabase/admin";
import { hasSupabaseAdminConfig } from "@/lib/config";
import { SITE } from "@/lib/site";

export const dynamic = "force-dynamic";

export default async function Admin(){
  if(!hasSupabaseAdminConfig()) redirect("/setup");
  const db=createAdminClient();
  const [{count:pages},{count:published},{count:pending},{data:settings},{data:actions}]=await Promise.all([
    db.from("content_items").select("id",{count:"exact",head:true}),
    db.from("content_items").select("id",{count:"exact",head:true}).eq("status","published"),
    db.from("ai_actions").select("id",{count:"exact",head:true}).eq("status","pending_approval"),
    db.from("site_settings").select("autonomy_level").eq("site_id",SITE.defaultSiteId).single(),
    db.from("ai_actions").select("id,action_type,status,summary,created_at").order("created_at",{ascending:false}).limit(8)
  ]);
  return <><div className="admin-header"><div><h1>Evolution overview</h1><p className="muted">Control center for your autonomous website.</p></div><RunEvolutionButton/></div><div className="stats"><StatCard label="All pages" value={pages||0}/><StatCard label="Published" value={published||0}/><StatCard label="Awaiting approval" value={pending||0}/><StatCard label="Autonomy level" value={`${settings?.autonomy_level||2}/4`} hint="Change in Settings"/></div><section className="panel"><h2>Recent AI actions</h2><table className="table"><thead><tr><th>Action</th><th>Summary</th><th>Status</th><th>Time</th></tr></thead><tbody>{actions?.map(a=><tr key={a.id}><td>{a.action_type}</td><td>{a.summary}</td><td><span className={`status ${a.status}`}>{a.status}</span></td><td>{new Date(a.created_at).toLocaleString()}</td></tr>)}</tbody></table></section></>;
}
