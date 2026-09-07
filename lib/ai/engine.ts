import { createAdminClient } from "@/lib/supabase/admin";
import { SITE } from "@/lib/site";
import { aiComplete, parseAIJson } from "./provider";
import { contentSystem, generatePrompt, improvePrompt, opportunitiesPrompt } from "./prompts";
import type { ContentBody } from "@/lib/types";

type AIArticle = {
  title: string;
  slug: string;
  excerpt: string;
  meta_title: string;
  meta_description: string;
  search_intent: string;
  body: ContentBody;
};

function safeSlug(value: string) {
  return value.toLowerCase().trim().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "").slice(0, 90);
}

export function heuristicScores(article: AIArticle) {
  const text = JSON.stringify(article.body);
  const sections = article.body.sections?.length || 0;
  const faq = article.body.faq?.length || 0;
  const seo = Math.min(100, 45 + sections * 6 + faq * 4 + (article.meta_title ? 8 : 0) + (article.meta_description ? 8 : 0));
  const health = Math.min(100, 50 + Math.min(20, Math.floor(text.length / 700)) + sections * 4 + faq * 2);
  return { seo, health };
}

export async function generateContent(topic: string, keyword: string) {
  const raw = await aiComplete([
    { role: "system", content: contentSystem },
    { role: "user", content: generatePrompt(topic, keyword) }
  ]);
  const article = parseAIJson<AIArticle>(raw);
  article.slug = safeSlug(article.slug || article.title);
  const scores = heuristicScores(article);

  const db = createAdminClient();
  const { data, error } = await db.from("content_items").insert({
    site_id: SITE.defaultSiteId,
    title: article.title,
    slug: article.slug,
    excerpt: article.excerpt,
    body: article.body,
    primary_keyword: keyword,
    search_intent: article.search_intent,
    meta_title: article.meta_title,
    meta_description: article.meta_description,
    status: "draft",
    health_score: scores.health,
    seo_score: scores.seo,
    risk_score: "low"
  }).select().single();
  if (error) throw error;

  await db.from("content_versions").insert({ content_id: data.id, version_number: 1, snapshot: data, reason: "AI generated" });
  await db.from("ai_actions").insert({ site_id: SITE.defaultSiteId, content_id: data.id, action_type: "generate", risk_level: "low", status: "completed", summary: `Generated draft: ${article.title}` });
  return data;
}

export async function improveContent(contentId: string, reason: string, autoPublish = false) {
  const db = createAdminClient();
  const { data: existing, error } = await db.from("content_items").select("*").eq("id", contentId).single();
  if (error || !existing) throw error || new Error("Content not found");

  const raw = await aiComplete([
    { role: "system", content: contentSystem },
    { role: "user", content: improvePrompt(existing, reason) }
  ]);
  const article = parseAIJson<AIArticle>(raw);
  const scores = heuristicScores(article);

  const { count } = await db.from("content_versions").select("id", { count: "exact", head: true }).eq("content_id", contentId);
  await db.from("content_versions").insert({ content_id: contentId, version_number: (count || 0) + 1, snapshot: existing, reason });

  const update = {
    title: article.title || existing.title,
    excerpt: article.excerpt,
    body: article.body,
    search_intent: article.search_intent,
    meta_title: article.meta_title,
    meta_description: article.meta_description,
    health_score: scores.health,
    seo_score: scores.seo,
    status: autoPublish ? "published" : "review",
    published_at: autoPublish ? (existing.published_at || new Date().toISOString()) : existing.published_at
  };

  const { data: changed, error: updateError } = await db.from("content_items").update(update).eq("id", contentId).select().single();
  if (updateError) throw updateError;

  await db.from("ai_actions").insert({
    site_id: SITE.defaultSiteId,
    content_id: contentId,
    action_type: "improve",
    risk_level: "medium",
    status: autoPublish ? "completed" : "pending_approval",
    summary: reason,
    payload: { before_version: (count || 0) + 1 }
  });
  return changed;
}

export async function discoverOpportunities() {
  const db = createAdminClient();
  const { data: content } = await db.from("content_items").select("title").eq("site_id", SITE.defaultSiteId).limit(50);
  const titles = (content || []).map(x => x.title);
  const raw = await aiComplete([
    { role: "system", content: contentSystem },
    { role: "user", content: opportunitiesPrompt(titles) }
  ]);
  const parsed = parseAIJson<{ opportunities: Array<{ topic: string; keyword: string; type: string; reason: string; priority: number }> }>(raw);
  const rows = parsed.opportunities.map(o => ({ ...o, site_id: SITE.defaultSiteId, status: "new" }));
  if (rows.length) await db.from("opportunities").insert(rows);
  return rows;
}
