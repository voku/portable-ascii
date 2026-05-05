<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * Shared transliterator ID helpers for the limited polyfill.
 *
 * @internal
 */
final class TransliteratorId
{
    public static function isValidUtf8(string $string): bool
    {
        return \Voku\Transliterator\TransliteratorId::isValidUtf8($string);
    }

    public static function normalize(string $id): string
    {
        return \Voku\Transliterator\TransliteratorId::normalize($id);
    }

    public static function containsUnsupportedRuleSyntax(string $id): bool
    {
        return \Voku\Transliterator\TransliteratorId::containsUnsupportedRuleSyntax($id);
    }
}
