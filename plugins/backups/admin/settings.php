<?php
/**
 * Backups — settings, Google Drive connection, history, and the manual
 * "Run backup now" button. Locked to Super Admin — this feature touches the
 * entire shared database (every tenant) and holds OAuth credentials.
 */
require_once dirname(__DIR__, 3) . '/config.php';
require_once dirname(__DIR__) . '/GoogleDriveClient.php';
require_once dirname(__DIR__) . '/BackupRunner.php';

Auth::require();
if (!Auth::isSuperAdmin()) {
    http_response_code(403);
    echo '<h1>403 Forbidden</h1><p>Super Admin only.</p>';
    exit;
}

$pageTitle  = 'Backups';
$currentNav = 'backups-settings';

$flash = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $flash = ['type' => 'error', 'msg' => 'Security check failed.'];
    } else {
        $action = (string)($_POST['_action'] ?? '');

        if ($action === 'save_settings') {
            Database::setSetting('backups.google_client_id', trim((string)($_POST['client_id'] ?? '')));
            $secret = trim((string)($_POST['client_secret'] ?? ''));
            if ($secret !== '') { // blank means "leave the stored secret alone"
                Database::setSetting('backups.google_client_secret', slate_encrypt_secret($secret));
            }
            Database::setSetting('backups.retention_count', (string) max(1, (int)($_POST['retention_count'] ?? 14)));
            Database::setSetting('backups.enabled', !empty($_POST['enabled']) ? '1' : '0');
            AuditLog::record('backups.settings_updated');
            $flash = ['type' => 'success', 'msg' => 'Settings saved.'];

        } elseif ($action === 'connect') {
            if (!GoogleDriveClient::isConfigured()) {
                $flash = ['type' => 'error', 'msg' => 'Add a Client ID and Client Secret first, then save, then connect.'];
            } else {
                $nonce = bin2hex(random_bytes(16));
                $_SESSION['backups_gdrive_nonce'] = $nonce;
                header('Location: ' . GoogleDriveClient::authUrl($nonce));
                exit;
            }

        } elseif ($action === 'disconnect') {
            GoogleDriveClient::disconnect();
            $flash = ['type' => 'success', 'msg' => 'Google Drive disconnected.'];

        } elseif ($action === 'run_now') {
            $res = BackupRunner::runNow();
            $flash = !empty($res['ok'])
                ? ['type' => 'success', 'msg' => 'Backup started (run #' . $res['run_id'] . '). It will continue in the background over the next few cron ticks.']
                : ['type' => 'error', 'msg' => (string)($res['error'] ?? 'Could not start a backup.')];
        }
    }
}

if (!empty($_GET['gdrive_connected'])) $flash = ['type' => 'success', 'msg' => 'Google Drive connected (' . e((string)$_GET['gdrive_connected']) . ').'];
if (!empty($_GET['gdrive_error']))     $flash = ['type' => 'error',   'msg' => (string)$_GET['gdrive_error']];

$g = fn(string $k, $d = '') => (string)(Database::setting('backups.' . $k) ?? $d);

$connected = GoogleDriveClient::isConnected();
$runs = Database::rows(
    "SELECT * FROM backups_runs WHERE tenant_id = ? ORDER BY id DESC LIMIT 20",
    [current_tenant_id()]
);

function backups_format_bytes(?int $bytes): string {
    if (!$bytes) return '—';
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $b = (float)$bytes;
    while ($b >= 1024 && $i < count($units) - 1) { $b /= 1024; $i++; }
    return round($b, 1) . ' ' . $units[$i];
}

require SLATE_ROOT . '/admin/partials/header.php';
?>

<?php slate_breadcrumbs([
    ['label' => 'Dashboard', 'href' => SLATE_URL . '/admin/'],
    ['label' => 'Backups'],
]); ?>

<div class="page-header"><div><h1>Backups</h1></div></div>

<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>" role="status"><?= e($flash['msg']) ?></div><?php endif; ?>

<div class="card">
    <div class="card-header"><h2>Google Drive connection</h2></div>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="_action" value="save_settings">

        <div class="field">
            <label class="field-label" for="client_id">Client ID</label>
            <input type="text" id="client_id" name="client_id" value="<?= e($g('google_client_id')) ?>" autocomplete="off">
        </div>
        <div class="field">
            <label class="field-label" for="client_secret">Client Secret</label>
            <input type="password" id="client_secret" name="client_secret" placeholder="<?= $g('google_client_secret') !== '' ? '••••••••  (leave blank to keep the current one)' : '' ?>" autocomplete="off">
        </div>
        <div class="field">
            <label class="field-label" for="retention_count">Keep the last</label>
            <input type="number" id="retention_count" name="retention_count" min="1" max="365" value="<?= e($g('retention_count', '14')) ?>" style="max-width:120px;"> backups in Drive
        </div>
        <div class="field">
            <label class="switch-label">
                <span class="switch">
                    <input type="checkbox" name="enabled" value="1" <?= $g('enabled') === '1' ? 'checked' : '' ?>>
                    <span class="switch-track"></span>
                </span>
                <span>Run automatically once a day</span>
            </label>
        </div>
        <div class="flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary">Save settings</button>
        </div>
    </form>

    <hr style="border:none;border-top:1px solid var(--border);margin:var(--space-3) 0;">

    <?php if ($connected): ?>
        <p>
            <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;color:#15803D;background:#DCFCE7;">Connected</span>
            <?php $email = $g('google_connected_email'); if ($email !== ''): ?> as <strong><?= e($email) ?></strong><?php endif; ?>
        </p>
        <form method="post" style="display:inline;" onsubmit="return confirm('Disconnect Google Drive? Scheduled backups will stop until you reconnect.')">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="disconnect">
            <button type="submit" class="btn btn-ghost">Disconnect</button>
        </form>
    <?php else: ?>
        <p class="text-sm text-muted">Not connected yet. Add a Client ID/Secret above, save, then connect.</p>
        <form method="post" style="display:inline;">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="connect">
            <button type="submit" class="btn btn-primary">Connect Google Drive</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <h2>Backup history</h2>
        <form method="post" style="margin:0;">
            <?= csrf_field() ?>
            <input type="hidden" name="_action" value="run_now">
            <button type="submit" class="btn btn-sm btn-primary">Run backup now</button>
        </form>
    </div>

    <?php if (!$runs): ?>
        <div class="empty"><div class="empty-title">No backups yet.</div></div>
    <?php else: ?>
        <div class="dlist">
            <?php foreach ($runs as $r):
                $statusColor = [
                    'done'   => 'color:#15803D;background:#DCFCE7;',
                    'failed' => 'color:#B91C1C;background:#FEE2E2;',
                ][$r['status']] ?? 'color:#B45309;background:#FEF3C7;';
                $driveUrl = $r['drive_file_id'] ? 'https://drive.google.com/file/d/' . rawurlencode($r['drive_file_id']) . '/view' : '';
            ?>
                <div class="dlist-row">
                    <span class="dlist-body">
                        <span class="dlist-title">
                            <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:12px;font-weight:600;<?= $statusColor ?>"><?= e(ucfirst($r['status'])) ?></span>
                            #<?= (int)$r['id'] ?>
                        </span>
                        <span class="dlist-sub">
                            <?= e($r['started_at']) ?><?= $r['finished_at'] ? ' → ' . e($r['finished_at']) : '' ?>
                            <?php if ($r['status'] === 'failed' && $r['error']): ?> — <?= e($r['error']) ?><?php endif; ?>
                        </span>
                    </span>
                    <span class="dlist-trail">
                        <span class="dlist-amount"><?= backups_format_bytes($r['size_bytes'] ?? null) ?></span>
                        <?php if ($driveUrl): ?><a href="<?= e($driveUrl) ?>" target="_blank" rel="noopener">Open in Drive</a><?php endif; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require SLATE_ROOT . '/admin/partials/footer.php'; ?>
