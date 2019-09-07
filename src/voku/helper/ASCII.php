<?php

declare(strict_types=1);

namespace voku\helper;

final class ASCII
{
    /**
     * @var array|null
     */
    private static $ASCII_MAPS;

    /**
     * @var array|null
     */
    private static $ASCII_MAPS_EXTRAS;

    /**
     * @var array|null
     */
    private static $ORD;

    /**
     * bidirectional text chars
     *
     * url: https://www.w3.org/International/questions/qa-bidi-unicode-controls
     *
     * @var array
     */
    private static $BIDI_UNI_CODE_CONTROLS_TABLE = [
        // LEFT-TO-RIGHT EMBEDDING (use -> dir = "ltr")
        8234 => "\xE2\x80\xAA",
        // RIGHT-TO-LEFT EMBEDDING (use -> dir = "rtl")
        8235 => "\xE2\x80\xAB",
        // POP DIRECTIONAL FORMATTING // (use -> </bdo>)
        8236 => "\xE2\x80\xAC",
        // LEFT-TO-RIGHT OVERRIDE // (use -> <bdo dir = "ltr">)
        8237 => "\xE2\x80\xAD",
        // RIGHT-TO-LEFT OVERRIDE // (use -> <bdo dir = "rtl">)
        8238 => "\xE2\x80\xAE",
        // LEFT-TO-RIGHT ISOLATE // (use -> dir = "ltr")
        8294 => "\xE2\x81\xA6",
        // RIGHT-TO-LEFT ISOLATE // (use -> dir = "rtl")
        8295 => "\xE2\x81\xA7",
        // FIRST STRONG ISOLATE // (use -> dir = "auto")
        8296 => "\xE2\x81\xA8",
        // POP DIRECTIONAL ISOLATE
        8297 => "\xE2\x81\xA9",
    ];

    /**
     * Returns an replacement array for ASCII methods.
     *
     * @param bool $withExtras
     *
     * @return array
     */
    public static function charsArray(bool $withExtras = false): array {
        if ($withExtras) {
            self::prepareAsciiExtrasMaps();

            return self::$ASCII_MAPS_EXTRAS;
        }

        self::prepareAsciiMaps();

        return self::$ASCII_MAPS;
    }

    /**
     * Returns an replacement array for ASCII methods with a mix of multiple languages.
     *
     * @param bool $withExtras [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     *
     * @return array
     *               <p>An array of replacements.</p>
     */
    public static function charsArrayWithMultiLanguageValues(bool $withExtras = false): array
    {
        static $CHARS_ARRAY;
        $cacheKey = '' . $withExtras;

        /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
        if (isset($CHARS_ARRAY[$cacheKey])) {
            return $CHARS_ARRAY[$cacheKey];
        }

        // init
        $return = [];
        $returnTmp = self::charsArrayWithSingleLanguageValues($withExtras);

        foreach ((array) $returnTmp['replace'] as $replaceKey => $replaceValue) {
            foreach ((array) $returnTmp['orig'] as $origKey => $origValue) {
                if ($replaceKey === $origKey) {
                    $return[$replaceValue][] = $origValue;
                }
            }
        }

        $CHARS_ARRAY[$cacheKey] = $return;

        return $return;
    }

    /**
     * Returns an replacement array for ASCII methods with one language.
     *
     * For example, German will map 'ä' to 'ae', while other languages
     * will simply return e.g. 'a'.
     *
     * @param string $language   [optional] <p>Language of the source string e.g.: en, de_at, or de-ch</p>
     * @param bool   $withExtras [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     *
     * @return array{orig: string[], replace: string[]}
     *                     <p>An array of replacements.</p>
     */
    public static function charsArrayWithOneLanguage(
        string $language = 'en',
        bool $withExtras = false
    ): array {
        $regex = '/(?<first>[a-z]+)[\-_]\g{first}/i';
        $language = \str_replace(
            '-',
            '_',
            \strtolower(
                (string) \preg_replace($regex, '$1', $language)
            )
        );

        // init
        static $CHARS_ARRAY = [];
        $cacheKey = '' . $withExtras;

        // check static cache
        if (isset($CHARS_ARRAY[$cacheKey][$language])) {
            return $CHARS_ARRAY[$cacheKey][$language];
        }

        if ($withExtras) {
            self::prepareAsciiExtrasMaps();

            if (isset(self::$ASCII_MAPS[$language])) {
                $tmpArray = \array_merge(self::$ASCII_MAPS[$language] + self::$ASCII_MAPS_EXTRAS[$language]);

                $CHARS_ARRAY[$cacheKey][$language] = [
                    'orig'    => \array_keys($tmpArray),
                    'replace' => \array_values($tmpArray),
                ];
            } else {
                $CHARS_ARRAY[$cacheKey][$language] = [
                    'orig'    => '',
                    'replace' => '',
                ];
            }
        } else {
            self::prepareAsciiMaps();

            if (isset(self::$ASCII_MAPS[$language])) {
                $tmpArray = self::$ASCII_MAPS[$language];

                $CHARS_ARRAY[$cacheKey][$language] = [
                    'orig'    => \array_keys($tmpArray),
                    'replace' => \array_values($tmpArray),
                ];
            } else {
                $CHARS_ARRAY[$cacheKey][$language] = [
                    'orig'    => '',
                    'replace' => '',
                ];
            }
        }

        return $CHARS_ARRAY[$cacheKey][$language];
    }

    /**
     * Returns an replacement array for ASCII methods with multiple languages.
     *
     * @param bool $withExtras [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>
     *
     * @return array{orig: string[], replace: string[]}
     *                     <p>An array of replacements.</p>
     */
    public static function charsArrayWithSingleLanguageValues(bool $withExtras = false): array
    {
        // init
        static $CHARS_ARRAY = [];
        $cacheKey = '' . $withExtras;

        /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
        if (isset($CHARS_ARRAY[$cacheKey])) {
            return $CHARS_ARRAY[$cacheKey];
        }

        if ($withExtras) {
            self::prepareAsciiExtrasMaps();

            /** @noinspection AlterInForeachInspection */
            foreach (self::$ASCII_MAPS as &$map) {
                $CHARS_ARRAY[$cacheKey][] = $map;
            }

            /** @noinspection AlterInForeachInspection */
            foreach (self::$ASCII_MAPS_EXTRAS as &$map) {
                $CHARS_ARRAY[$cacheKey][] = $map;
            }
        } else {
            self::prepareAsciiMaps();

            /** @noinspection AlterInForeachInspection */
            foreach (self::$ASCII_MAPS as &$map) {
                $CHARS_ARRAY[$cacheKey][] = $map;
            }
        }

        $CHARS_ARRAY[$cacheKey] = \array_merge([], ...$CHARS_ARRAY[$cacheKey]);

        $CHARS_ARRAY[$cacheKey] = [
            'orig'    => \array_keys($CHARS_ARRAY[$cacheKey]),
            'replace' => \array_values($CHARS_ARRAY[$cacheKey]),
        ];

        return $CHARS_ARRAY[$cacheKey];
    }

    /**
     * Accepts a string and removes all non-UTF-8 characters from it + extras if needed.
     *
     * @param string $str                         <p>The string to be sanitized.</p>
     * @param bool   $normalize_whitespace        [optional] <p>Set to true, if you need to normalize the
     *                                            whitespace.</p>
     * @param bool   $normalize_msword            [optional] <p>Set to true, if you need to normalize MS Word chars
     *                                            e.g.: "…"
     *                                            => "..."</p>
     * @param bool   $keep_non_breaking_space     [optional] <p>Set to true, to keep non-breaking-spaces, in
     *                                            combination with
     *                                            $normalize_whitespace</p>
     * @param bool   $remove_invisible_characters [optional] <p>Set to false, if you not want to remove invisible
     *                                            characters e.g.: "\0"</p>
     *
     * @return string clean UTF-8 encoded string
     */
    public static function clean(
        string $str,
        bool $normalize_whitespace = true,
        bool $keep_non_breaking_space = false,
        bool $normalize_msword = true,
        bool $remove_invisible_characters = true
    ): string {
        // http://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
        // caused connection reset problem on larger strings

        $regex = '/
          (
            (?: [\x00-\x7F]               # single-byte sequences   0xxxxxxx
            |   [\xC0-\xDF][\x80-\xBF]    # double-byte sequences   110xxxxx 10xxxxxx
            |   [\xE0-\xEF][\x80-\xBF]{2} # triple-byte sequences   1110xxxx 10xxxxxx * 2
            |   [\xF0-\xF7][\x80-\xBF]{3} # quadruple-byte sequence 11110xxx 10xxxxxx * 3
            ){1,100}                      # ...one or more times
          )
        | ( [\x80-\xBF] )                 # invalid byte in range 10000000 - 10111111
        | ( [\xC0-\xFF] )                 # invalid byte in range 11000000 - 11111111
        /x';
        /** @noinspection NotOptimalRegularExpressionsInspection */
        $str = (string) \preg_replace($regex, '$1', $str);

        if ($normalize_whitespace === true) {
            $str = self::normalize_whitespace($str, $keep_non_breaking_space);
        }

        if ($normalize_msword === true) {
            $str = self::normalize_msword($str);
        }

        if ($remove_invisible_characters === true) {
            $str = self::remove_invisible_characters($str);
        }

        return $str;
    }

    /**
     * Checks if a string is 7 bit ASCII.
     *
     * @param string $str <p>The string to check.</p>
     *
     * @return bool
     *              <strong>true</strong> if it is ASCII<br>
     *              <strong>false</strong> otherwise
     */
    public static function is_ascii(string $str): bool
    {
        if ($str === '') {
            return true;
        }

        return !\preg_match('/[^\x09\x10\x13\x0A\x0D\x20-\x7E]/', $str);
    }

    /**
     * Returns a string with smart quotes, ellipsis characters, and dashes from
     * Windows-1252 (commonly used in Word documents) replaced by their ASCII
     * equivalents.
     *
     * @param string $str <p>The string to be normalized.</p>
     *
     * @return string
     */
    public static function normalize_msword(string $str): string
    {
        if ($str === '') {
            return '';
        }

        $keys = [
            "\xc2\xab", // « (U+00AB) in UTF-8
            "\xc2\xbb", // » (U+00BB) in UTF-8
            "\xe2\x80\x98", // ‘ (U+2018) in UTF-8
            "\xe2\x80\x99", // ’ (U+2019) in UTF-8
            "\xe2\x80\x9a", // ‚ (U+201A) in UTF-8
            "\xe2\x80\x9b", // ‛ (U+201B) in UTF-8
            "\xe2\x80\x9c", // “ (U+201C) in UTF-8
            "\xe2\x80\x9d", // ” (U+201D) in UTF-8
            "\xe2\x80\x9e", // „ (U+201E) in UTF-8
            "\xe2\x80\x9f", // ‟ (U+201F) in UTF-8
            "\xe2\x80\xb9", // ‹ (U+2039) in UTF-8
            "\xe2\x80\xba", // › (U+203A) in UTF-8
            "\xe2\x80\x93", // – (U+2013) in UTF-8
            "\xe2\x80\x94", // — (U+2014) in UTF-8
            "\xe2\x80\xa6", // … (U+2026) in UTF-8
        ];

        $values = [
            '"', // « (U+00AB) in UTF-8
            '"', // » (U+00BB) in UTF-8
            "'", // ‘ (U+2018) in UTF-8
            "'", // ’ (U+2019) in UTF-8
            "'", // ‚ (U+201A) in UTF-8
            "'", // ‛ (U+201B) in UTF-8
            '"', // “ (U+201C) in UTF-8
            '"', // ” (U+201D) in UTF-8
            '"', // „ (U+201E) in UTF-8
            '"', // ‟ (U+201F) in UTF-8
            "'", // ‹ (U+2039) in UTF-8
            "'", // › (U+203A) in UTF-8
            '-', // – (U+2013) in UTF-8
            '-', // — (U+2014) in UTF-8
            '...', // … (U+2026) in UTF-8
        ];

        return \str_replace($keys, $values, $str);
    }

    /**
     * Normalize the whitespace.
     *
     * @param string $str                     <p>The string to be normalized.</p>
     * @param bool   $keepNonBreakingSpace    [optional] <p>Set to true, to keep non-breaking-spaces.</p>
     * @param bool   $keepBidiUnicodeControls [optional] <p>Set to true, to keep non-printable (for the web)
     *                                        bidirectional text chars.</p>
     *
     * @return string
     */
    public static function normalize_whitespace(
        string $str,
        bool $keepNonBreakingSpace = false,
        bool $keepBidiUnicodeControls = false
    ): string {
        if ($str === '') {
            return '';
        }

        static $WHITESPACE_CACHE = [];
        $cacheKey = (int) $keepNonBreakingSpace;

        if (!isset($WHITESPACE_CACHE[$cacheKey])) {
            self::prepareAsciiMaps();

            $WHITESPACE_CACHE[$cacheKey] = self::$ASCII_MAPS[' '];

            if ($keepNonBreakingSpace === true) {
                unset($WHITESPACE_CACHE[$cacheKey]["\xc2\xa0"]);
            }

            $WHITESPACE_CACHE[$cacheKey] = \array_keys($WHITESPACE_CACHE[$cacheKey]);
        }

        if ($keepBidiUnicodeControls === false) {
            static $BIDI_UNICODE_CONTROLS_CACHE = null;

            if ($BIDI_UNICODE_CONTROLS_CACHE === null) {
                $BIDI_UNICODE_CONTROLS_CACHE = \array_values(self::$BIDI_UNI_CODE_CONTROLS_TABLE);
            }

            $str = \str_replace($BIDI_UNICODE_CONTROLS_CACHE, '', $str);
        }

        return \str_replace($WHITESPACE_CACHE[$cacheKey], ' ', $str);
    }

    /**
     * Remove invisible characters from a string.
     *
     * e.g.: This prevents sandwiching null characters between ascii characters, like Java\0script.
     *
     * copy&past from https://github.com/bcit-ci/CodeIgniter/blob/develop/system/core/Common.php
     *
     * @param string $str
     * @param bool   $url_encoded
     * @param string $replacement
     *
     * @return string
     */
    public static function remove_invisible_characters(
        string $str,
        bool $url_encoded = true,
        string $replacement = ''
    ): string {
        // init
        $non_displayables = [];

        // every control character except newline (dec 10),
        // carriage return (dec 13) and horizontal tab (dec 09)
        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcefBCEF]/'; // url encoded 00-08, 11, 12, 14, 15
            $non_displayables[] = '/%1[0-9a-fA-F]/'; // url encoded 16-31
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S'; // 00-08, 11, 12, 14-31, 127

        do {
            $str = (string) \preg_replace($non_displayables, $replacement, $str, -1, $count);
        } while ($count !== 0);

        return $str;
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * by default. The language or locale of the source string can be supplied
     * for language-specific transliteration in any of the following formats:
     * en, en_GB, or en-GB. For example, passing "de" results in "äöü" mapping
     * to "aeoeue" rather than "aou" as in other languages.
     *
     * @param string $str               <p>The input string.</p>
     * @param string $language          [optional] <p>Language of the source string.</p>
     * @param bool   $removeUnsupported [optional] <p>Whether or not to remove the
     *                                  unsupported characters.</p>
     *
     * @return string
     *                <p>A string that contains only ASCII characters.</p>
     */
    public static function to_ascii(
        string $str,
        string $language = 'en',
        bool $removeUnsupported = true
    ): string {
        if ($str === '') {
            return '';
        }

        $langSpecific = self::charsArrayWithOneLanguage($language);
        if (!empty($langSpecific)) {
            $str = \str_replace($langSpecific['orig'], $langSpecific['replace'], $str);
        }

        foreach (self::charsArrayWithMultiLanguageValues() as $replace => $orig) {
            $str = \str_replace($orig, $replace, $str);
        }

        if ($removeUnsupported) {
            $str = (string) \str_replace(["\n\r", "\n", "\r", "\t"], ' ', $str);
            /** @noinspection NotOptimalRegularExpressionsInspection */
            $str = (string) \preg_replace('/[^\\x20-\\x7E]/u', '', $str);
        }

        return $str;
    }

    /**
     * Convert given string to safe filename (and keep string case).
     *
     * @param string $str
     * @param bool   $use_transliterate No transliteration, conversion etc. is done by default - unsafe characters are
     *                                  simply replaced with hyphen.
     * @param string $fallback_char
     *
     * @return string
     */
    public static function to_filename(
        string $str,
        bool $use_transliterate = false,
        string $fallback_char = '-'
    ): string {
        if ($use_transliterate === true) {
            $str = self::to_transliterate($str, $fallback_char);
        }

        $fallback_char_escaped = \preg_quote($fallback_char, '/');

        $str = (string) \preg_replace(
            [
                '/[^' . $fallback_char_escaped . '\\.\\-a-zA-Z0-9\\s]/', // 1) remove un-needed chars
                '/[\\s]+/u',                                             // 2) convert spaces to $fallback_char
                '/[' . $fallback_char_escaped . ']+/u',                  // 3) remove double $fallback_char's
            ],
            [
                '',
                $fallback_char,
                $fallback_char,
            ],
            $str
        );

        return \trim($str, $fallback_char);
    }

    /**
     * Converts the string into an URL slug. This includes replacing non-ASCII
     * characters with their closest ASCII equivalents, removing remaining
     * non-ASCII and non-alphanumeric characters, and replacing whitespace with
     * $separator. The separator defaults to a single dash, and the string
     * is also converted to lowercase. The language of the source string can
     * also be supplied for language-specific transliteration.
     *
     * @param string   $str
     * @param string   $separator    [optional] <p>The string used to replace whitespace.</p>
     * @param string   $language     [optional] <p>Language of the source string.</p>
     * @param string[] $replacements [optional] <p>A map of replaceable strings.</p>
     *
     * @return string
     *                <p>A string that has been converted to an URL slug.</p>
     */
    public static function to_slugify(
        string $str,
        string $separator = '-',
        string $language = 'en',
        array $replacements = []
    ): string {
        if ($str === '') {
            return '';
        }

        foreach ($replacements as $from => $to) {
            $str = \str_replace($from, $to, $str);
        }

        $langSpecific = self::charsArrayWithOneLanguage($language, true);
        if (\count($langSpecific['orig']) > 0) {
            $str = \str_replace($langSpecific['orig'], $langSpecific['replace'], $str);
        }

        $charsArray = self::charsArrayWithSingleLanguageValues(true);
        $str = \str_replace($charsArray['orig'], $charsArray['replace'], $str);

        /** @noinspection CascadeStringReplacementInspection - FP */
        $str = \str_replace('@', $separator, $str);

        $str = (string) \preg_replace(
            '/[^a-zA-Z\\d\\s\\-_' . \preg_quote($separator, '/') . ']/u',
            '',
            $str
        );
        $str = (string) \preg_replace('/^[\'\\s]+|[\'\\s]+$/', '', \strtolower($str));
        $str = (string) \preg_replace('/\\B([A-Z])/', '/-\\1/', $str);
        $str = (string) \preg_replace('/[\\-_\\s]+/', $separator, $str);

        $l = \strlen($separator);
        if (\strpos($str, $separator) === 0) {
            $str = (string) \substr($str, $l);
        }

        if (\substr($str, -$l) === $separator) {
            $str = (string) \substr($str, 0, \strlen($str) - $l);
        }

        return $str;
    }

    /**
     * Returns an ASCII version of the string. A set of non-ASCII characters are
     * replaced with their closest ASCII counterparts, and the rest are removed
     * unless instructed otherwise.
     *
     * @param string $str     <p>The input string.</p>
     * @param string $unknown [optional] <p>Character use if character unknown. (default is ?)</p>
     * @param bool   $strict  [optional] <p>Use "transliterator_transliterate()" from PHP-Intl | WARNING: bad
     *                        performance</p>
     *
     * @return string
     *                <p>A String that contains only ASCII characters.</p>
     */
    public static function to_transliterate(
        string $str,
        string $unknown = '?',
        bool $strict = false
    ): string {
        static $UTF8_TO_ASCII;
        static $SUPPORT = [];

        if ($str === '') {
            return '';
        }

        if (!isset($SUPPORT['intl'])) {
            $SUPPORT['intl'] = \extension_loaded('intl');
        }

        // check if we only have ASCII, first (better performance)
        if (self::is_ascii($str) === true) {
            return $str;
        }

        $str = self::clean($str);

        // check again, if we only have ASCII, now ...
        if (self::is_ascii($str) === true) {
            return $str;
        }

        if (
            $strict === true
            &&
            $SUPPORT['intl'] === true
        ) {
            // INFO: https://unicode.org/cldr/utility/character.jsp?a=%E2%84%8C
            /** @noinspection PhpComposerExtensionStubsInspection */
            /** @noinspection UnnecessaryCastingInspection */
            $str = (string) \transliterator_transliterate('NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII;', $str);

            // check again, if we only have ASCII, now ...
            if (self::is_ascii($str) === true) {
                return $str;
            }
        }

        if (self::$ORD === null) {
            self::$ORD = self::getData('ascii_ord');
        }

        \preg_match_all('/.|[^\x00]$/us', $str, $ar);
        $chars = $ar[0];
        $ord = null;
        /** @noinspection ForeachSourceInspection */
        foreach ($chars as &$c) {
            $ordC0 = self::$ORD[$c[0]];

            if ($ordC0 >= 0 && $ordC0 <= 127) {
                continue;
            }

            $ordC1 = self::$ORD[$c[1]];

            // ASCII - next please
            if ($ordC0 >= 192 && $ordC0 <= 223) {
                $ord = ($ordC0 - 192) * 64 + ($ordC1 - 128);
            }

            if ($ordC0 >= 224) {
                $ordC2 = self::$ORD[$c[2]];

                if ($ordC0 <= 239) {
                    $ord = ($ordC0 - 224) * 4096 + ($ordC1 - 128) * 64 + ($ordC2 - 128);
                }

                if ($ordC0 >= 240) {
                    $ordC3 = self::$ORD[$c[3]];

                    if ($ordC0 <= 247) {
                        $ord = ($ordC0 - 240) * 262144 + ($ordC1 - 128) * 4096 + ($ordC2 - 128) * 64 + ($ordC3 - 128);
                    }

                    if ($ordC0 >= 248) {
                        $ordC4 = self::$ORD[$c[4]];

                        if ($ordC0 <= 251) {
                            $ord = ($ordC0 - 248) * 16777216 + ($ordC1 - 128) * 262144 + ($ordC2 - 128) * 4096 + ($ordC3 - 128) * 64 + ($ordC4 - 128);
                        }

                        if ($ordC0 >= 252) {
                            $ordC5 = self::$ORD[$c[5]];

                            if ($ordC0 <= 253) {
                                $ord = ($ordC0 - 252) * 1073741824 + ($ordC1 - 128) * 16777216 + ($ordC2 - 128) * 262144 + ($ordC3 - 128) * 4096 + ($ordC4 - 128) * 64 + ($ordC5 - 128);
                            }
                        }
                    }
                }
            }

            if ($ordC0 === 254 || $ordC0 === 255) {
                $c = $unknown;

                continue;
            }

            if ($ord === null) {
                $c = $unknown;

                continue;
            }

            $bank = $ord >> 8;
            if (!isset($UTF8_TO_ASCII[$bank])) {
                $UTF8_TO_ASCII[$bank] = self::getDataIfExists(\sprintf('x%02x', $bank));
                if ($UTF8_TO_ASCII[$bank] === false) {
                    $UTF8_TO_ASCII[$bank] = [];
                }
            }

            $newchar = $ord & 255;

            /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
            if (isset($UTF8_TO_ASCII[$bank][$newchar])) {

                // keep for debugging
                /*
                echo "file: " . sprintf('x%02x', $bank) . "\n";
                echo "char: " . $c . "\n";
                echo "ord: " . $ord . "\n";
                echo "newchar: " . $newchar . "\n";
                echo "ascii: " . $UTF8_TO_ASCII[$bank][$newchar] . "\n";
                echo "bank:" . $bank . "\n\n";
                 */

                $c = $UTF8_TO_ASCII[$bank][$newchar];
            } else {

                // keep for debugging missing chars
                /*
                echo "file: " . sprintf('x%02x', $bank) . "\n";
                echo "char: " . $c . "\n";
                echo "ord: " . $ord . "\n";
                echo "newchar: " . $newchar . "\n";
                echo "bank:" . $bank . "\n\n";
                 */

                $c = $unknown;
            }
        }

        return \implode('', $chars);
    }

    /**
     * get data from "/data/*.php"
     *
     * @param string $file
     *
     * @return array
     */
    private static function getData(string $file): array
    {
        /** @noinspection PhpIncludeInspection */
        /** @noinspection UsingInclusionReturnValueInspection */
        /** @psalm-suppress UnresolvableInclude */
        return include __DIR__ . '/data/' . $file . '.php';
    }

    /**
     * get data from "/data/*.php"
     *
     * @param string $file
     *
     * @return false|mixed will return false on error
     */
    private static function getDataIfExists(string $file)
    {
        $file = __DIR__ . '/data/' . $file . '.php';
        if (\file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            /** @noinspection UsingInclusionReturnValueInspection */
            return include $file;
        }

        return false;
    }

    private static function prepareAsciiExtrasMaps()
    {
        if (self::$ASCII_MAPS_EXTRAS === null) {
            self::prepareAsciiMaps();

            self::$ASCII_MAPS_EXTRAS = \array_merge(
                self::$ASCII_MAPS,
                self::getData('ascii_extras_by_languages')
            );
        }
    }

    private static function prepareAsciiMaps()
    {
        if (self::$ASCII_MAPS === null) {
            self::$ASCII_MAPS = self::getData('ascii_by_languages');
        }
    }
}
