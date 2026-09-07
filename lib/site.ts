const vercelHost =
  process.env.VERCEL_PROJECT_PRODUCTION_URL || process.env.VERCEL_URL;

const siteUrl = (
  process.env.NEXT_PUBLIC_SITE_URL ||
  (vercelHost ? `https://${vercelHost}` : "http://localhost:3000")
).replace(/\/$/, "");

export const SITE = {
  name: "AutoEvolve",
  tagline: "A website that improves itself.",
  description:
    "Decision guides, comparisons, tools and explainers continuously improved by an autonomous content and SEO engine.",
  url: siteUrl,
  defaultSiteId: "11111111-1111-1111-1111-111111111111"
};
