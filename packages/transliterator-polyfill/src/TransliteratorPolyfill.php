<?php

declare(strict_types=1);

namespace Voku\Transliterator;

final class TransliteratorPolyfill
{
    /**
     * Thin package facade backed by voku/portable-ascii.
     *
     * @param mixed  $transliterator transliterator ID string or limited Transliterator object polyfill
     * @param string $string         the string to transliterate
     * @param int    $start          start offset in codepoints
     * @param int    $end            end offset in codepoints; -1 means end of string
     *
     * @return string|false
     */
    public static function transliterate($transliterator, string $string, int $start = 0, int $end = -1)
    {
        if ($transliterator instanceof Transliterator) {
            $transliterator = $transliterator->getId();
        }

        return \voku\helper\TransliteratorPolyfill::transliterate($transliterator, $string, $start, $end);
    }
}
