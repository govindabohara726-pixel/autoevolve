export type ContentStatus = "draft" | "review" | "published" | "archived";
export type RiskLevel = "low" | "medium" | "high";

export type ContentBody = {
  summary: string;
  intro: string;
  sections: Array<{ heading: string; body: string }>;
  faq: Array<{ question: string; answer: string }>;
  takeaways: string[];
  sources?: Array<{ title: string; url: string }>;
};

export type ContentItem = {
  id: string;
  site_id?: string;
  title: string;
  slug: string;
  excerpt: string | null;
  body: ContentBody;
  primary_keyword: string | null;
  search_intent: string | null;
  status: ContentStatus;
  health_score: number;
  seo_score: number;
  risk_score: RiskLevel;
  meta_title: string | null;
  meta_description: string | null;
  published_at: string | null;
  updated_at: string;
};
