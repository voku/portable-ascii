<?php

declare(strict_types=1);

namespace voku\tests;

use voku\helper\TransliteratorPolyfill;

/**
 * Upstream compatibility matrix adapted from PHP's ext/intl PHPT tests.
 *
 * Source files inspected:
 * - ext/intl/tests/transliterator_transliterate_basic.phpt
 * - ext/intl/tests/transliterator_transliterate_error.phpt
 * - ext/intl/tests/transliterator_transliterate_variant1.phpt
 * - ext/intl/transliterator/transliterator.stub.php
 *
 * Matrix:
 * - basic.phpt / "Latin; Title" Greek transliteration => unsupported here; warn + false.
 * - basic.phpt / start+end offsets => supported for limited IDs; codepoint slicing is tested here.
 * - error.phpt / start past end of string => divergence; upstream intl errors, polyfill returns input unchanged.
 * - error.phpt / invalid UTF-8 input => supported safety behavior; warn + false.
 * - variant1.phpt / ICU rule-string ID => divergence; upstream executes ICU rule, polyfill rejects it.
 * - variant1.phpt / invalid UTF-8 ID => supported safety behavior; warn + false.
 * - variant1.phpt / object with __toString() => divergence; upstream string-casts, polyfill rejects objects.
 *
 * @internal
 */
final class TransliteratorPolyfillUpstreamCompatibilityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param mixed $transliterator
     *
     * @return array{result: string|false, warning: string|null}
     */
    private static function transliterateCapturingWarning($transliterator, string $string, int $start = 0, int $end = -1): array
    {
        $warning = null;
        \set_error_handler(static function (int $errno, string $errstr) use (&$warning): bool {
            if ($errno !== \E_USER_WARNING) {
                return false;
            }

            $warning = $errstr;

            return true;
        }, \E_USER_WARNING);

        try {
            $result = TransliteratorPolyfill::transliterate($transliterator, $string, $start, $end);
        } finally {
            \restore_error_handler();
        }

        return ['result' => $result, 'warning' => $warning];
    }

    private static function assertInvalidOffsets(int $start, int $end): void
    {
        if (\PHP_VERSION_ID >= 80000) {
            try {
                TransliteratorPolyfill::transliterate('Latin-ASCII', 'café', $start, $end);
                static::fail('Expected ValueError for invalid start/end offsets.');
            } catch (\ValueError $exception) {
                static::assertStringContainsString('transliterator_transliterate()', $exception->getMessage());
            }

            return;
        }

        $captured = self::transliterateCapturingWarning('Latin-ASCII', 'café', $start, $end);

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
    }

    public function testBasicPhptLatinTitleIsExplicitlyUnsupported(): void
    {
        $captured = self::transliterateCapturingWarning('Latin; Title', 'Κοντογιαννάτος, Βασίλης');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('Latin', $captured['warning']);
    }

    public function testBasicPhptOffsetSlicingIsSupportedForLimitedIds(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'déjà vu résumé', 8, 14);

        static::assertSame('déjà vu resume', $result);
    }

    public function testErrorPhptStartPastStringLengthReturnsInputUnchanged(): void
    {
        $captured = self::transliterateCapturingWarning('Latin-ASCII', 'str', 7);

        static::assertSame('str', $captured['result']);
        static::assertNull($captured['warning']);
    }

    public function testErrorPhptStartGreaterThanEndStillRejectsInvalidOffsets(): void
    {
        self::assertInvalidOffsets(7, 6);
    }

    public function testErrorPhptInvalidUtf8InputIsRejectedSafely(): void
    {
        $captured = self::transliterateCapturingWarning('Latin-ASCII', "\x80\x03");

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('invalid UTF-8', $captured['warning']);
    }

    public function testVariant1PhptIcuRuleStringsAreRejectedInsteadOfExecuted(): void
    {
        $captured = self::transliterateCapturingWarning('[\p{White_Space}] hex', ' o');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('[\p{White_Space}] hex', $captured['warning']);
    }

    public function testVariant1PhptInvalidUtf8IdIsRejectedSafely(): void
    {
        $captured = self::transliterateCapturingWarning("\x8F", ' o');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('invalid UTF-8', $captured['warning']);
    }

    public function testVariant1PhptStringableObjectsAreRejectedInsteadOfStringCast(): void
    {
        $transliterator = new class {
            public function __toString(): string
            {
                return 'inexistent id';
            }
        };

        $captured = self::transliterateCapturingWarning($transliterator, ' o');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('object', $captured['warning']);
    }
}
