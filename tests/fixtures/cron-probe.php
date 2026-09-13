<?php
/**
 * Hit the real cron.php in a CHILD process with a REJECTED key (wrong or
 * missing), and report what it returned.
 *
 * Deliberately does NOT support a "valid key" mode. A successful auth
 * reaches Hook::doAction('frequent_cron'/'daily_cron'), which real plugins
 * (Booking::sendReminders — real customer notifications; BackupRunner::
 * runFrequentCron/runDailyCron — real backup operations; GoogleCalendarSync
 * — real network calls) listen on. This test suite has no business
 * triggering any of that, so only the two rejection paths — which return
 * 403 and exit before Hook::doAction is ever reached — are exercised here.
 * Proving the header-delivery method actually resolves the secret is done
 * as a static/logic check instead (see CronDocumentationTest.php).
 *
 * A child, not the current process, because cron.php calls exit() after
 * writing its response — requiring it inline would end the test runner.
 *
 * Usage: php cron-probe.php wrong|none
 * Prints:
 *     STATUS <code>
 *     <json body>
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$mode = $argv[1] ?? '';
if (!in_array($mode, ['wrong', 'none'], true)) {
    fwrite(STDERR, "usage: cron-probe.php wrong|none\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
require $root . '/config.php';

$_GET = [];
unset($_SERVER['HTTP_X_CRON_KEY']);
if ($mode === 'wrong') $_GET['key'] = 'not-the-real-secret';
$_SERVER['REQUEST_METHOD'] = 'GET';

ob_start();
$shutdownDone = false;
register_shutdown_function(static function () use (&$shutdownDone): void {
    if ($shutdownDone) return;
    $shutdownDone = true;
    $body = ob_get_length() !== false ? (string) ob_get_clean() : '';
    $code = http_response_code();
    fwrite(STDOUT, 'STATUS ' . ($code === false ? 200 : (int) $code) . "\n");
    fwrite(STDOUT, $body);
});

require $root . '/cron.php';
