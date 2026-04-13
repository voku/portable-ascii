<?php

/**
 * Bootstrap file for the transliterator_transliterate() polyfill.
 *
 * Conditionally defines the global transliterator_transliterate() function
 * when ext-intl is not installed. The implementation delegates to
 * \voku\helper\TransliteratorPolyfill::transliterate().
 *
 * This polyfill supports a LIMITED subset of ICU transliterator IDs.
 * See TransliteratorPolyfill class documentation for the supported subset
 * and known divergences from ext-intl / ICU.
 */
if (!\function_exists('transliterator_transliterate')) {
    /**
     * Polyfill for transliterator_transliterate().
     *
     * @param \Transliterator|string $transliterator transliterator ID string
     * @param string                 $string         the string to transliterate
     * @param int                    $start          start codepoint offset (default 0)
     * @param int                    $end            end codepoint offset (-1 = end of string)
     *
     * @return string|false the transliterated string, or false on failure
     *
     * @see \voku\helper\TransliteratorPolyfill::transliterate()
     */
    function transliterator_transliterate($transliterator, string $string, int $start = 0, int $end = -1)
    {
        return \voku\helper\TransliteratorPolyfill::transliterate($transliterator, $string, $start, $end);
    }
}
