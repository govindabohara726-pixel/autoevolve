import type { ContentItem } from "@/lib/types";

export type ContentSummary = Pick<ContentItem, "id" | "title" | "slug" | "excerpt" | "primary_keyword" | "updated_at" | "published_at">;

export function backendUrl() {
  return (process.env.BACKEND_URL || process.env.NEXT_PUBLIC_BACKEND_URL || "").replace(/\/$/, "");
}

export function backendConfigured() {
  return Boolean(backendUrl());
}

export async function backendFetch<T>(path: string, fallback: T, revalidate = 300): Promise<T> {
  const base = backendUrl();
  if (!base) return fallback;
  try {
    const response = await fetch(`${base}/api${path.startsWith("/") ? path : `/${path}`}`, {
      headers: { accept: "application/json" },
      next: { revalidate }
    });
    if (!response.ok) return fallback;
    return await response.json() as T;
  } catch {
    return fallback;
  }
}

export async function getPublishedArticle(slug: string) {
  return backendFetch<ContentItem | null>(`/content/${encodeURIComponent(slug)}`, null, 120);
}
