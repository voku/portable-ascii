<?php

declare(strict_types=1);

namespace Voku\Transliterator\Tests;

use Voku\Transliterator\TransliteratorPolyfill;

/**
 * Environment-dependent behavior is an explicit contract, not ICU parity.
 *
 * The CI matrix sets the expectation variables to catch a mistakenly enabled
 * extension; in an ordinary local run the tests use the actual environment.
 *
 * @internal
 */
final class ExtensionEnvironmentContractTest extends \PHPUnit\Framework\TestCase
{
    public function testConfiguredExtensionModesAreActuallyApplied(): void
    {
        $intl = \getenv('POLYFILL_EXPECT_INTL');
        if ($intl !== false) {
            static::assertSame($intl === '1', \class_exists('Normalizer', true));
        }

        $mbstring = \getenv('POLYFILL_EXPECT_MBSTRING');
        if ($mbstring !== false) {
            static::assertSame($mbstring === '1', \function_exists('mb_strtoupper'));
            static::assertSame($mbstring === '1', \function_exists('mb_strtolower'));
        }
    }

    public function testNfcIsNoOpWhenNormalizerIsUnavailable(): void
    {
        $decomposed = "e\u{0301}";
        $expected = \class_exists('Normalizer', true) ? 'é' : $decomposed;

        static::assertSame(
            $expected,
            TransliteratorPolyfill::transliterate('NFC', $decomposed)
        );
    }

    public function testUnicodeUppercaseDependsOnMbstring(): void
    {
        $expected = \function_exists('mb_strtoupper') ? 'Ä' : 'ä';

        static::assertSame(
            $expected,
            TransliteratorPolyfill::transliterate('Any-Upper', 'ä')
        );
    }
}
