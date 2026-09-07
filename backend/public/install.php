<?php

declare(strict_types=1);
session_start();

$base = dirname(__DIR__);
$lock = $base.'/storage/app/autoevolve-installed';
$vendor = $base.'/vendor/autoload.php';

function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function envQuote(?string $value): string {
    $value ??= '';
    return '"'.str_replace(['\\','"',"\n","\r"], ['\\\\','\\"','\\n',''], $value).'"';
}
function checkedRequirement(bool $ok, string $label): string {
    return '<div class="req '.($ok?'ok':'bad').'"><span>'.($ok?'✓':'✕').'</span> '.h($label).'</div>';
}

$requirements = [
    'PHP 8.3+' => version_compare(PHP_VERSION, '8.3.0', '>='),
    'PDO MySQL' => extension_loaded('pdo_mysql'),
    'Mbstring' => extension_loaded('mbstring'),
    'OpenSSL' => extension_loaded('openssl'),
    'JSON' => extension_loaded('json'),
    'Tokenizer' => extension_loaded('tokenizer'),
    'Writable storage' => is_writable($base.'/storage'),
    'Writable project root (.env)' => is_writable($base),
    'Composer dependencies installed' => is_file($vendor),
];

$installed = is_file($lock);
$error = null;
$success = null;

if (!isset($_SESSION['installer_token'])) $_SESSION['installer_token'] = bin2hex(random_bytes(24));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$installed) {
    try {
        if (!hash_equals($_SESSION['installer_token'], (string)($_POST['_token'] ?? ''))) throw new RuntimeException('Installer session expired. Refresh and try again.');
        foreach ($requirements as $label=>$ok) if (!$ok) throw new RuntimeException('Server requirement failed: '.$label);

        $appUrl = rtrim((string)($_POST['app_url'] ?? ''), '/');
        if (!filter_var($appUrl, FILTER_VALIDATE_URL)) throw new RuntimeException('Enter a valid application URL including https://');

        $dbHost = trim((string)($_POST['db_host'] ?? 'localhost'));
        $dbPort = (int)($_POST['db_port'] ?? 3306);
        $dbName = trim((string)($_POST['db_database'] ?? ''));
        $dbUser = trim((string)($_POST['db_username'] ?? ''));
        $dbPass = (string)($_POST['db_password'] ?? '');
        if ($dbName === '' || $dbUser === '') throw new RuntimeException('Database name and username are required.');

        $adminName = trim((string)($_POST['admin_name'] ?? ''));
        $adminEmail = strtolower(trim((string)($_POST['admin_email'] ?? '')));
        $adminPassword = (string)($_POST['admin_password'] ?? '');
        if ($adminName === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid administrator name and email.');
        if (strlen($adminPassword) < 10) throw new RuntimeException('Administrator password must be at least 10 characters.');

        $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 8,
        ]);
        $pdo->query('SELECT 1');

        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $env = [
            'APP_NAME='.envQuote('AutoEvolve'),
            'APP_ENV=production',
            'APP_KEY='.envQuote($appKey),
            'APP_DEBUG=false',
            'APP_URL='.envQuote($appUrl),
            'FRONTEND_URL='.envQuote((string)($_POST['frontend_url'] ?? $appUrl)),
            '',
            'LOG_CHANNEL=stack',
            'LOG_LEVEL=warning',
            '',
            'DB_CONNECTION=mysql',
            'DB_HOST='.envQuote($dbHost),
            'DB_PORT='.$dbPort,
            'DB_DATABASE='.envQuote($dbName),
            'DB_USERNAME='.envQuote($dbUser),
            'DB_PASSWORD='.envQuote($dbPass),
            '',
            'SESSION_DRIVER=database',
            'CACHE_STORE=database',
            'QUEUE_CONNECTION=database',
            '',
            'AI_BASE_URL='.envQuote((string)($_POST['ai_base_url'] ?? 'https://api.deepseek.com')),
            'AI_API_KEY='.envQuote((string)($_POST['ai_api_key'] ?? '')),
            'AI_MODEL='.envQuote((string)($_POST['ai_model'] ?? 'deepseek-chat')),
            '',
            'STRIPE_KEY='.envQuote((string)($_POST['stripe_key'] ?? '')),
            'STRIPE_SECRET='.envQuote((string)($_POST['stripe_secret'] ?? '')),
            'STRIPE_WEBHOOK_SECRET='.envQuote((string)($_POST['stripe_webhook_secret'] ?? '')),
            'STRIPE_PRICE_STARTER='.envQuote((string)($_POST['stripe_price_starter'] ?? '')),
            'STRIPE_PRICE_GROWTH='.envQuote((string)($_POST['stripe_price_growth'] ?? '')),
            'STRIPE_PRICE_SCALE='.envQuote((string)($_POST['stripe_price_scale'] ?? '')),
            '',
            'MAIL_MAILER='.envQuote((string)($_POST['mail_mailer'] ?? 'log')),
            'MAIL_HOST='.envQuote((string)($_POST['mail_host'] ?? '127.0.0.1')),
            'MAIL_PORT='.((int)($_POST['mail_port'] ?? 2525)),
            'MAIL_USERNAME='.envQuote((string)($_POST['mail_username'] ?? '')),
            'MAIL_PASSWORD='.envQuote((string)($_POST['mail_password'] ?? '')),
            'MAIL_ENCRYPTION='.envQuote((string)($_POST['mail_encryption'] ?? 'tls')),
            'MAIL_FROM_ADDRESS='.envQuote((string)($_POST['mail_from_address'] ?? $adminEmail)),
            'MAIL_FROM_NAME='.envQuote('AutoEvolve'),
        ];

        if (file_put_contents($base.'/.env', implode(PHP_EOL, $env).PHP_EOL, LOCK_EX) === false) throw new RuntimeException('Could not write .env. Check project folder permissions.');

        require $vendor;
        $app = require $base.'/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        $exit = Illuminate\Support\Facades\Artisan::call('migrate', ['--force'=>true]);
        if ($exit !== 0) throw new RuntimeException('Database migration failed: '.Illuminate\Support\Facades\Artisan::output());

        App\Models\User::updateOrCreate(
            ['email'=>$adminEmail],
            ['name'=>$adminName,'password'=>$adminPassword,'is_admin'=>true]
        );

        if (!is_dir(dirname($lock))) mkdir(dirname($lock), 0755, true);
        file_put_contents($lock, json_encode(['installed_at'=>date(DATE_ATOM),'version'=>'1.0.0'], JSON_PRETTY_PRINT), LOCK_EX);
        $installed = true;
        $success = 'Installation completed. AutoEvolve is ready.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Install AutoEvolve</title><style>body{margin:0;background:#080b12;color:#f5f7fb;font-family:Inter,system-ui,sans-serif}.wrap{width:min(900px,calc(100% - 30px));margin:50px auto}.brand{font-size:22px;font-weight:900}.brand b{display:inline-grid;place-items:center;width:36px;height:36px;border-radius:11px;background:linear-gradient(135deg,#7c5cff,#31c7ff);margin-right:8px}.card{background:#101622;border:1px solid #27334a;border-radius:18px;padding:26px;margin:20px 0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{display:grid;gap:6px}.field.full{grid-column:1/-1}label{font-size:12px;color:#aab5c6;font-weight:700}input{width:100%;box-sizing:border-box;background:#0b111c;color:#fff;border:1px solid #2d3a51;border-radius:9px;padding:11px}.req{padding:8px 0;color:#aab5c6}.req.ok span{color:#35d39a}.req.bad span{color:#ff6b85}.btn{border:0;border-radius:10px;padding:12px 17px;background:linear-gradient(135deg,#7c5cff,#5e67f4);color:#fff;font-weight:800;cursor:pointer}.error{padding:13px;background:#34151e;border:1px solid #733147;border-radius:10px;color:#ffc1cc}.success{padding:13px;background:#0e3027;border:1px solid #21644e;border-radius:10px;color:#a8efd1}details{margin-top:18px}summary{cursor:pointer;color:#bcb3ff}@media(max-width:700px){.grid{grid-template-columns:1fr}.field.full{grid-column:auto}.wrap{margin:25px auto}}</style></head><body><main class="wrap"><div class="brand"><b>↗</b>AutoEvolve Installer</div><?php if($installed): ?><div class="card"><div class="success"><?= h($success ?: 'AutoEvolve is already installed. The installer is locked.') ?></div><h1>Installation complete</h1><p>Sign in as the administrator, configure billing/AI if needed, then add the scheduler cron from your hosting panel.</p><p><a class="btn" href="/admin/login">Super admin login</a> <a style="color:#bcb3ff;margin-left:14px" href="/login">Customer login</a></p></div><?php else: ?><div class="card"><h1>Server check</h1><?php foreach($requirements as $label=>$ok) echo checkedRequirement($ok,$label); ?></div><?php if($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?><form method="post"><input type="hidden" name="_token" value="<?= h($_SESSION['installer_token']) ?>"><div class="card"><h2>Application</h2><div class="grid"><div class="field full"><label>Application URL</label><input name="app_url" value="<?= h($_POST['app_url'] ?? ((isset($_SERVER['HTTPS'])?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost'))) ?>" placeholder="https://example.com" required></div><div class="field full"><label>Optional external frontend URL</label><input name="frontend_url" value="<?= h($_POST['frontend_url'] ?? '') ?>" placeholder="Leave blank to use Laravel frontend"></div></div></div><div class="card"><h2>MySQL database</h2><div class="grid"><div class="field"><label>Host</label><input name="db_host" value="<?= h($_POST['db_host'] ?? 'localhost') ?>" required></div><div class="field"><label>Port</label><input name="db_port" value="<?= h($_POST['db_port'] ?? '3306') ?>" required></div><div class="field"><label>Database</label><input name="db_database" value="<?= h($_POST['db_database'] ?? '') ?>" required></div><div class="field"><label>Username</label><input name="db_username" value="<?= h($_POST['db_username'] ?? '') ?>" required></div><div class="field full"><label>Password</label><input type="password" name="db_password" required></div></div></div><div class="card"><h2>Super administrator</h2><div class="grid"><div class="field"><label>Name</label><input name="admin_name" value="<?= h($_POST['admin_name'] ?? '') ?>" required></div><div class="field"><label>Email</label><input type="email" name="admin_email" value="<?= h($_POST['admin_email'] ?? '') ?>" required></div><div class="field full"><label>Password (10+ characters)</label><input type="password" name="admin_password" minlength="10" required></div></div></div><div class="card"><h2>AI provider</h2><p style="color:#94a3b8">Optional during installation. You can add these values later in .env.</p><div class="grid"><div class="field"><label>Base URL</label><input name="ai_base_url" value="<?= h($_POST['ai_base_url'] ?? 'https://api.deepseek.com') ?>"></div><div class="field"><label>Model</label><input name="ai_model" value="<?= h($_POST['ai_model'] ?? 'deepseek-chat') ?>"></div><div class="field full"><label>API key</label><input type="password" name="ai_api_key"></div></div><details><summary>Stripe billing (optional)</summary><div class="grid" style="margin-top:15px"><div class="field"><label>Publishable key</label><input name="stripe_key"></div><div class="field"><label>Secret key</label><input type="password" name="stripe_secret"></div><div class="field full"><label>Webhook secret</label><input type="password" name="stripe_webhook_secret"></div><div class="field"><label>Starter Price ID</label><input name="stripe_price_starter"></div><div class="field"><label>Growth Price ID</label><input name="stripe_price_growth"></div><div class="field"><label>Scale Price ID</label><input name="stripe_price_scale"></div></div></details><details><summary>Email / SMTP (optional)</summary><div class="grid" style="margin-top:15px"><div class="field"><label>Mailer</label><input name="mail_mailer" value="smtp"></div><div class="field"><label>Host</label><input name="mail_host"></div><div class="field"><label>Port</label><input name="mail_port" value="587"></div><div class="field"><label>Encryption</label><input name="mail_encryption" value="tls"></div><div class="field"><label>Username</label><input name="mail_username"></div><div class="field"><label>Password</label><input type="password" name="mail_password"></div><div class="field full"><label>From address</label><input type="email" name="mail_from_address"></div></div></details></div><button class="btn" type="submit">Install AutoEvolve</button></form><?php endif; ?></main></body></html>