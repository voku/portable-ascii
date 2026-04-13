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
 * - Any-Latin (any script to Latin; uses package transliteration tables)
 * - Latin-ASCII (Latin with diacritics to ASCII)
 * - Any-ASCII (any script directly to ASCII)
 *
 * Unsupported IDs trigger E_USER_WARNING and return false.
 * Custom ICU rules (containing '>' or '<' operators) are not supported and return false.
 *
 * Known divergences from ext-intl / ICU:
 * - Any-Latin produces ASCII output (not Latin-with-diacritics) because the
 *   underlying transliteration tables map directly to ASCII.
 * - Normalization steps (NFC, NFD, NFKC, NFKD) are silently skipped if the
 *   Normalizer class is not available.
 * - Character mappings may differ from ICU data (e.g., Cyrillic, CJK, currency symbols).
 * - The full ICU rule-based transliteration syntax is not supported.
 */
final class TransliteratorPolyfill
{
    /**
     * Supported transliterator pipeline steps.
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

        // Reject custom ICU rules (contain transformation operators)
        if (\strpos($transliterator, '>') !== false || \strpos($transliterator, '<') !== false) {
            \trigger_error(
                'transliterator_transliterate(): polyfill does not support custom ICU rules',
                \E_USER_WARNING
            );

            return false;
        }

        // Parse pipeline steps (semicolon-separated, trim whitespace, filter empty)
        $steps = \array_values(\array_filter(
            \array_map('trim', \explode(';', $transliterator)),
            static function (string $s): bool {
                return $s !== '';
            }
        ));

        if (\count($steps) === 0) {
            \trigger_error(
                'transliterator_transliterate(): polyfill received empty transliterator ID',
                \E_USER_WARNING
            );

            return false;
        }

        // Validate all steps before executing any
        foreach ($steps as $step) {
            if (!\in_array($step, self::SUPPORTED_STEPS, true)) {
                \trigger_error(
                    \sprintf(
                        'transliterator_transliterate(): polyfill does not support transliterator step "%s". Supported: %s',
                        $step,
                        \implode(', ', self::SUPPORTED_STEPS)
                    ),
                    \E_USER_WARNING
                );

                return false;
            }
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
     * @param list<string> $steps  validated pipeline steps
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
     * @param list<string> $steps  validated pipeline steps
     * @param string       $string input string
     *
     * @return string the transliterated string
     */
    private static function applyPipeline(array $steps, string $string): string
    {
        $result = $string;

        foreach ($steps as $step) {
            switch ($step) {
                case 'NFC':
                case 'NFD':
                case 'NFKC':
                case 'NFKD':
                    $result = self::applyNormalization($result, $step);

                    break;

                case '[:Nonspacing Mark:] Remove':
                    $result = self::removeNonspacingMarks($result);

                    break;

                case 'Any-Latin':
                case 'Latin-ASCII':
                case 'Any-ASCII':
                    $result = ASCII::to_transliterate($result, null, false);

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
}
