<?php

declare(strict_types=1);

namespace Voku\Transliterator;

use voku\helper\ASCII;

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
 * @phpstan-type SupportedLanguageAlias 'am'|'ar'|'az'|'be'|'bg'|'bn'|'de'|'de_at'|'de_ch'|'el'|'fa'|'hy'|'ka'|'kk'|'ko'|'ky'|'mk'|'mn'|'or'|'ps'|'ru'|'sr'|'th'|'tk'|'uk'|'uz'|'zh'
 * @phpstan-type NormalizedStep array{type: 'step', value: string}|array{type: 'language', value: SupportedLanguageAlias}
 */
final class TransliteratorPolyfill
{
    private const CODEPOINT_SPLIT_PATTERN = '/./us';
    private const NONSPACING_MARK_PATTERN = '/\p{Mn}/u';
    private const TITLECASE_UMLAUT_PATTERN = '/[ÄÖÜ](?=\p{Ll})/u';

    /**
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
     * @var array<string, SupportedLanguageAlias>
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
            $transliterator = $transliterator->getId();
        }

        if (!\is_string($transliterator)) {
            \trigger_error(
                'transliterator_transliterate(): Argument #1 ($transliterator) must be of type Transliterator|string, '
                . \gettype($transliterator) . ' given',
                \E_USER_WARNING
            );

            return false;
        }

        if (!TransliteratorId::isValidUtf8($transliterator)) {
            \trigger_error(
                'transliterator_transliterate(): invalid UTF-8 in transliterator ID. Transliterator IDs must be valid UTF-8 strings.',
                \E_USER_WARNING
            );

            return false;
        }

        if (!TransliteratorId::isValidUtf8($string)) {
            \trigger_error(
                'transliterator_transliterate(): invalid UTF-8 in input string. Input strings must be valid UTF-8.',
                \E_USER_WARNING
            );

            return false;
        }

        if (!self::validateOffsets($start, $end)) {
            return false;
        }

        $transliterator = TransliteratorId::normalize($transliterator);

        if (TransliteratorId::containsUnsupportedRuleSyntax($transliterator)) {
            \trigger_error(
                'transliterator_transliterate(): polyfill does not support custom ICU rules',
                \E_USER_WARNING
            );

            return false;
        }

        $rawSteps = \array_values(\array_filter(
            \array_map('trim', \explode(';', $transliterator)),
            static function (string $step): bool {
                return $step !== '';
            }
        ));

        if (\count($rawSteps) === 0) {
            \trigger_error(
                'transliterator_transliterate(): polyfill received empty transliterator ID',
                \E_USER_WARNING
            );

            return false;
        }

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

        if ($start !== 0 || $end !== -1) {
            return self::transliterateSlice($steps, $string, $start, $end);
        }

        return self::applyPipeline($steps, $string);
    }

    /**
     * @param list<NormalizedStep> $steps
     */
    private static function transliterateSlice(array $steps, string $string, int $start, int $end): string
    {
        \preg_match_all(self::CODEPOINT_SPLIT_PATTERN, $string, $matches);
        $codepoints = $matches[0];
        $len = \count($codepoints);

        $effectiveStart = \max(0, \min($start, $len));
        $effectiveEnd = ($end < 0) ? $len : \min($end, $len);

        if ($effectiveStart >= $effectiveEnd) {
            return $string;
        }

        $prefix = \implode('', \array_slice($codepoints, 0, $effectiveStart));
        $middle = \implode('', \array_slice($codepoints, $effectiveStart, $effectiveEnd - $effectiveStart));
        $suffix = \implode('', \array_slice($codepoints, $effectiveEnd));

        return $prefix . self::applyPipeline($steps, $middle) . $suffix;
    }

    /**
     * @param list<NormalizedStep> $steps
     */
    private static function applyPipeline(array $steps, string $string): string
    {
        $result = $string;

        foreach ($steps as $step) {
            switch ($step['type']) {
                case 'language':
                    /** @phpstan-var SupportedLanguageAlias $language */
                    $language = $step['value'];
                    $result = self::applyLanguageStep($result, $language);

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

    private static function removeNonspacingMarks(string $string): string
    {
        $result = \preg_replace(self::NONSPACING_MARK_PATTERN, '', $string);

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

    private static function failForInvalidOffsets(string $message): bool
    {
        $message = 'transliterator_transliterate(): ' . $message;

        if (\PHP_VERSION_ID >= 80000) {
            throw new \ValueError($message);
        }

        \trigger_error($message, \E_USER_WARNING);

        return false;
    }

    /**
     * @phpstan-param SupportedLanguageAlias $language
     */
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
                self::TITLECASE_UMLAUT_PATTERN,
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

    /**
     * @return NormalizedStep|null
     */
    private static function normalizeStep(string $step): ?array
    {
        if (self::isNonspacingMarkRemovalStep($step)) {
            return ['type' => 'step', 'value' => '[:Nonspacing Mark:] Remove'];
        }

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

    private static function isNonspacingMarkRemovalStep(string $step): bool
    {
        $normalized = \str_replace(['[', ']', ':', ' '], '', \strtolower($step));

        return $normalized === 'nonspacingmarkremove';
    }

    /**
     * @phpstan-return SupportedLanguageAlias|null
     */
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
