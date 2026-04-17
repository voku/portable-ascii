<?php

declare(strict_types=1);

namespace Voku\Transliterator\Tests;

use Voku\Transliterator\TransliteratorPolyfill;

/**
 * @internal
 */
final class TransliteratorPolyfillTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return array{result: string|false, warning: string|null}
     */
    private static function transliterateCapturingWarning(mixed $transliterator, string $string, int $start = 0, int $end = -1): array
    {
        $warning = null;
        \set_error_handler(static function (int $errno, string $errstr) use (&$warning): bool {
            $warning = $errstr;

            return true;
        }, \E_USER_WARNING);

        $result = TransliteratorPolyfill::transliterate($transliterator, $string, $start, $end);
        \restore_error_handler();

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

    public function testAnyLatinLatinAsciiPipeline(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'déjà vu');
        static::assertSame('deja vu', $result);
    }

    public function testGermanAsciiRulePreservesUppercaseExpansions(): void
    {
        $result = TransliteratorPolyfill::transliterate('de-ASCII', 'Ä Ö Ü ä ö ü ß');
        static::assertSame('AE OE UE ae oe ue ss', $result);
    }

    public function testGermanAustrianAsciiRuleMatchesNativeAlias(): void
    {
        $result = TransliteratorPolyfill::transliterate('de_AT-ascii', 'Ä Ö Ü ä ö ü ß');
        static::assertSame('A O U a o u ss', $result);
    }

    public function testGermanSwissAsciiRuleMatchesNativeAlias(): void
    {
        $result = TransliteratorPolyfill::transliterate('de_CH-ascii', 'Ä Ö Ü ä ö ü ß');
        static::assertSame('A O U a o u ss', $result);
    }

    public function testGreekText(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'Any-Latin; Latin-ASCII',
            'Αυτή είναι μια δοκιμή'
        );
        static::assertSame('Aute einai mia dokime', $result);
    }

    public function testArabicText(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'أبز');
        static::assertSame('abz', $result);
    }

    public function testStartAndEndParameter(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'déjà vu résumé', 8, 14);
        static::assertSame('déjà vu resume', $result);
    }

    public function testNegativeStartOffsetIsRejected(): void
    {
        self::assertInvalidOffsets(-1, -1);
    }

    public function testEndLessThanMinusOneIsRejected(): void
    {
        self::assertInvalidOffsets(0, -2);
    }

    public function testStartGreaterThanEndIsRejected(): void
    {
        self::assertInvalidOffsets(3, 1);
    }

    public function testUnsupportedIdTriggersWarning(): void
    {
        $captured = self::transliterateCapturingWarning('Katakana-Hiragana', 'テスト');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('Katakana-Hiragana', $captured['warning']);
    }

    public function testCustomIcuRulesTriggersWarning(): void
    {
        $captured = self::transliterateCapturingWarning('a > b; b > c;', 'abc');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('custom ICU rules', $captured['warning']);
    }

    public function testNfkdPlusMarkRemoval(): void
    {
        $result = TransliteratorPolyfill::transliterate('NFD; [:Nonspacing Mark:] Remove', 'é');
        if (\class_exists('Normalizer', false)) {
            static::assertSame('e', $result);
        } else {
            static::assertSame('é', $result);
        }
    }

    public function testPolyfillClassExposesTransliterateMethod(): void
    {
        static::assertTrue(\method_exists(TransliteratorPolyfill::class, 'transliterate'));
    }
}
