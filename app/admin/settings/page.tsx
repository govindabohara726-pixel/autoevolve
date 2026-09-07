import { redirect } from "next/navigation";
import { createAdminClient } from "@/lib/supabase/admin";
import { hasSupabaseAdminConfig } from "@/lib/config";
import { SITE } from "@/lib/site";

export const dynamic = "force-dynamic";

export default async function Settings(){
  if(!hasSupabaseAdminConfig()) redirect("/setup");
  const db=createAdminClient();
  const {data}=await db.from("site_settings").select("*").eq("site_id",SITE.defaultSiteId).single();
  return <><div className="admin-header"><div><h1>Settings</h1><p className="muted">Safe defaults are configured in the database.</p></div></div><section className="panel"><h2>Autonomy policy</h2><table className="table"><tbody><tr><th>Current level</th><td>{data?.autonomy_level||2} / 4</td></tr><tr><th>Stale after</th><td>{data?.stale_after_days||30} days</td></tr><tr><th>Max actions / cycle</th><td>{data?.max_actions_per_run||5}</td></tr><tr><th>Auto-publish low risk</th><td>{String(data?.auto_publish_low_risk??true)}</td></tr><tr><th>Require approval for URL/deletion</th><td>Always</td></tr></tbody></table><p className="muted">MVP: edit these values in Supabase. A settings mutation form can be added once your preferred autonomy policy is finalized.</p></section></>;
}
