<?php
/**
 * Post-deploy hook for cPanel shared hosting (no SSH needed).
 * Called by GitHub Actions after FTP sync.
 *
 * Set DEPLOY_SECRET in .env and pass it as ?token=... to authenticate.
 * NEVER expose this file without the token check.
 */

// ── Auth ──────────────────────────────────────────────────────
$envFile = __DIR__ . '/../.env';
$secret  = null;

if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with($line, 'DEPLOY_SECRET=')) {
            $secret = trim(substr($line, strlen('DEPLOY_SECRET=')), " \t\"'");
            break;
        }
    }
}

if (! $secret || ! hash_equals($secret, (string) ($_GET['token'] ?? ''))) {
    http_response_code(403);
    exit('Forbidden');
}

// ── Run ───────────────────────────────────────────────────────
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(120);

$appRoot = dirname(__DIR__);
$php     = PHP_BINARY;

$commands = [
    'migrate'          => "$php artisan migrate --force --no-interaction",
    'storage:link'     => "$php artisan storage:link --force",
    'pwa manifest'     => "$php artisan erag:update-manifest",
    'optimize:clear'   => "$php artisan optimize:clear --no-interaction",
    'optimize'         => "$php artisan optimize --no-interaction",
];

$ok   = true;
$log  = [];

foreach ($commands as $label => $cmd) {
    $output = [];
    $code   = 0;
    exec("cd " . escapeshellarg($appRoot) . " && $cmd 2>&1", $output, $code);
    $status = $code === 0 ? '✓' : '✗';
    $log[]  = "$status  [$label]\n" . implode("\n", $output);
    if ($code !== 0) $ok = false;
}

http_response_code($ok ? 200 : 500);
echo implode("\n\n", $log);
echo "\n\n" . ($ok ? "✓ Deploy complete" : "✗ One or more commands failed");
