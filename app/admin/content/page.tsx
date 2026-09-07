import { redirect } from "next/navigation";
import { GenerateContentForm } from "@/components/AdminActions";
import { createAdminClient } from "@/lib/supabase/admin";
import { hasSupabaseAdminConfig } from "@/lib/config";

export const dynamic = "force-dynamic";

export default async function ContentAdmin(){
  if(!hasSupabaseAdminConfig()) redirect("/setup");
  const db=createAdminClient();
  const {data}=await db.from("content_items").select("id,title,slug,status,health_score,seo_score,updated_at").order("updated_at",{ascending:false}).limit(100);
  return <><div className="admin-header"><div><h1>Content</h1><p className="muted">Generate, score and monitor every page.</p></div></div><section className="panel"><h2>Generate new draft</h2><GenerateContentForm/></section><section className="panel"><table className="table"><thead><tr><th>Title</th><th>Status</th><th>Health</th><th>SEO</th><th>Updated</th></tr></thead><tbody>{data?.map(x=><tr key={x.id}><td><strong>{x.title}</strong><div className="muted">/{x.slug}</div></td><td><span className="status">{x.status}</span></td><td className="score">{x.health_score}</td><td className="score">{x.seo_score}</td><td>{new Date(x.updated_at).toLocaleDateString()}</td></tr>)}</tbody></table></section></>;
}
