AUTOEVOLVE SAAS — READ THIS FIRST
=================================

Thank you for installing AutoEvolve.

FAST CPANEL INSTALL
-------------------
1. Upload the AutoEvolve package to your cPanel account.
2. Extract it to a folder such as /home/USERNAME/autoevolve.
3. Point your domain/subdomain document root to /home/USERNAME/autoevolve/public.
4. Create a MySQL database + user and grant the user all privileges to the database.
5. Open https://YOUR-DOMAIN/install.php in your browser.
6. Complete the installer and create the super-administrator.
7. Add the Laravel scheduler cron shown in INSTALL.md.
8. Configure AI, Stripe and SMTP when needed.

IMPORTANT
---------
- Never put the Laravel .env file inside a public web folder.
- Never commit database, Stripe, SMTP or AI secrets to GitHub.
- Keep APP_DEBUG=false in production.
- Enable HTTPS and backups.
- The installer locks itself after a successful installation.

FULL DOCUMENTATION
------------------
Read INSTALL.md in this package.

MAIN URLS
---------
/                    SaaS marketing site
/register             Customer registration
/login                Customer login
/app                  Customer workspace
/admin/login          Super-admin login
/setup                Deployment health checks
/install.php          First-install wizard (locked after install)

AUTOMATION
----------
AutoEvolve only runs scheduled evolution when Laravel's scheduler is invoked by cron.
See INSTALL.md for the exact cPanel cron command.
