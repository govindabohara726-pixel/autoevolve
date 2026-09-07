import { createAdminClient } from "@/lib/supabase/admin";
import { SITE } from "@/lib/site";
import { discoverOpportunities, improveContent } from "@/lib/ai/engine";

export async function runEvolutionCycle() {
  const db = createAdminClient();
  const { data: settings } = await db.from("site_settings").select("autonomy_level, stale_after_days, max_actions_per_run").eq("site_id", SITE.defaultSiteId).single();
  const autonomy = settings?.autonomy_level ?? 2;
  const staleDays = settings?.stale_after_days ?? 30;
  const max = settings?.max_actions_per_run ?? 5;
  const staleBefore = new Date(Date.now() - staleDays * 86400000).toISOString();

  const { data: candidates } = await db
    .from("content_items")
    .select("id,title,health_score,updated_at")
    .eq("site_id", SITE.defaultSiteId)
    .eq("status", "published")
    .or(`health_score.lt.70,updated_at.lt.${staleBefore}`)
    .order("health_score", { ascending: true })
    .limit(max);

  const results: Array<{ id: string; title: string; status: string }> = [];
  for (const item of candidates || []) {
    try {
      const reason = item.health_score < 70 ? `Content health dropped to ${item.health_score}. Improve usefulness and SEO without changing URL intent.` : `Content is older than ${staleDays} days. Refresh evergreen clarity while flagging facts that need external verification.`;
      await improveContent(item.id, reason, autonomy >= 3);
      results.push({ id: item.id, title: item.title, status: autonomy >= 3 ? "auto-published" : "awaiting approval" });
    } catch (error) {
      await db.from("ai_actions").insert({ site_id: SITE.defaultSiteId, content_id: item.id, action_type: "improve", risk_level: "medium", status: "failed", summary: error instanceof Error ? error.message : "Unknown failure" });
    }
  }

  let opportunities: unknown[] = [];
  try { opportunities = await discoverOpportunities(); } catch {}

  return { autonomy, improved: results, opportunitiesFound: opportunities.length };
}
