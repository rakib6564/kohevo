<?php
/**
 * Slate — cron entry point.
 *
 * Hit by an external scheduler (cPanel cron, GitHub Actions, etc.)
 * to fire periodic actions that plugins listen on.
 *
 * Auth via the CRON_SECRET defined in .env. Set the `X-Cron-Key:
 * <CRON_SECRET>` header (recommended), or pass `?key=<CRON_SECRET>` if
 * your scheduler can't set a custom header. Without a valid key the
 * endpoint returns 403 — no information about whether cron is
 * configured at all.
 *
 * Prefer the header. A secret in the URL ends up in the web server's
 * access log, in curl's own argv (visible to other local users via `ps`
 * for the life of the request), and in shell history if the cron line
 * is ever run by hand. The header form avoids all three.
 *
 * Actions fired (priority-ordered by listener):
 *   - `frequent_cron`  every time this is hit (recommended: every 5m)
 *   - `daily_cron`     once per UTC day (cheap idempotency via the
 *                      `cron_last_daily` setting)
 *
 * Recommended cron line (every 5 minutes):
 *   <every 5 min>  curl -fsS -H 'X-Cron-Key: YOUR_CRON_SECRET' 'https://yoursite/cron.php' > /dev/null
 *
 * Output: a tiny JSON blob with what fired + how long it took.
 * Errors inside individual listeners are caught by Hook::doAction
 * and logged via slate_log — they never crash this endpoint.
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ─────────────────────────────────────────────────────
$secret   = defined('CRON_SECRET') ? (string) CRON_SECRET : '';
$provided = (string) ($_GET['key'] ?? ($_SERVER['HTTP_X_CRON_KEY'] ?? ''));
if ($secret === '' || $provided === '' || !hash_equals($secret, $provided)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'forbidden']);
    exit;
}

// ── CLI / web duals ──────────────────────────────────────────
$start = microtime(true);
$fired = ['frequent_cron'];

Hook::doAction('frequent_cron');

// Daily cron — fire once per UTC day, gate via the settings table.
$todayUtc = gmdate('Y-m-d');
$lastDaily = (string) Database::setting('cron_last_daily');
if ($lastDaily !== $todayUtc) {
    Hook::doAction('daily_cron');
    Database::setSetting('cron_last_daily', $todayUtc);
    $fired[] = 'daily_cron';
}

$elapsed = (int) ((microtime(true) - $start) * 1000);
echo json_encode([
    'ok'         => true,
    'fired'      => $fired,
    'duration_ms'=> $elapsed,
    'now'        => gmdate('c'),
]);
