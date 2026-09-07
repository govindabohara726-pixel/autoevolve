# AutoEvolve — autonomous full-stack website MVP

A working foundation for a website that can generate, audit, improve and publish content through a controlled AI evolution loop.

## Included

- Next.js 16 App Router public site
- Supabase Postgres + Auth + Row Level Security
- Admin dashboard
- AI content generation
- AI content improvement
- Content health + SEO scoring
- Full version history before rewrites
- AI action log with low/medium/high risk
- Approval queue for medium-risk rewrites
- Autonomous scheduled evolution endpoint
- AI topic-opportunity discovery
- SEO metadata, canonical URLs, JSON-LD, robots.txt and dynamic sitemap
- DeepSeek/OpenAI-compatible model connection
- Vercel cron example

## 1. Install

```bash
npm install
cp .env.example .env.local
```

## 2. Create Supabase

Create a Supabase project, then run `supabase/migrations/001_init.sql` in the Supabase SQL editor.

Fill `.env.local` with:

- `NEXT_PUBLIC_SUPABASE_URL`
- `NEXT_PUBLIC_SUPABASE_PUBLISHABLE_KEY`
- `SUPABASE_SERVICE_ROLE_KEY` (server only; never expose in browser code)

## 3. Create your admin account

Create an email/password user in Supabase Auth. Then promote that exact user:

```sql
update public.profiles
set role = 'admin'
where id = (select id from auth.users where email = 'YOUR_EMAIL');
```

## 4. Connect AI

The included adapter uses the OpenAI-compatible `/chat/completions` format.

For DeepSeek:

```env
AI_BASE_URL=https://api.deepseek.com
AI_API_KEY=YOUR_KEY
AI_MODEL=deepseek-chat
```

You can point the same adapter at another OpenAI-compatible provider by changing the three variables.

## 5. Run

```bash
npm run dev
```

Open:

- Public site: http://localhost:3000
- Admin: http://localhost:3000/admin
- Login: http://localhost:3000/login

## 6. First workflow

1. Admin → Content → generate a draft.
2. In Supabase, set the draft to `published` for the very first seed article, or trigger an improvement and approve it from AI Activity.
3. Admin → Overview → Run evolution cycle.
4. At autonomy level 2, medium-risk rewrites enter the approval queue.
5. At autonomy level 3+, refresh rewrites may auto-publish. High-risk URL/deletion actions are intentionally not implemented as autonomous operations.

## 7. Scheduled evolution

`vercel.json` schedules `/api/cron/evolve` daily. Set `CRON_SECRET` in production. Vercel cron requests normally authenticate according to your deployment configuration; you can also trigger the endpoint from another scheduler using:

```text
Authorization: Bearer YOUR_CRON_SECRET
```

## Safety model

- Low risk: generate draft, metadata suggestion, internal-link suggestion.
- Medium risk: rewrite existing content → approval at autonomy 1–2, automatic at 3–4.
- High risk: delete URL, redirect, pricing/monetization changes, large site-code changes → deliberately kept out of the autonomous executor.
- Every rewrite stores a full prior snapshot in `content_versions`.

## What to add next for production scale

- Google Search Console data ingestion (OAuth + scheduled sync)
- GA4 or privacy-friendly analytics ingestion
- Web research/search provider for factual freshness
- Citation verification pipeline
- Internal-link graph and orphan-page repair
- Programmatic page templates: `/compare`, `/alternatives`, `/best`, `/tools`
- Background queue (BullMQ/Redis or Supabase Queues) for thousands of jobs
- Stripe/affiliate tracking and revenue-per-page metrics
- A/B experimentation with feature flags
- Image pipeline + licensed asset source
- Error tracking, rate limiting, backups and observability

## Important

This MVP is intentionally conservative: an AI should not be allowed to freely delete production URLs or rewrite application code. Start with autonomy level 2, collect action logs, then raise autonomy when the site's quality controls are proven.
