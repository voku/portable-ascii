<?php

declare(strict_types=1);

namespace Voku\Transliterator\Tests;

use Voku\Transliterator\Transliterator;
use Voku\Transliterator\TransliteratorPolyfill;

/**
 * Upstream-derived facade smoke tests.
 *
 * These intentionally cover only a small subset of the root compatibility
 * matrix to prove that the nested package exposes the same behavior.
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

    /**
     * @param callable $callback
     *
     * @return array{result: mixed, warning: string|null}
     */
    private static function callCapturingWarning(callable $callback): array
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
            $result = $callback();
        } finally {
            \restore_error_handler();
        }

        return ['result' => $result, 'warning' => $warning];
    }

    public function testFacadeRejectsUnsupportedLatinTitleFromUpstreamBasicCase(): void
    {
        $captured = self::transliterateCapturingWarning('Latin; Title', 'Κοντογιαννάτος, Βασίλης');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('Latin', $captured['warning']);
    }

    public function testFacadeSupportsOffsetSlicingForSupportedIds(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'déjà vu résumé', 8, 14);

        static::assertSame('déjà vu resume', $result);
    }

    public function testFacadeObjectWrapperSupportsMethodAndStaticUsage(): void
    {
        $transliterator = Transliterator::create('Latin-ASCII');

        static::assertInstanceOf(Transliterator::class, $transliterator);
        static::assertSame('cafe', $transliterator->transliterate('café'));
        static::assertSame('cafe', TransliteratorPolyfill::transliterate($transliterator, 'café'));
    }

    public function testFacadeRejectsInvalidUtf8Input(): void
    {
        $captured = self::transliterateCapturingWarning('Latin-ASCII', "\x80\x03");

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('invalid UTF-8', $captured['warning']);
    }

    public function testFacadeRejectsIcuRuleStrings(): void
    {
        $captured = self::transliterateCapturingWarning('[\p{White_Space}] hex', ' o');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('[\p{White_Space}] hex', $captured['warning']);
    }

    public function testFacadeRejectsStringableObjectsInsteadOfCastingThem(): void
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

    public function testFacadeRejectsCreateFromRulesBecauseThereIsNoFullIcuParserExecutor(): void
    {
        $captured = self::callCapturingWarning(static function () {
            return Transliterator::createFromRules('[\p{White_Space}] hex');
        });

        static::assertNull($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('full ICU rule parser/executor', $captured['warning']);
    }
}
