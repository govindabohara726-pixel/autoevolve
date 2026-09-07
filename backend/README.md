# AutoEvolve Laravel Backend

Laravel 13 backend, Blade admin panel, MySQL persistence, public JSON API, AI content engine, version history and scheduled evolution for AutoEvolve.

## Production database

AutoEvolve uses MySQL as its production source of truth. Keep credentials only in the server environment; never commit them to GitHub.

```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_secret_password
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

If Laravel and MySQL are on the same cPanel account, `DB_HOST=localhost` is normally correct. If the backend is hosted elsewhere, use the database provider's remote MySQL hostname and allow that backend server to connect.

All persistent application and runtime state lives in MySQL: admin users, sessions, cache, queued jobs, failed jobs, content, content versions, AI actions, opportunities and site settings.

## Install

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan autoevolve:install --email=you@example.com --password='use-a-strong-password'
php artisan serve
```

Admin: `/admin/login`  
Health/setup: `/setup`  
Public API: `/api/content`

## AI

```env
AI_BASE_URL=https://api.deepseek.com
AI_API_KEY=...
AI_MODEL=deepseek-chat
```

## Scheduler

One server cron entry is enough:

```cron
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Laravel evaluates the source-controlled schedule and runs `autoevolve:evolve` daily.

## Next.js frontend

On Vercel add:

```env
BACKEND_URL=https://your-laravel-backend.example.com
NEXT_PUBLIC_BACKEND_URL=https://your-laravel-backend.example.com
```

The public Next.js site reads published content from Laravel. It never connects directly to MySQL and never receives database credentials.
