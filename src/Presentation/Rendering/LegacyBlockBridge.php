<?php
/**
 * Slate — LegacyBlockBridge: registers a minimal, representative set of native
 * Slate\Presentation\Block implementations for admin/editor.php's Phase 2
 * migration.
 *
 * The archived content-builder plugin (archive/plugins/content-builder/) shipped
 * ~25 block types as array-defined blocks with a `tpl` PHP template, rendered by
 * its own Renderer::renderBlock(). That plugin no longer exists in plugins/, and
 * neither the old array-based BlockRegistry nor the new Slate\Presentation\
 * BlockRegistry has any live block registered anywhere in the app. Porting all
 * ~25 is out of scope for this migration (tracked as deferred, see the plan);
 * this bridge ports exactly 5 representative types — heading, paragraph, image,
 * button, hero — enough to prove the full validate/save/preview/publish/restore
 * pipeline with real, renderable content.
 *
 * Each render closure is a faithful, direct port of the corresponding archived
 * template (archive/plugins/content-builder/lib/blocks/{type}.php) — all five
 * were already pure functions of props -> HTML with no DB/ContentBuilderAPI
 * dependency — so output markup matches what the existing canvas CSS
 * (plugins/content-builder/assets/css/public.css, still inlined by
 * admin/editor.php) already styles. CallbackBlock is the existing adapter this
 * repo already ships for exactly this bridging purpose (see its own docblock).
 *
 * Pure registration — no DB, no side effects beyond populating the registry
 * instance handed in.
 */

declare(strict_types=1);

namespace Slate\Presentation\Rendering;

use Slate\Presentation\FieldSchema;
use Slate\Presentation\RenderContext;

final class LegacyBlockBridge
{
    public static function register(InMemoryBlockRegistry $registry): void
    {
        $registry->register(new CallbackBlock(
            'heading',
            FieldSchema::of(
                [
                    ['key' => 'text', 'type' => 'text', 'label' => 'Text'],
                    ['key' => 'level', 'type' => 'select', 'label' => 'Level', 'options' => [
                        ['v' => '1', 'l' => 'H1'], ['v' => '2', 'l' => 'H2'],
                        ['v' => '3', 'l' => 'H3'], ['v' => '4', 'l' => 'H4'],
                    ]],
                    ['key' => 'align', 'type' => 'select', 'label' => 'Alignment', 'options' => [
                        ['v' => 'left', 'l' => 'Left'], ['v' => 'center', 'l' => 'Center'], ['v' => 'right', 'l' => 'Right'],
                    ]],
                ],
                ['text' => 'Heading', 'level' => '2', 'align' => 'left'],
            ),
            static function (array $props, RenderContext $ctx): string {
                $lvl = max(1, min(6, (int) ($props['level'] ?? 2)));
                $align = self::choice($props['align'] ?? 'left', ['left', 'center', 'right'], 'left');
                $style = $align !== 'left' ? ' style="text-align:' . $align . '"' : '';
                return sprintf('<h%1$d class="cb-heading"%3$s>%2$s</h%1$d>', $lvl, \e((string) ($props['text'] ?? '')), $style);
            },
        ));

        $registry->register(new CallbackBlock(
            'paragraph',
            FieldSchema::of(
                [['key' => 'text', 'type' => 'textarea', 'label' => 'Text']],
                ['text' => 'Write something…'],
            ),
            static function (array $props, RenderContext $ctx): string {
                return '<p class="cb-paragraph">' . nl2br(\e((string) ($props['text'] ?? ''))) . '</p>';
            },
        ));

        $registry->register(new CallbackBlock(
            'image',
            FieldSchema::of(
                [
                    ['key' => 'media', 'type' => 'media', 'label' => 'Image'],
                    ['key' => 'width', 'type' => 'select', 'label' => 'Width', 'options' => [
                        ['v' => 'full', 'l' => 'Full'], ['v' => 'wide', 'l' => 'Wide'], ['v' => 'normal', 'l' => 'Normal'],
                    ]],
                ],
                ['width' => 'full'],
            ),
            static function (array $props, RenderContext $ctx): string {
                $media = is_array($props['media'] ?? null) ? $props['media'] : null;
                $url = self::resolveMediaUrl($media);
                if ($url === '') {
                    return '';
                }
                $wv = (string) ($props['width'] ?? 'full');
                $w = in_array($wv, ['full', 'wide', 'normal'], true) ? $wv : 'full';
                $alt = (string) ($media['alt'] ?? '');
                $style = '';
                $focal = $media['focal'] ?? null;
                if (is_array($focal) && count($focal) === 2) {
                    $style = sprintf(' style="object-position:%.2F%% %.2F%%"', (float) $focal[0] * 100, (float) $focal[1] * 100);
                }
                return sprintf(
                    '<figure class="cb-image cb-image-%s"><img src="%s" alt="%s" loading="lazy"%s></figure>',
                    \e($w),
                    \e(\slate_safe_url($url)),
                    \e($alt),
                    $style,
                );
            },
        ));

        $registry->register(new CallbackBlock(
            'button',
            FieldSchema::of(
                [
                    ['key' => 'text', 'type' => 'text', 'label' => 'Label'],
                    ['key' => 'href', 'type' => 'url', 'label' => 'Link URL'],
                    ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'options' => [
                        ['v' => 'primary', 'l' => 'Primary'], ['v' => 'secondary', 'l' => 'Secondary'],
                    ]],
                ],
                ['text' => 'Click me', 'href' => '#', 'style' => 'primary'],
            ),
            static function (array $props, RenderContext $ctx): string {
                $st = (($props['style'] ?? 'primary') === 'secondary') ? 'btn-secondary' : 'btn-primary';
                return sprintf(
                    '<p class="cb-button"><a class="btn %s" href="%s">%s</a></p>',
                    $st,
                    \e(\slate_safe_url((string) ($props['href'] ?? '#'))),
                    \e((string) ($props['text'] ?? 'Button')),
                );
            },
        ));

        $registry->register(new CallbackBlock(
            'hero',
            FieldSchema::of(
                [
                    ['key' => 'pad', 'type' => 'select', 'label' => 'Section spacing', 'options' => [
                        ['v' => 'compact', 'l' => 'Compact'], ['v' => 'normal', 'l' => 'Normal'], ['v' => 'spacious', 'l' => 'Spacious'],
                    ]],
                    ['key' => 'layout', 'type' => 'select', 'label' => 'Layout', 'options' => [
                        ['v' => 'split', 'l' => 'Split (image + text)'], ['v' => 'banner', 'l' => 'Banner (text over image)'],
                    ]],
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'subheading', 'type' => 'textarea', 'label' => 'Subheading'],
                    ['key' => 'btnText', 'type' => 'text', 'label' => 'Button label'],
                    ['key' => 'btnHref', 'type' => 'url', 'label' => 'Button link'],
                    ['key' => 'btn2Text', 'type' => 'text', 'label' => 'Second button label'],
                    ['key' => 'btn2Href', 'type' => 'url', 'label' => 'Second button link'],
                    ['key' => 'media', 'type' => 'media', 'label' => 'Image'],
                    ['key' => 'mediaSide', 'type' => 'select', 'label' => 'Image side', 'options' => [
                        ['v' => 'left', 'l' => 'Left'], ['v' => 'right', 'l' => 'Right'],
                    ]],
                    ['key' => 'overlay', 'type' => 'select', 'label' => 'Image darkness', 'options' => [
                        ['v' => 'light', 'l' => 'Light'], ['v' => 'medium', 'l' => 'Medium'], ['v' => 'dark', 'l' => 'Dark'],
                    ]],
                    ['key' => 'height', 'type' => 'select', 'label' => 'Height', 'options' => [
                        ['v' => 'normal', 'l' => 'Normal'], ['v' => 'tall', 'l' => 'Tall'], ['v' => 'short', 'l' => 'Short'],
                    ]],
                ],
                [
                    'pad' => 'normal', 'layout' => 'split', 'eyebrow' => '', 'heading' => 'Your big headline',
                    'subheading' => 'A short supporting sentence that sells the idea.',
                    'btnText' => 'Get started', 'btnHref' => '#', 'btn2Text' => '', 'btn2Href' => '',
                    'mediaSide' => 'left', 'overlay' => 'medium', 'height' => 'normal',
                ],
            ),
            static function (array $props, RenderContext $ctx): string {
                return self::renderHero($props);
            },
        ));

        self::registerArchivedFallbacks($registry);
    }

    /**
     * Keep archived content portable even when a specialized renderer is not
     * available in the active application. These contracts deliberately expose
     * only safe text fields; richer blocks can be upgraded without changing the
     * stored type or document shape later.
     */
    private static function registerArchivedFallbacks(InMemoryBlockRegistry $registry): void
    {
        $registry->register(new CallbackBlock('cta', FieldSchema::of([
            ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
            ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
            ['key' => 'text', 'type' => 'textarea', 'label' => 'Text'],
            ['key' => 'btnText', 'type' => 'text', 'label' => 'Button label'],
            ['key' => 'btnHref', 'type' => 'url', 'label' => 'Button link'],
            ['key' => 'pad', 'type' => 'select', 'label' => 'Spacing', 'options' => [['v' => 'compact', 'l' => 'Compact'], ['v' => 'normal', 'l' => 'Normal'], ['v' => 'spacious', 'l' => 'Spacious']]],
        ], ['eyebrow' => 'A better way forward', 'heading' => 'Ready to get started?', 'text' => 'Give your visitors a clear next step.', 'btnText' => 'Get started', 'btnHref' => '#', 'pad' => 'normal']),
            static fn (array $p, RenderContext $c): string => '<section class="cb-pad-' . self::choice($p['pad'] ?? 'normal', ['compact', 'normal', 'spacious'], 'normal') . ' cb-cta"><div class="cb-cta-inner">' . (($p['eyebrow'] ?? '') !== '' ? '<span class="cb-eyebrow">' . \e((string)$p['eyebrow']) . '</span>' : '') . '<h2 class="cb-cta-title">' . \e((string)($p['heading'] ?? 'Ready to get started?')) . '</h2><p class="cb-cta-text">' . \e((string)($p['text'] ?? '')) . '</p><a class="cb-btn cb-btn-primary" href="' . \e(\slate_safe_url((string)($p['btnHref'] ?? '#'))) . '">' . \e((string)($p['btnText'] ?? 'Get started')) . '</a></div></section>'
        ));

        $registry->register(new CallbackBlock('divider', FieldSchema::of([
            ['key' => 'style', 'type' => 'select', 'label' => 'Style', 'options' => [['v' => 'solid', 'l' => 'Solid'], ['v' => 'dashed', 'l' => 'Dashed']]],
            ['key' => 'width', 'type' => 'number', 'label' => 'Width (px)'],
        ], ['style' => 'solid', 'width' => 100]), static fn (array $p, RenderContext $c): string => '<div style="padding:24px 0"><hr style="width:' . max(10, min(100, (int)($p['width'] ?? 100))) . '%;border:0;border-top:2px ' . self::choice($p['style'] ?? 'solid', ['solid', 'dashed'], 'solid') . ' currentColor;opacity:.18;margin:auto"></div>'));
        $registry->register(new CallbackBlock('spacer', FieldSchema::of([['key' => 'height', 'type' => 'number', 'label' => 'Height (px)']], ['height' => 48]), static fn (array $p, RenderContext $c): string => '<div class="cb-block cb-spacer" style="height:' . max(8, min(400, (int)($p['height'] ?? 48))) . 'px" aria-hidden="true"></div>'));
        $registry->register(new CallbackBlock('html', FieldSchema::of([['key' => 'content', 'type' => 'textarea', 'label' => 'Text content']], ['content' => 'Add custom content here']), static fn (array $p, RenderContext $c): string => '<div class="cb-section-inner"><p>' . nl2br(\e((string)($p['content'] ?? ''))) . '</p></div>'));
        $registry->register(new CallbackBlock('testimonial', FieldSchema::of([
            ['key' => 'quote', 'type' => 'textarea', 'label' => 'Quote'], ['key' => 'author', 'type' => 'text', 'label' => 'Author'], ['key' => 'role', 'type' => 'text', 'label' => 'Role'],
        ], ['quote' => 'A thoughtful experience from start to finish.', 'author' => 'Happy customer', 'role' => 'Client']), static fn (array $p, RenderContext $c): string => '<section class="cb-testimonial"><figure class="cb-testimonial-inner"><blockquote class="cb-testimonial-quote">&ldquo;' . \e((string)($p['quote'] ?? '')) . '&rdquo;</blockquote><figcaption class="cb-testimonial-cite"><span><b class="cb-testimonial-author">' . \e((string)($p['author'] ?? '')) . '</b><span class="cb-testimonial-role">' . \e((string)($p['role'] ?? '')) . '</span></span></figcaption></figure></section>'));
        $registry->register(new CallbackBlock('columns', FieldSchema::of([
            ['key' => 'sectionName', 'type' => 'text', 'label' => 'Section name'], ['key' => 'columns', 'type' => 'number', 'label' => 'Columns'], ['key' => 'gap', 'type' => 'number', 'label' => 'Gap (px)'],
            // Each column's nested block list, addressed via a DocumentOperations
            // path like ['s1', 0, 'props', 'cols', 0, 'blocks']. Declared as
            // 'blocks' so DocumentValidator accepts the list rather than rejecting
            // it as an undeclared property (it previously wasn't declared here at
            // all, so saving any columns section failed outright). Note
            // capabilities.nested stays false (see
            // ContentServiceFactory::validatorRegistry()), so the nested blocks
            // themselves are not yet structurally validated against their own
            // schemas — only that 'cols' itself is a list.
            ['key' => 'cols', 'type' => 'blocks', 'label' => 'Columns content'],
        ], ['sectionName' => 'Columns section', 'columns' => 2, 'gap' => 24, 'cols' => [['blocks' => []], ['blocks' => []]]]), static function (array $p, RenderContext $c): string {
            $n = max(1, min(4, (int)($p['columns'] ?? count((array)($p['cols'] ?? [])) ?: 2)));
            $cols = is_array($p['cols'] ?? null) ? $p['cols'] : [];
            $items = '';
            for ($i = 0; $i < $n; $i++) {
                $blocks = is_array($cols[$i]['blocks'] ?? null) ? $cols[$i]['blocks'] : [];
                $inner = self::renderNestedBlocks($blocks);
                $items .= '<div class="cb-col" data-col-index="' . $i . '">' . ($inner !== '' ? $inner : '<div class="cb-card"><h3 class="cb-card-title">Column ' . ($i + 1) . '</h3><p class="cb-card-text">Drop widgets here</p></div>') . '</div>';
            }
            return '<div class="cb-columns cb-cols-' . $n . '" style="gap:' . max(0, min(80, (int)($p['gap'] ?? 24))) . 'px">' . $items . '</div>';
        }));
        $registry->register(new CallbackBlock('container', FieldSchema::of([
            ['key' => 'sectionName', 'type' => 'text', 'label' => 'Section name'], ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'], ['key' => 'content', 'type' => 'textarea', 'label' => 'Content'],
            ['key' => 'contentWidth', 'type' => 'select', 'label' => 'Content width', 'options' => [['v' => 'boxed', 'l' => 'Boxed'], ['v' => 'full', 'l' => 'Full width']]],
            ['key' => 'direction', 'type' => 'select', 'label' => 'Direction', 'options' => [['v' => 'column', 'l' => 'Column'], ['v' => 'row', 'l' => 'Row']]],
            ['key' => 'gap', 'type' => 'number', 'label' => 'Gap (px)'],
            // Same reasoning as 'cols' above: this container's nested block list,
            // previously in the defaults but never declared as a field, so any
            // container with actual children failed validation the same way.
            ['key' => 'children', 'type' => 'blocks', 'label' => 'Container content'],
        ], ['sectionName' => 'Container', 'heading' => 'Container', 'content' => 'Add content inside this container', 'children' => [], 'contentWidth' => 'boxed', 'direction' => 'column', 'gap' => 24]), static fn (array $p, RenderContext $c): string => '<div class="cb-section-inner cb-container" style="width:' . (($p['contentWidth'] ?? 'boxed') === 'full' ? '100%' : 'min(100%,1180px)') . ';display:flex;flex-direction:' . self::choice($p['direction'] ?? 'column', ['column', 'row'], 'column') . ';gap:' . max(0, min(120, (int)($p['gap'] ?? 24))) . 'px;padding:24px;border:1px dashed #94a3b8;border-radius:10px"><div><h3>' . \e((string)($p['heading'] ?? 'Container')) . '</h3><p>' . nl2br(\e((string)($p['content'] ?? ''))) . '</p></div>' . self::renderNestedBlocks(is_array($p['children'] ?? null) ? $p['children'] : []) . '</div>'));
        $registry->register(new CallbackBlock('icon-grid', self::cardGridSchema(), static fn (array $p, RenderContext $c): string => self::renderCardGrid($p, 'cb-icon-grid')));
        $registry->register(new CallbackBlock('image-grid', self::cardGridSchema(), static fn (array $p, RenderContext $c): string => self::renderCardGrid($p, 'cb-image-grid')));

        self::registerRxGallery($registry);

        foreach (['post-list', 'react', 'rx-hero', 'rx-marquee', 'rx-menu', 'rx-reviews', 'rx-story', 'rx-visit'] as $type) {
            $registry->register(new CallbackBlock($type, FieldSchema::of([
                ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'], ['key' => 'content', 'type' => 'textarea', 'label' => 'Content'],
            ], ['heading' => ucfirst(str_replace('-', ' ', $type)), 'content' => 'Add content for this section']), static function (array $p, RenderContext $c) use ($type): string {
                return '<section class="cb-section cb-legacy-' . \e($type) . '"><div class="cb-section-inner"><span class="cb-eyebrow">' . \e(ucfirst(str_replace('-', ' ', $type))) . '</span><h2 class="cb-section-title">' . \e((string)($p['heading'] ?? '')) . '</h2><p>' . nl2br(\e((string)($p['content'] ?? ''))) . '</p></div></section>';
            }));
        }
    }

    private static function choice(mixed $value, array $allowed, string $fallback): string
    {
        return in_array((string)$value, $allowed, true) ? (string)$value : $fallback;
    }

    /**
     * rx-gallery — a real image gallery (upload via the Media Library picker,
     * a repeater of {media, label, size} tiles) with a dependency-free,
     * CSS-only lightbox. Faithfully ported from the archived template
     * (archive/plugins/content-builder/lib/blocks/rx-gallery.php) — same
     * markup/classes (.rx-gallery-grid/.rx-tile/.rx-tone-*), so it renders
     * through content-blocks.css's existing rx-* styling — plus the lightbox,
     * which the archived version never had.
     *
     * Previously this type was just a generic heading+content placeholder (see
     * the loop below) — a real photo gallery with no way to add photos.
     */
    private static function registerRxGallery(InMemoryBlockRegistry $registry): void
    {
        $registry->register(new CallbackBlock(
            'rx-gallery',
            FieldSchema::of(
                [
                    ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'],
                    ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'],
                    ['key' => 'tone', 'type' => 'select', 'label' => 'Tone', 'options' => [
                        ['v' => 'light', 'l' => 'Light'], ['v' => 'dark', 'l' => 'Dark'], ['v' => 'surface', 'l' => 'Surface'],
                    ]],
                    ['key' => 'images', 'type' => 'repeater', 'label' => 'Images', 'itemLabel' => 'Image', 'maxItems' => 24, 'item' => [
                        ['key' => 'media', 'type' => 'media', 'label' => 'Image', 'required' => true],
                        ['key' => 'label', 'type' => 'text', 'label' => 'Caption (optional)', 'maxLength' => 120],
                        ['key' => 'size', 'type' => 'select', 'label' => 'Tile size', 'options' => [
                            ['v' => 'normal', 'l' => 'Normal'], ['v' => 'wide', 'l' => 'Wide'], ['v' => 'tall', 'l' => 'Tall'],
                        ]],
                    ]],
                ],
                ['eyebrow' => '', 'heading' => 'Gallery', 'tone' => 'light', 'images' => []],
            ),
            static function (array $props, RenderContext $ctx): string {
                $tone = self::choice($props['tone'] ?? 'light', ['light', 'dark', 'surface'], 'light');
                $eyebrow = (string) ($props['eyebrow'] ?? '');
                $heading = (string) ($props['heading'] ?? '');
                $images = is_array($props['images'] ?? null) ? $props['images'] : [];

                // A stable-enough id namespace per block instance so multiple
                // galleries on one page don't collide on their lightbox anchors.
                $gid = 'rxg' . substr(md5(json_encode($images) ?: uniqid('', true)), 0, 8);

                $head = '';
                if ($eyebrow !== '') $head .= '<span class="rx-eyebrow">' . \e($eyebrow) . '</span>';
                if ($heading !== '') $head .= '<h2 class="rx-h2">' . \e($heading) . '</h2>';

                $tiles = '';
                $lightboxes = '';
                foreach ($images as $i => $item) {
                    if (!is_array($item)) continue;
                    $media = is_array($item['media'] ?? null) ? $item['media'] : null;
                    $url = self::resolveMediaUrl($media);
                    if ($url === '') continue;
                    $label = (string) ($item['label'] ?? '');
                    $size = self::choice($item['size'] ?? 'normal', ['normal', 'wide', 'tall'], 'normal');
                    $sizeClass = $size !== 'normal' ? ' rx-tile-' . $size : '';
                    $alt = (string) ($media['alt'] ?? $label);
                    $anchorId = $gid . '-' . $i;

                    $tiles .= '<a class="rx-tile' . $sizeClass . '" href="#' . \e($anchorId) . '" style="background-image:url(\'' . \e($url) . '\')">'
                        . '<span class="rx-tile-zoom" aria-hidden="true">⤢</span>'
                        . ($label !== '' ? '<span class="rx-tile-label">' . \e($label) . '</span>' : '')
                        . '</a>';

                    $lightboxes .= '<div class="rx-lightbox" id="' . \e($anchorId) . '">'
                        . '<a href="#" class="rx-lightbox-close" aria-label="Close">✕</a>'
                        . '<img src="' . \e($url) . '" alt="' . \e($alt) . '" loading="lazy">'
                        . ($label !== '' ? '<div class="rx-lightbox-cap">' . \e($label) . '</div>' : '')
                        . '</div>';
                }

                return '<section class="rx rx-gallery rx-tone-' . \e($tone) . '"><div class="rx-inner">'
                    . ($head !== '' ? '<div class="rx-head">' . $head . '</div>' : '')
                    . '<div class="rx-gallery-grid">' . $tiles . '</div>'
                    . '</div></section>' . $lightboxes;
            },
        ));
    }

    private static function cardGridSchema(): FieldSchema
    {
        return FieldSchema::of([
            ['key' => 'eyebrow', 'type' => 'text', 'label' => 'Eyebrow'], ['key' => 'heading', 'type' => 'text', 'label' => 'Heading'], ['key' => 'cols', 'type' => 'select', 'label' => 'Columns', 'options' => [['v' => '2', 'l' => '2'], ['v' => '3', 'l' => '3'], ['v' => '4', 'l' => '4']]],
        ], ['eyebrow' => 'What we offer', 'heading' => 'Explore the possibilities', 'cols' => '3']);
    }

    private static function renderCardGrid(array $p, string $class): string
    {
        $cols = self::choice($p['cols'] ?? '3', ['2', '3', '4'], '3');
        $cards = '';
        foreach (['Simple and clear', 'Built for you', 'Made to last'] as $title) $cards .= '<div class="cb-card"><span class="cb-card-icon">✦</span><h3 class="cb-card-title">' . \e($title) . '</h3><p class="cb-card-text">A concise description that makes this section useful immediately.</p></div>';
        return '<section class="cb-section ' . $class . '"><div class="cb-section-inner"><span class="cb-eyebrow">' . \e((string)($p['eyebrow'] ?? '')) . '</span><h2 class="cb-section-title">' . \e((string)($p['heading'] ?? '')) . '</h2><div class="cb-grid cb-grid-' . $cols . '">' . $cards . '</div></div></section>';
    }

    private static function renderNestedBlocks(array $blocks): string
    {
        $out = '';
        foreach ($blocks as $block) {
            if (!is_array($block)) continue;
            $type = (string)($block['type'] ?? '');
            $p = is_array($block['props'] ?? null) ? $block['props'] : [];
            if ($type === 'heading') {
                $level = max(1, min(6, (int)($p['level'] ?? 2)));
                $out .= '<h' . $level . ' class="cb-heading">' . \e((string)($p['text'] ?? 'Heading')) . '</h' . $level . '>';
            } elseif ($type === 'paragraph') {
                $out .= '<p class="cb-paragraph">' . nl2br(\e((string)($p['text'] ?? 'Paragraph text'))) . '</p>';
            } elseif ($type === 'button') {
                $out .= '<a class="cb-btn cb-btn-primary" href="' . \e(\slate_safe_url((string)($p['href'] ?? '#'))) . '">' . \e((string)($p['text'] ?? 'Button')) . '</a>';
            } elseif ($type === 'container' || $type === 'columns') {
                $out .= '<div class="cb-nested-widget">' . self::renderNestedBlocks($type === 'container' ? (array)($p['children'] ?? []) : self::flattenColumns($p)) . '</div>';
            } else {
                $label = ucfirst(str_replace('-', ' ', $type ?: 'Widget'));
                $out .= '<div class="cb-nested-widget"><strong>' . \e($label) . '</strong></div>';
            }
        }
        return $out;
    }

    private static function flattenColumns(array $props): array
    {
        $out = [];
        foreach ((array)($props['cols'] ?? []) as $col) foreach ((array)($col['blocks'] ?? []) as $block) $out[] = $block;
        return $out;
    }

    /** @param array<string,mixed>|null $media */
    private static function resolveMediaUrl(?array $media): string
    {
        $key = trim((string) ($media['key'] ?? ''));
        if ($key === '') {
            return '';
        }
        // Logical key -> a path under uploads/, never an arbitrary host or an
        // escape from it (Phase 2 spec §8: "must not permit path traversal").
        $key = ltrim($key, '/');
        if ($key === '' || str_contains($key, '..') || str_contains($key, "\0")) {
            return '';
        }
        $base = defined('SLATE_URL') ? \SLATE_URL : '';
        return rtrim($base, '/') . '/uploads/' . $key;
    }

    /** @param array<string,mixed> $props */
    private static function renderHero(array $props): string
    {
        $layout = (string) ($props['layout'] ?? 'banner');
        $layout = in_array($layout, ['split', 'banner'], true) ? $layout : 'banner';
        $overlay = (string) ($props['overlay'] ?? 'medium');
        $overlay = in_array($overlay, ['light', 'medium', 'dark'], true) ? $overlay : 'medium';
        $height = (string) ($props['height'] ?? 'normal');
        $height = in_array($height, ['short', 'normal', 'tall'], true) ? $height : 'normal';
        $mediaSide = (($props['mediaSide'] ?? 'left') === 'right') ? 'right' : 'left';
        $pad = (string) ($props['pad'] ?? 'normal');
        $pad = in_array($pad, ['compact', 'normal', 'spacious'], true) ? $pad : 'normal';

        $media = is_array($props['media'] ?? null) ? $props['media'] : null;
        $imageUrl = self::resolveMediaUrl($media);
        $hasImg = $imageUrl !== '';

        $eyebrow = (string) ($props['eyebrow'] ?? '');
        $heading = (string) ($props['heading'] ?? '');
        $subheading = (string) ($props['subheading'] ?? '');
        $btnText = (string) ($props['btnText'] ?? '');
        $btnHref = (string) ($props['btnHref'] ?? '#');
        $btn2Text = (string) ($props['btn2Text'] ?? '');
        $btn2Href = (string) ($props['btn2Href'] ?? '#');

        $actions = '<div class="cb-hero-actions">';
        if ($btnText !== '') {
            $actions .= sprintf('<a class="cb-btn cb-btn-primary" href="%s">%s</a>', \e(\slate_safe_url($btnHref)), \e($btnText));
        }
        if ($btn2Text !== '') {
            $extra = ($hasImg && $layout === 'banner') ? ' style="color:#fff;box-shadow:inset 0 0 0 1.5px #fff"' : '';
            $actions .= sprintf('<a class="cb-btn cb-btn-outline" href="%s"%s>%s</a>', \e(\slate_safe_url($btn2Href)), $extra, \e($btn2Text));
        }
        $actions .= '</div>';

        $eyebrowHtml = $eyebrow !== '' ? '<span class="cb-eyebrow">' . \e($eyebrow) . '</span>' : '';
        $headingHtml = $heading !== '' ? '<h1 class="cb-hero-title">' . \e($heading) . '</h1>' : '';
        $subHtml = $subheading !== '' ? '<p class="cb-hero-sub">' . \e($subheading) . '</p>' : '';

        if ($layout === 'split' && $hasImg) {
            $cls = 'cb-hero cb-hero-split cb-hero-media-' . $mediaSide;
            return sprintf(
                '<section class="cb-pad-%s %s"><div class="cb-hero-inner">'
                    . '<div class="cb-hero-media"><img src="%s" alt="%s"></div>'
                    . '<div class="cb-hero-body">%s%s%s%s</div>'
                    . '</div></section>',
                \e($pad), \e($cls), \e(\slate_safe_url($imageUrl)), \e($heading),
                $eyebrowHtml, $headingHtml, $subHtml, $actions,
            );
        }

        $cls = 'cb-hero cb-hero-banner cb-hero-h-' . $height;
        $style = '';
        if ($hasImg) {
            $cls .= ' cb-overlay-' . $overlay;
            $style = ' style="background-image:url(\'' . \e(\slate_safe_url($imageUrl)) . '\')"';
        } else {
            $cls .= ' cb-hero-plain';
        }

        return sprintf(
            '<section class="cb-pad-%s %s"%s><div class="cb-hero-inner">%s%s%s%s</div></section>',
            \e($pad), \e($cls), $style, $eyebrowHtml, $headingHtml, $subHtml, $actions,
        );
    }
}
