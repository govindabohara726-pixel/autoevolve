import type { MetadataRoute } from "next";
import { createClient } from "@/lib/supabase/server";
import { hasSupabasePublicConfig } from "@/lib/config";
import { SITE } from "@/lib/site";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const base: MetadataRoute.Sitemap = [
    { url: SITE.url, lastModified: new Date(), changeFrequency: "daily", priority: 1 },
    { url: `${SITE.url}/articles`, lastModified: new Date(), changeFrequency: "daily", priority: 0.8 }
  ];

  if (!hasSupabasePublicConfig()) return base;

  try {
    const db = await createClient();
    const { data, error } = await db
      .from("content_items")
      .select("slug,updated_at")
      .eq("status", "published");
    if (error) return base;

    return [
      ...base,
      ...(data || []).map(x => ({
        url: `${SITE.url}/articles/${x.slug}`,
        lastModified: new Date(x.updated_at),
        changeFrequency: "weekly" as const,
        priority: 0.7
      }))
    ];
  } catch {
    return base;
  }
}
