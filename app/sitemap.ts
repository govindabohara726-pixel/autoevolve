import type { MetadataRoute } from "next";
import { backendFetch, type ContentSummary } from "@/lib/backend";
import { SITE } from "@/lib/site";
export default async function sitemap():Promise<MetadataRoute.Sitemap>{const data=await backendFetch<ContentSummary[]>("/content?limit=100",[]);return[{url:SITE.url,lastModified:new Date(),changeFrequency:"daily",priority:1},{url:`${SITE.url}/articles`,lastModified:new Date(),changeFrequency:"daily",priority:.8},...data.map(x=>({url:`${SITE.url}/articles/${x.slug}`,lastModified:new Date(x.updated_at),changeFrequency:"weekly" as const,priority:.7}))]}
