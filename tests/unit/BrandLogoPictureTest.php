<?php
/**
 * Unit tests for slate_logo_picture()/slate_logo_urls() — the dynamic
 * (light/dark) logo swap added for the Kohevo rebrand. Pure: no DB, so this
 * only exercises the markup-building half; slate_logo_urls() itself (which
 * reads Database::setting()) is exercised indirectly by every page that
 * calls it, already covered by the existing integration suite.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/helpers.php';

unit('slate_logo_picture with no dark URL renders a plain <img>, byte-identical to the old single-logo markup', function () {
    $html = slate_logo_picture('https://example.test/logo.png', '', ' alt="Kohevo"');
    assert_eq('<img src="https://example.test/logo.png" alt="Kohevo">', $html);
});

unit('slate_logo_picture with a dark URL renders a <picture> with a prefers-color-scheme source', function () {
    $html = slate_logo_picture('https://example.test/logo.png', 'https://example.test/logo-dark.png', ' alt="Kohevo"');
    assert_true(str_starts_with($html, '<picture>'), 'must wrap in <picture> when a dark variant is set');
    assert_true(str_contains($html, '<source srcset="https://example.test/logo-dark.png" media="(prefers-color-scheme: dark)">'), 'the dark source must use the correct media query');
    assert_true(str_contains($html, '<img src="https://example.test/logo.png" alt="Kohevo">'), 'the light logo must still be the fallback <img>');
});

unit('slate_logo_picture with no light URL renders nothing, even if a dark URL is set', function () {
    assert_eq('', slate_logo_picture('', 'https://example.test/logo-dark.png'));
});

unit('slate_logo_picture escapes both URLs and passes attribute text through as-is', function () {
    $html = slate_logo_picture('https://example.test/a"b.png', '', ' alt="X&Y"');
    assert_true(str_contains($html, 'https://example.test/a&quot;b.png'), 'the light URL must be HTML-escaped');
    assert_true(str_contains($html, 'alt="X&Y"'), 'the caller-provided attribute text is trusted, not re-escaped');
});
