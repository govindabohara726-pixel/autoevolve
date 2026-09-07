# AutoEvolve

AutoEvolve is a full-stack autonomous publishing and SEO system.

## Architecture

- **Next.js 16 / Vercel** — public website, articles, metadata, sitemap and graceful frontend fallback.
- **Laravel 13 / PHP 8.3+** — complete admin panel, authentication, content CRUD, AI engine, opportunity discovery, version history and scheduler.
- **MySQL** — the single production persistence layer for admin users, content, content versions, AI actions, opportunities and site settings.
- **DeepSeek/OpenAI-compatible provider** — optional AI generation and improvement through the Laravel backend only.

The public frontend never receives database credentials or the AI key.

## Frontend

```bash
npm install
cp .env.example .env.local
npm run dev
```

Set the Laravel backend URL in `BACKEND_URL` and `NEXT_PUBLIC_BACKEND_URL`.

## Laravel backend

See [`backend/README.md`](backend/README.md).

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan autoevolve:install --email=you@example.com --password='strong-password'
php artisan serve
```

Then visit `/admin/login` on the Laravel backend.

## Production database security

Do not commit database usernames/passwords or API keys. Configure them as environment variables on the PHP/Laravel host. Production defaults to MySQL.
