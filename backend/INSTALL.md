# AutoEvolve SaaS — cPanel Installation

AutoEvolve can run as a complete Laravel/MySQL SaaS on cPanel. The separate Next.js frontend is optional.

## Server requirements

- PHP 8.3 or newer
- MySQL 8 / compatible MariaDB
- PHP extensions: PDO MySQL, mbstring, OpenSSL, tokenizer, JSON, XML, Ctype, BCMath
- Apache `mod_rewrite`
- Composer 2 (only required if you install from source; the release ZIP includes `vendor/`)
- Ability to create one cron job
- HTTPS certificate strongly recommended

## Recommended folder layout

Do **not** expose the Laravel project root directly to the web.

```text
/home/CPANEL_USER/autoevolve/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/        ← document root
├── resources/
├── routes/
├── storage/
├── vendor/
└── artisan
```

Create a domain/subdomain in cPanel and set its document root to:

```text
/home/CPANEL_USER/autoevolve/public
```

For a single-domain installation, that domain can serve the marketing site, customer app, public hosted sites and super-admin.

## Fast installation (release ZIP)

1. Download the `autoevolve-cpanel.zip` release artifact.
2. Upload it using cPanel File Manager and extract it outside the public document root.
3. Point your domain/subdomain document root to the extracted `public/` folder.
4. Create a MySQL database and database user in cPanel and grant **ALL PRIVILEGES** to that database.
5. Visit:

```text
https://YOUR-DOMAIN/install.php
```

6. Complete the installer. It will:
   - verify PHP/server requirements;
   - test the MySQL credentials;
   - generate `APP_KEY`;
   - write the private `.env` file;
   - run all migrations;
   - create the first super-administrator;
   - lock the installer after success.
7. Sign in at `/admin/login`.
8. Customer registration is available at `/register`.

## Scheduler (required for autonomous evolution)

In **cPanel → Cron Jobs**, add one job every minute:

```bash
* * * * * cd /home/CPANEL_USER/autoevolve && /usr/local/bin/php artisan schedule:run >> /dev/null 2>&1
```

Your PHP path may differ. cPanel Terminal can show it with `which php`.

## Queue worker

The default release uses the database queue driver. On basic shared hosting you may run queued work synchronously or use a cron-driven worker:

```bash
* * * * * cd /home/CPANEL_USER/autoevolve && /usr/local/bin/php artisan queue:work --stop-when-empty --tries=3 --timeout=120 >> /dev/null 2>&1
```

If your host supports Supervisor, a continuously running Laravel worker is better for higher traffic.

## Stripe billing

Create three recurring Stripe prices and place their Price IDs in `.env`:

```env
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_PRICE_STARTER=price_...
STRIPE_PRICE_GROWTH=price_...
STRIPE_PRICE_SCALE=price_...
```

Configure the Stripe webhook endpoint as:

```text
https://YOUR-DOMAIN/stripe/webhook
```

Subscribe at minimum to:

- `checkout.session.completed`
- `customer.subscription.created`
- `customer.subscription.updated`
- `customer.subscription.deleted`

## AI provider

DeepSeek is the default OpenAI-compatible provider:

```env
AI_BASE_URL=https://api.deepseek.com
AI_API_KEY=YOUR_PRIVATE_KEY
AI_MODEL=deepseek-chat
```

Never expose this key in JavaScript or commit it to GitHub.

## Email / password reset

Configure SMTP in `.env`. Until SMTP is configured, use `MAIL_MAILER=log` for testing.

## Permissions

Laravel must be able to write to:

```text
storage/
bootstrap/cache/
```

Typical cPanel permissions are directories `755` and files `644`, with the PHP process owning or having write access to those two runtime directories. Avoid `777` unless your host explicitly requires it.

## Production optimization

If Terminal access is available:

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

After changing `.env`, run `php artisan optimize:clear` before re-caching configuration.

## Backups

Back up both:

- the MySQL database;
- `storage/` and the private `.env` file.

Do not treat GitHub as a database backup.

## Security checklist

- `APP_DEBUG=false`
- HTTPS enabled
- strong database password
- strong super-admin password
- Stripe webhook signature secret configured
- AI key kept server-side
- automatic cPanel/database backups enabled
- PHP/Laravel dependencies updated regularly
- remove unused FTP/cPanel accounts
- rotate any credentials ever shared in plaintext

## Optional Next.js frontend

If you want the separate Vercel frontend, set its environment variables:

```env
BACKEND_URL=https://YOUR-LARAVEL-DOMAIN
NEXT_PUBLIC_BACKEND_URL=https://YOUR-LARAVEL-DOMAIN
```

The Laravel application remains the source of truth.
