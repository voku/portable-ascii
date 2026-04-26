<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * Polyfill implementation for transliterator_transliterate().
 *
 * This is a LIMITED polyfill that supports only a subset of ICU transliterator IDs.
 * It is intended for environments where ext-intl is not installed.
 *
 * Supported transliterator pipeline steps:
 * - NFC, NFD, NFKC, NFKD (Unicode normalization; requires Normalizer class from ext-intl or a polyfill)
 * - [:Nonspacing Mark:] Remove (combining mark removal via \p{Mn} regex)
 * - Any-Latin, Latin-ASCII, Any-ASCII
 * - Any-Upper, Any-Lower
 * - limited language aliases backed by existing package rules, e.g. de-ASCII and Turkmen-Latin/BGN
 *
 * Unsupported IDs trigger E_USER_WARNING and return false.
 * Custom ICU rules (containing '>' or '<' operators) are not supported and return false.
 *
 * Known divergences from ext-intl / ICU:
 * - Any-Latin and language-specific *-Latin aliases produce ASCII output
 *   because the underlying package rules map directly to ASCII.
 * - Normalization steps (NFC, NFD, NFKC, NFKD) are silently skipped if the
 *   Normalizer class is not available.
 * - Character mappings may differ from ICU data (e.g., Cyrillic, CJK, currency symbols).
 * - The full ICU rule-based transliteration syntax is not supported.
 */
final class TransliteratorPolyfill
{
    /**
     * Supported canonical transliterator pipeline steps.
     *
     * @var list<string>
     */
    private const SUPPORTED_STEPS = [
        'NFKC',
        'NFKD',
        'NFC',
        'NFD',
        '[:Nonspacing Mark:] Remove',
        'Any-Latin',
        'Latin-ASCII',
        'Any-ASCII',
        'Any-Upper',
        'Any-Lower',
    ];

    /**
     * Limited language aliases adapted from the referenced Symfony polyfill work.
     *
     * @var array<string, string>
     */
    private const LANGUAGE_STEP_ALIASES = [
        'amharic' => 'am',
        'arabic' => 'ar',
        'azerbaijani' => 'az',
        'belarusian' => 'be',
        'bulgarian' => 'bg',
        'bengali' => 'bn',
        'greek' => 'el',
        'persian' => 'fa',
        'hebrew' => 'he',
        'armenian' => 'hy',
        'georgian' => 'ka',
        'kazakh' => 'kk',
        'kirghiz' => 'ky',
        'korean' => 'ko',
        'macedonian' => 'mk',
        'mongolian' => 'mn',
        'oriya' => 'or',
        'pashto' => 'ps',
        'russian' => 'ru',
        'serbian' => 'sr',
        'thai' => 'th',
        'turkmen' => 'tk',
        'ukrainian' => 'uk',
        'uzbek' => 'uz',
        'han' => 'zh',
        'de' => 'de',
        'de_at' => 'de_at',
        'de_ch' => 'de_ch',
    ];

    /**
     * Transliterate a string using a subset of ICU transliterator IDs.
     *
     * This method matches the signature of PHP's transliterator_transliterate(),
     * but only supports the pipeline steps listed in SUPPORTED_STEPS.
     *
     * @param mixed  $transliterator transliterator ID string (Transliterator objects are not supported)
     * @param string $string         the string to transliterate
     * @param int    $start          start offset in codepoints (not bytes); default 0
     * @param int    $end            end offset in codepoints (not bytes); -1 means end of string
     *
     * @return string|false the transliterated string, or false on failure
     */
    public static function transliterate($transliterator, string $string, int $start = 0, int $end = -1)
    {
        if (!\is_string($transliterator)) {
            \trigger_error(
                'transliterator_transliterate(): Argument #1 ($transliterator) must be of type Transliterator|string, '
                . \gettype($transliterator) . ' given',
                \E_USER_WARNING
            );

            return false;
        }

        if (!self::isValidUtf8($transliterator)) {
            \trigger_error(
                'transliterator_transliterate(): polyfill requires a valid UTF-8 transliterator ID; invalid UTF-8 given',
                \E_USER_WARNING
            );

            return false;
        }

        if (!self::isValidUtf8($string)) {
            \trigger_error(
                'transliterator_transliterate(): polyfill requires a valid UTF-8 input string; invalid UTF-8 given',
                \E_USER_WARNING
            );

            return false;
        }

        if (!self::validateOffsets($start, $end)) {
            return false;
        }

        $transliterator = self::cleanId($transliterator);

        // Reject custom ICU rules (contain transformation operators)
        if (\strpos($transliterator, '>') !== false || \strpos($transliterator, '<') !== false) {
            \trigger_error(
                'transliterator_transliterate(): polyfill does not support custom ICU rules',
                \E_USER_WARNING
            );

            return false;
        }

        // Parse pipeline steps (semicolon-separated, trim whitespace, filter empty)
        $rawSteps = \array_values(\array_filter(
            \array_map('trim', \explode(';', $transliterator)),
            static function (string $s): bool {
                return $s !== '';
            }
        ));

        if (\count($rawSteps) === 0) {
            \trigger_error(
                'transliterator_transliterate(): polyfill received empty transliterator ID',
                \E_USER_WARNING
            );

            return false;
        }

        // Validate all steps before executing any
        $steps = [];
        foreach ($rawSteps as $step) {
            $normalizedStep = self::normalizeStep($step);
            if ($normalizedStep === null) {
                \trigger_error(
                    \sprintf(
                        'transliterator_transliterate(): polyfill does not support transliterator step "%s". Supported: %s',
                        $step,
                        \implode(', ', self::SUPPORTED_STEPS)
                        . ', Any-Upper, Any-Lower, limited <language>-ASCII and <language>-Latin[/BGN] aliases'
                    ),
                    \E_USER_WARNING
                );

                return false;
            }

            $steps[] = $normalizedStep;
        }

        // Handle start/end slicing (codepoint offsets, not byte offsets)
        if ($start !== 0 || $end !== -1) {
            return self::transliterateSlice($steps, $string, $start, $end);
        }

        return self::applyPipeline($steps, $string);
    }

    /**
     * Apply transliteration to a slice of the string defined by codepoint offsets.
     *
     * Characters outside the [start, end) range are preserved unchanged.
     *
     * @param list<array{type: string, value: string}> $steps validated pipeline steps
     * @param string       $string input string
     * @param int          $start  start codepoint offset
     * @param int          $end    end codepoint offset (-1 = end of string)
     *
     * @return string the string with the specified slice transliterated
     */
    private static function transliterateSlice(array $steps, string $string, int $start, int $end): string
    {
        // Split into codepoints using PCRE (works without ext-mbstring)
        \preg_match_all('/./us', $string, $matches);
        $codepoints = $matches[0];
        $len = \count($codepoints);

        // Normalize offsets
        $effectiveStart = \max(0, \min($start, $len));
        $effectiveEnd = ($end < 0) ? $len : \min($end, $len);

        if ($effectiveStart >= $effectiveEnd) {
            return $string;
        }

        $prefix = \implode('', \array_slice($codepoints, 0, $effectiveStart));
        $middle = \implode('', \array_slice($codepoints, $effectiveStart, $effectiveEnd - $effectiveStart));
        $suffix = \implode('', \array_slice($codepoints, $effectiveEnd));

        $transliterated = self::applyPipeline($steps, $middle);

        return $prefix . $transliterated . $suffix;
    }

    /**
     * Apply the validated pipeline steps to the string sequentially.
     *
     * @param list<array{type: string, value: string}> $steps validated pipeline steps
     * @param string       $string input string
     *
     * @return string the transliterated string
     */
    private static function applyPipeline(array $steps, string $string): string
    {
        $result = $string;

        foreach ($steps as $step) {
            switch ($step['type']) {
                case 'language':
                    $result = self::applyLanguageStep($result, $step['value']);

                    break;

                case 'step':
                    switch ($step['value']) {
                        case 'NFC':
                        case 'NFD':
                        case 'NFKC':
                        case 'NFKD':
                            $result = self::applyNormalization($result, $step['value']);

                            break;

                        case '[:Nonspacing Mark:] Remove':
                            $result = self::removeNonspacingMarks($result);

                            break;

                        case 'Any-Latin':
                        case 'Latin-ASCII':
                        case 'Any-ASCII':
                            $result = ASCII::to_transliterate($result, null, false);

                            break;

                        case 'Any-Upper':
                            $result = self::toUpper($result);

                            break;

                        case 'Any-Lower':
                            $result = self::toLower($result);

                            break;
                    }

                    break;
            }
        }

        return $result;
    }

    /**
     * Apply Unicode normalization if a Normalizer class is available.
     *
     * If no Normalizer is available (neither ext-intl nor a polyfill package),
     * the string is returned unchanged. This is a documented limitation.
     *
     * @param string $string the string to normalize
     * @param string $form   one of NFC, NFD, NFKC, NFKD
     *
     * @return string the normalized string, or the original on failure/unavailability
     */
    private static function applyNormalization(string $string, string $form): string
    {
        if (!\class_exists('Normalizer', true)) {
            return $string;
        }

        switch ($form) {
            case 'NFC':
                $normalized = \Normalizer::normalize($string, \Normalizer::FORM_C);

                break;

            case 'NFD':
                $normalized = \Normalizer::normalize($string, \Normalizer::FORM_D);

                break;

            case 'NFKC':
                $normalized = \Normalizer::normalize($string, \Normalizer::FORM_KC);

                break;

            case 'NFKD':
                $normalized = \Normalizer::normalize($string, \Normalizer::FORM_KD);

                break;

            default:
                return $string;
        }

        return ($normalized !== false) ? $normalized : $string;
    }

    /**
     * Remove Unicode non-spacing marks (combining characters) using \p{Mn} regex.
     *
     * This handles combining accents, diacritics, etc. Works with PHP's PCRE
     * Unicode support without requiring ext-intl.
     *
     * @param string $string the string to process
     *
     * @return string the string with non-spacing marks removed
     */
    private static function removeNonspacingMarks(string $string): string
    {
        $result = \preg_replace('/\p{Mn}/u', '', $string);

        return ($result !== null) ? $result : $string;
    }

    private static function validateOffsets(int $start, int $end): bool
    {
        if ($start < 0) {
            return self::failForInvalidOffsets('Argument #3 ($start) must be greater than or equal to 0');
        }

        if ($end < -1) {
            return self::failForInvalidOffsets('Argument #4 ($end) must be greater than or equal to -1');
        }

        if ($end !== -1 && $start > $end) {
            return self::failForInvalidOffsets('Argument #3 ($start) must be less than or equal to argument #4 ($end)');
        }

        return true;
    }

    private static function isValidUtf8(string $string): bool
    {
        return \preg_match('//u', $string) === 1;
    }

    private static function failForInvalidOffsets(string $message): bool
    {
        $message = 'transliterator_transliterate(): ' . $message;

        if (\PHP_VERSION_ID >= 80000) {
            throw new \ValueError($message);
        }

        \trigger_error($message, \E_USER_WARNING);

        return false;
    }

    private static function applyLanguageStep(string $string, string $language): string
    {
        switch ($language) {
            case 'de':
                return self::applyGermanAsciiAlias($string, true);

            case 'de_at':
            case 'de_ch':
                return self::applyGermanAsciiAlias($string, false);
        }

        return ASCII::to_ascii($string, $language);
    }

    private static function applyGermanAsciiAlias(string $string, bool $expandUmlauts): string
    {
        if ($expandUmlauts) {
            $string = \preg_replace_callback(
                '/[ÄÖÜ](?=\p{Ll})/u',
                static function (array $matches): string {
                    switch ($matches[0]) {
                        case 'Ä':
                            return 'Ae';
                        case 'Ö':
                            return 'Oe';
                        case 'Ü':
                            return 'Ue';
                    }

                    return $matches[0];
                },
                $string
            ) ?? $string;

            $string = \strtr($string, [
                'Ä' => 'AE',
                'Ö' => 'OE',
                'Ü' => 'UE',
                'ä' => 'ae',
                'ö' => 'oe',
                'ü' => 'ue',
                'ß' => 'ss',
            ]);
        } else {
            $string = \strtr($string, [
                'Ä' => 'A',
                'Ö' => 'O',
                'Ü' => 'U',
                'ä' => 'a',
                'ö' => 'o',
                'ü' => 'u',
                'ß' => 'ss',
            ]);
        }

        return ASCII::to_transliterate($string, null, false);
    }

    private static function cleanId(string $id): string
    {
        $id = \trim($id);
        $id = \preg_replace('/\s*;\s*/', ';', $id) ?? $id;
        $id = \rtrim($id, ';');
        $id = \preg_replace(
            '/\[\s*:?\s*Nonspacing\s*Mark\s*:?\s*\]\s*Remove/i',
            '[:Nonspacing Mark:] Remove',
            $id
        ) ?? $id;

        return $id;
    }

    /**
     * @return array{type: string, value: string}|null
     */
    private static function normalizeStep(string $step): ?array
    {
        foreach (self::SUPPORTED_STEPS as $supportedStep) {
            if (\strcasecmp($step, $supportedStep) === 0) {
                return ['type' => 'step', 'value' => $supportedStep];
            }
        }

        if (\preg_match('/^([a-z_]+)-ascii$/i', $step, $matches) === 1) {
            $language = self::resolveLanguageAlias($matches[1]);
            if ($language !== null) {
                return ['type' => 'language', 'value' => $language];
            }
        }

        if (\preg_match('/^([a-z_]+)-latin(?:\/bgn)?$/i', $step, $matches) === 1) {
            $language = self::resolveLanguageAlias($matches[1]);
            if ($language !== null) {
                return ['type' => 'language', 'value' => $language];
            }
        }

        return null;
    }

    private static function resolveLanguageAlias(string $language): ?string
    {
        $language = \strtolower($language);

        return self::LANGUAGE_STEP_ALIASES[$language] ?? null;
    }

    private static function toUpper(string $string): string
    {
        if (\function_exists('mb_strtoupper')) {
            return \mb_strtoupper($string, 'UTF-8');
        }

        return \strtoupper($string);
    }

    private static function toLower(string $string): string
    {
        if (\function_exists('mb_strtolower')) {
            return \mb_strtolower($string, 'UTF-8');
        }

        return \strtolower($string);
    }
}
