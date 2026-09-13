<?php
declare(strict_types=1);

namespace Slate\Presentation;

use InvalidArgumentException;

/** Pure immutable operations used by React/Craft.js/dnd-kit adapters. */
final class DocumentOperations
{
    /** @param array<string,mixed> $document @param array<string,mixed> $block @return array<string,mixed> */
    public static function insertBlock(array $document, string $sectionId, int $index, array $block): array
    {
        $out = DocumentSchema::normalize($document);
        foreach ($out['sections'] as &$section) {
            if ($section['id'] !== $sectionId) continue;
            if ($index < 0 || $index > count($section['blocks'])) throw new InvalidArgumentException('Block insertion index is out of range.');
            array_splice($section['blocks'], $index, 0, [$block]);
            return $out;
        }
        throw new InvalidArgumentException('Section does not exist.');
    }

    /** @param array<string,mixed> $document @return array<string,mixed> */
    public static function moveBlock(array $document, string $fromSectionId, int $fromIndex, string $toSectionId, int $toIndex): array
    {
        $out = DocumentSchema::normalize($document);
        $from = self::sectionIndex($out, $fromSectionId);
        $to = self::sectionIndex($out, $toSectionId);
        if (!isset($out['sections'][$from]['blocks'][$fromIndex])) throw new InvalidArgumentException('Source block does not exist.');
        $block = array_splice($out['sections'][$from]['blocks'], $fromIndex, 1)[0];
        if ($toIndex < 0 || $toIndex > count($out['sections'][$to]['blocks'])) throw new InvalidArgumentException('Block destination index is out of range.');
        array_splice($out['sections'][$to]['blocks'], $toIndex, 0, [$block]);
        return $out;
    }

    /** @param array<string,mixed> $document @param array<string,mixed> $patch @return array<string,mixed> */
    public static function updateBlockProps(array $document, string $sectionId, int $index, array $patch): array
    {
        $out = DocumentSchema::normalize($document);
        $si = self::sectionIndex($out, $sectionId);
        if (!isset($out['sections'][$si]['blocks'][$index])) throw new InvalidArgumentException('Block does not exist.');
        $props = $out['sections'][$si]['blocks'][$index]['props'] ?? [];
        $out['sections'][$si]['blocks'][$index]['props'] = array_merge($props, $patch);
        return $out;
    }

    /** @param array<string,mixed> $document */
    private static function sectionIndex(array $document, string $sectionId): int
    {
        foreach ($document['sections'] as $index => $section) if (($section['id'] ?? '') === $sectionId) return $index;
        throw new InvalidArgumentException('Section does not exist.');
    }
}
