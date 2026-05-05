<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * Backward-compatible facade around the extracted transliterator package.
 */
final class TransliteratorPolyfill
{
    /**
     * @param mixed  $transliterator
     * @param string $string
     * @param int    $start
     * @param int    $end
     *
     * @return string|false
     */
    public static function transliterate($transliterator, string $string, int $start = 0, int $end = -1)
    {
        if ($transliterator instanceof Transliterator) {
            $transliterator = $transliterator->toPackageTransliterator();
        }

        return \Voku\Transliterator\TransliteratorPolyfill::transliterate($transliterator, $string, $start, $end);
    }
}
