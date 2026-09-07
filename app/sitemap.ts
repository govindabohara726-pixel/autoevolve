import type { MetadataRoute } from "next";
import { createClient } from "@/lib/supabase/server";
import { SITE } from "@/lib/site";
export default async function sitemap():Promise<MetadataRoute.Sitemap>{const db=await createClient();const {data}=await db.from("content_items").select("slug,updated_at").eq("status","published");return[{url:SITE.url,lastModified:new Date(),changeFrequency:"daily",priority:1},{url:`${SITE.url}/articles`,lastModified:new Date(),changeFrequency:"daily",priority:.8},...(data||[]).map(x=>({url:`${SITE.url}/articles/${x.slug}`,lastModified:new Date(x.updated_at),changeFrequency:"weekly" as const,priority:.7}))]}
