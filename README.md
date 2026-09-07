# AutoEvolve

AutoEvolve is a multi-tenant Laravel/MySQL SaaS for autonomous content publishing, SEO improvement, versioned AI revisions, opportunity discovery, usage metering, subscriptions, hosted customer sites, and super-admin operations.

## Production architecture

- Laravel 13 backend + SaaS/customer/admin UI
- MySQL as the production source of truth
- Stripe subscriptions and plan enforcement
- OpenAI-compatible AI provider (DeepSeek by default)
- Laravel scheduler + database queue
- Optional Next.js/Vercel marketing/public frontend
- cPanel-ready release package built by GitHub Actions

## cPanel install

Use the `Build cPanel Package` GitHub Actions artifact (`autoevolve-cpanel.zip`) rather than GitHub's generic source ZIP. The artifact includes Composer production dependencies.

1. Extract outside the public web root.
2. Point the domain/subdomain document root to `public/`.
3. Open `/install.php`.
4. Enter MySQL, administrator, AI, Stripe, and SMTP settings.
5. Add the scheduler cron from `INSTALL.md`.

See `backend/INSTALL.md` and `backend/README-FIRST.txt` for the full deployment guide.
