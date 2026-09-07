<?php
/**
 * Slate — activate an on-disk plugin from the CLI (the counterpart to
 * bin/deactivate-plugin.php, and the same effect as the admin Plugins page).
 *
 * The admin page lists PluginLoader::listAll(), which reads the `plugins`
 * TABLE — not the plugins/ directory. A plugin shipped in the repo therefore
 * stays invisible in the UI until something registers a row for it, and the
 * only path that does is the ZIP upload. That makes a fresh checkout awkward
 * to bring up: every bundled plugin has to be packaged and re-uploaded to
 * reach a state the repo already describes.
 *
 * This registers the row straight from plugins/<slug>/plugin.json and then
 * hands off to PluginLoader::activate(), so manifest validation, install.sql
 * and permission registration all run exactly as they do from the UI.
 *
 * Run:  php bin/activate-plugin.php <slug> [<slug> ...]
 *       php bin/activate-plugin.php --all      (every plugin in plugins/)
 * e.g.: php bin/activate-plugin.php booking membership coaching
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from the CLI: php bin/activate-plugin.php <slug>\n");
    exit(1);
}

require __DIR__ . '/../config.php';

$args = array_slice($argv, 1);
if (!$args) {
    fwrite(STDERR, "Usage: php bin/activate-plugin.php <slug> [<slug> ...]\n"
                 . "       php bin/activate-plugin.php --all\n");
    exit(1);
}

if (in_array('--all', $args, true)) {
    $args = [];
    foreach (glob(SLATE_ROOT . '/plugins/*', GLOB_ONLYDIR) ?: [] as $dir) {
        if (is_file($dir . '/plugin.json')) $args[] = basename($dir);
    }
    sort($args);
    if (!$args) {
        fwrite(STDERR, "No plugins with a plugin.json found under plugins/.\n");
        exit(1);
    }
}

$failed = 0;

foreach ($args as $slug) {
    $dir      = SLATE_ROOT . '/plugins/' . $slug;
    $manifest = $dir . '/plugin.json';

    if (!is_dir($dir) || !is_file($manifest)) {
        fwrite(STDERR, "  ✗ $slug — no plugins/$slug/plugin.json\n");
        $failed++;
        continue;
    }

    $json = json_decode((string) file_get_contents($manifest), true);
    if (!is_array($json)) {
        fwrite(STDERR, "  ✗ $slug — plugin.json is not valid JSON\n");
        $failed++;
        continue;
    }

    // Seed the row at 0.0.0 rather than the manifest's real version.
    // PluginLoader::loadOne() only refreshes a plugin's manifest from disk when
    // the FILE version is newer than the row's, so writing the real version here
    // would pin whatever we happened to store — including, for a hand-made row,
    // a manifest with no "capabilities" key, which silently disables every
    // capability-gated hook. A version below any real one lets the file win.
    if (!Database::row("SELECT id FROM plugins WHERE slug = ?", [$slug])) {
        Database::query(
            "INSERT INTO plugins (slug, name, version, status, manifest_json)
             VALUES (?, ?, '0.0.0', 'installed', ?)",
            [$slug, (string) ($json['name'] ?? $slug),
             json_encode(['slug' => $slug, 'name' => $json['name'] ?? $slug, 'version' => '0.0.0'])]
        );
        echo "  + $slug — registered\n";
    }

    $res = PluginLoader::activate($slug);
    if (!empty($res['ok'])) {
        $note = !empty($res['note']) ? " ({$res['note']})" : '';
        echo "  ✓ $slug$note\n";
    } else {
        fwrite(STDERR, "  ✗ $slug — " . ($res['error'] ?? 'unknown error') . "\n");
        $failed++;
    }
}

if ($failed > 0) {
    fwrite(STDERR, "\n$failed plugin(s) failed.\n");
    exit(1);
}

echo "\nDone. Check Admin → Plugins.\n";
