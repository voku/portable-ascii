<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * Shared transliterator ID helpers for the limited polyfill.
 *
 * All regex patterns are static constants. User-provided transliterator IDs are
 * validated as UTF-8 and normalized, but never executed as regex patterns.
 *
 * @internal
 */
final class TransliteratorId
{
    private const UTF8_VALIDATION_PATTERN = '//u';
    private const STEP_SEPARATOR_PATTERN = '/\s*;\s*/';
    private const NONSPACING_MARK_REMOVE_PATTERN = '/\[\s*:?\s*Nonspacing\s*Mark\s*:?\s*\]\s*Remove/i';

    public static function isValidUtf8(string $string): bool
    {
        return \preg_match(self::UTF8_VALIDATION_PATTERN, $string) === 1;
    }

    public static function normalize(string $id): string
    {
        $id = \trim($id);
        $id = \preg_replace(self::STEP_SEPARATOR_PATTERN, ';', $id) ?? $id;
        $id = \rtrim($id, ';');

        return \preg_replace(
            self::NONSPACING_MARK_REMOVE_PATTERN,
            '[:Nonspacing Mark:] Remove',
            $id
        ) ?? $id;
    }

    public static function containsUnsupportedRuleSyntax(string $id): bool
    {
        return \strpos($id, '>') !== false || \strpos($id, '<') !== false;
    }
}
