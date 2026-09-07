import { redirect } from "next/navigation";
import { createAdminClient } from "@/lib/supabase/admin";
import { hasSupabaseAdminConfig } from "@/lib/config";

export const dynamic = "force-dynamic";

export default async function Opportunities(){
  if(!hasSupabaseAdminConfig()) redirect("/setup");
  const db=createAdminClient();
  const {data}=await db.from("opportunities").select("*").order("priority",{ascending:false}).limit(100);
  return <><div className="admin-header"><div><h1>Opportunities</h1><p className="muted">AI-discovered topic gaps. Priority is an internal relevance score, not claimed search volume.</p></div></div><div className="grid">{data?.map(o=><div className="card" key={o.id}><span className="pill">{o.type}</span><h3>{o.topic}</h3><p>{o.reason}</p><strong>Priority {o.priority}/100</strong><div className="muted">Keyword: {o.keyword}</div></div>)}</div></>;
}
