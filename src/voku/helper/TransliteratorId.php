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

    public static function isValidUtf8(string $string): bool
    {
        return \preg_match(self::UTF8_VALIDATION_PATTERN, $string) === 1;
    }

    public static function normalize(string $id): string
    {
        $steps = \array_map('trim', \explode(';', \trim($id)));
        $steps = \array_values(\array_filter($steps, static function (string $step): bool {
            return $step !== '';
        }));

        return \implode(';', $steps);
    }

    public static function containsUnsupportedRuleSyntax(string $id): bool
    {
        return \strpos($id, '>') !== false || \strpos($id, '<') !== false;
    }
}
