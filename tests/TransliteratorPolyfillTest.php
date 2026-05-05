<?php

declare(strict_types=1);

namespace voku\tests;

use voku\helper\TransliteratorPolyfill;

/**
 * Tests for the transliterator_transliterate() polyfill.
 *
 * This polyfill supports a LIMITED subset of ICU transliterator IDs:
 * - NFC, NFD, NFKC, NFKD (normalization)
 * - [:Nonspacing Mark:] Remove
 * - Any-Latin, Latin-ASCII, Any-ASCII
 *
 * Upstream php-src / ICU compatibility cases live in
 * TransliteratorPolyfillUpstreamCompatibilityTest.
 *
 * This file stays focused on polyfill-specific behavior and additional
 * functional coverage outside the upstream-derived matrix.
 *
 * @internal
 */
final class TransliteratorPolyfillTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param mixed $transliterator
     *
     * Call TransliteratorPolyfill::transliterate() and capture any E_USER_WARNING.
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

    // ─── Happy-path transliteration tests ───────────────────────────────

    public function testLatinAsciiBasic(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'déjà vu');
        static::assertSame('deja vu', $result);
    }

    public function testLatinAsciiCafe(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'café');
        static::assertSame('cafe', $result);
    }

    public function testLatinAsciiNaive(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'naïve');
        static::assertSame('naive', $result);
    }

    public function testLatinAsciiResume(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'résumé');
        static::assertSame('resume', $result);
    }

    public function testAnyLatinLatinAsciiPipeline(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'déjà vu');
        static::assertSame('deja vu', $result);
    }

    public function testAnyAsciiDirect(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-ASCII', 'déjà vu');
        static::assertSame('deja vu', $result);
    }

    public function testFullPipeline(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII',
            'déjà vu'
        );
        static::assertSame('deja vu', $result);
    }

    public function testTrailingSemicolonInId(): void
    {
        // Trailing semicolons should be handled gracefully (empty steps filtered out)
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII;', 'café');
        static::assertSame('cafe', $result);
    }

    public function testCanonicalizesCompactNonspacingMarkSyntax(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'NFKC;[:NonspacingMark:]Remove;NFKC;Any-Latin;Latin-ASCII;',
            'déjà vu'
        );
        static::assertSame('deja vu', $result);
    }

    public function testMatchesStepsCaseInsensitively(): void
    {
        $result = TransliteratorPolyfill::transliterate('latin-ascii', 'café');
        static::assertSame('cafe', $result);
    }

    public function testAnyUpperThenAsciiPipeline(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Upper; Any-Latin; Latin-ASCII',
            'ŤÉŚŢ - öäü - 123 - abc - …'
        );
        static::assertSame('TEST - OAU - 123 - ABC - ...', $result);
    }

    public function testGermanAsciiRule(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'NFC; [:Nonspacing Mark:] Remove; NFC; Any-Lower; de-ascii',
            'ŤÉŚŢ - öäü - 123 - abc - …'
        );
        static::assertSame('test - oeaeue - 123 - abc - ...', $result);
    }

    public function testGermanAsciiRulePreservesUppercaseExpansions(): void
    {
        $result = TransliteratorPolyfill::transliterate('de-ASCII', 'Ä Ö Ü ä ö ü ß');
        static::assertSame('AE OE UE ae oe ue ss', $result);
    }

    public function testGermanAsciiRuleUsesTitlecaseExpansionsInsideWords(): void
    {
        $result = TransliteratorPolyfill::transliterate('de-ASCII', 'Äpfel Öl Übergröße');
        static::assertSame('Aepfel Oel Uebergroesse', $result);
    }

    public function testGermanAsciiRuleKeepsAllCapsExpansionsForUppercaseWords(): void
    {
        $result = TransliteratorPolyfill::transliterate('de-ASCII', 'ÄPFEL ÖL ÜBER');
        static::assertSame('AEPFEL OEL UEBER', $result);
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

    public function testTurkmenLatinBgnRule(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'Turkmen-Latin/BGN; Latin-ASCII',
            'ŤÉŚŢ - öäü - 123 - abc - …'
        );
        static::assertSame('TEST - oau - 123 - abc - ...', $result);
    }

    public function testRussianLatinBgnAlias(): void
    {
        $result = TransliteratorPolyfill::transliterate('Russian-Latin/BGN', 'Привет мир');
        static::assertSame('Privet mir', $result);
    }

    public function testUkrainianLatinBgnAlias(): void
    {
        $result = TransliteratorPolyfill::transliterate('Ukrainian-Latin/BGN', 'Привіт світ');
        static::assertSame('Pryvit svit', $result);
    }

    public function testArabicLatinAlias(): void
    {
        $result = TransliteratorPolyfill::transliterate('Arabic-Latin', 'أبجد');
        static::assertSame('abgd', $result);
    }

    // ─── Mixed-language / accented character tests ──────────────────────

    public function testFrenchAccents(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'Any-Latin; Latin-ASCII',
            'Un été brûlant sur la côte'
        );
        static::assertSame('Un ete brulant sur la cote', $result);
    }

    public function testSpanishCharacters(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'El niño');
        static::assertSame('El nino', $result);
    }

    public function testGreekText(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'Any-Latin; Latin-ASCII',
            'Αυτή είναι μια δοκιμή'
        );
        static::assertSame('Aute einai mia dokime', $result);
    }

    public function testCyrillicText(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'Any-Latin; Latin-ASCII',
            'биологическом'
        );
        // Divergence: native ICU produces "biologiceskom", our tables produce "biologicheskom"
        static::assertSame('biologicheskom', $result);
    }

    public function testArabicText(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'أبز');
        static::assertSame('abz', $result);
    }

    public function testKoreanText(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'Any-Latin; Latin-ASCII',
            '정, 병호'
        );
        static::assertSame('jeong, byeongho', $result);
    }

    public function testJapaneseText(): void
    {
        $result = TransliteratorPolyfill::transliterate(
            'Any-Latin; Latin-ASCII',
            'ますだ, よしひこ'
        );
        static::assertSame('masuda, yoshihiko', $result);
    }

    public function testGreekSingleWord(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'κόσμε');
        static::assertSame('kosme', $result);
    }

    // ─── Slicing behavior tests (start/end) ─────────────────────────────

    public function testStartParameterTransliteratesFromOffset(): void
    {
        // 'déjà vu résumé' codepoints: d(0) é(1) j(2) à(3) (4) v(5) u(6) (7) r(8) é(9) s(10) u(11) m(12) é(13)
        // Start at codepoint 8: transliterate 'résumé' → 'resume', keep 'déjà vu ' as-is
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'déjà vu résumé', 8);
        static::assertSame('déjà vu resume', $result);
    }

    public function testEndParameterTransliteratesUpToOffset(): void
    {
        // End at codepoint 4: transliterate 'déjà' → 'deja', keep ' vu résumé' as-is
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'déjà vu résumé', 0, 4);
        static::assertSame('deja vu résumé', $result);
    }

    public function testEndMinusOneMeansWholeString(): void
    {
        // End = -1 means "to end of string"
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'café', 0, -1);
        static::assertSame('cafe', $result);
    }

    public function testStartEqualsEndNoTransliteration(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'café', 2, 2);
        static::assertSame('café', $result);
    }

    public function testNegativeStartOffsetIsRejected(): void
    {
        self::assertInvalidOffsets(-1, -1);
    }

    public function testEndLessThanMinusOneIsRejected(): void
    {
        self::assertInvalidOffsets(0, -2);
    }

    // ─── Unsupported-ID tests ───────────────────────────────────────────

    public function testUnsupportedIdReturnsFalse(): void
    {
        $result = @TransliteratorPolyfill::transliterate('Katakana-Hiragana', 'テスト');
        static::assertFalse($result);
    }

    public function testUnsupportedIdTriggersWarning(): void
    {
        $captured = self::transliterateCapturingWarning('Katakana-Hiragana', 'テスト');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('Katakana-Hiragana', $captured['warning']);
    }

    public function testCustomIcuRulesReturnFalse(): void
    {
        $result = @TransliteratorPolyfill::transliterate('a > b; b > c;', 'abc');
        static::assertFalse($result);
    }

    public function testCustomIcuRulesTriggersWarning(): void
    {
        $captured = self::transliterateCapturingWarning('a > b; b > c;', 'abc');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('custom ICU rules', $captured['warning']);
    }

    public function testEmptyIdReturnsFalse(): void
    {
        $result = @TransliteratorPolyfill::transliterate('', 'test');
        static::assertFalse($result);
    }

    public function testEmptyIdTriggersWarning(): void
    {
        $captured = self::transliterateCapturingWarning('', 'test');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('empty transliterator ID', $captured['warning']);
    }

    public function testMixedSupportedAndUnsupportedStepsReturnsFalse(): void
    {
        // 'Latin-ASCII' is supported, but 'Katakana-Hiragana' is not
        $result = @TransliteratorPolyfill::transliterate('Latin-ASCII; Katakana-Hiragana', 'test');
        static::assertFalse($result);
    }

    public function testMixedSupportedAndUnsupportedStepsTriggerWarningForUnsupportedStep(): void
    {
        $captured = self::transliterateCapturingWarning('Latin-ASCII; Katakana-Hiragana', 'résumé');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('Katakana-Hiragana', $captured['warning']);
    }

    // ─── Invalid-input / edge-case tests ────────────────────────────────

    public function testEmptyStringReturnsEmpty(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', '');
        static::assertSame('', $result);
    }

    public function testAsciiOnlyInputPassesThrough(): void
    {
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', 'Hello World 123');
        static::assertSame('Hello World 123', $result);
    }

    public function testNonStringTransliteratorReturnsFalse(): void
    {
        $result = @TransliteratorPolyfill::transliterate(42, 'test');
        static::assertFalse($result);
    }

    public function testNonStringTransliteratorTriggersWarning(): void
    {
        $captured = self::transliterateCapturingWarning(42, 'test');

        static::assertFalse($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('integer', $captured['warning']);
    }

    public function testNullBytesInString(): void
    {
        // Divergence: native ext-intl preserves null bytes ("a\x00b\x00c"),
        // but the underlying ASCII::to_transliterate() strips them.
        $result = TransliteratorPolyfill::transliterate('Latin-ASCII', "a\x00b\x00c");
        static::assertSame('abc', $result);
    }

    public function testEmojiPreserved(): void
    {
        // Emoji can't be transliterated to ASCII; polyfill preserves them (unknown = null)
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', '😀');
        static::assertSame('😀', $result);
    }

    public function testMixedTextWithEmoji(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'café 😀');
        static::assertSame('cafe 😀', $result);
    }

    public function testOnlySemicolonsId(): void
    {
        $result = @TransliteratorPolyfill::transliterate(';;;', 'test');
        static::assertFalse($result);
    }

    // ─── Deterministic output tests ─────────────────────────────────────

    public function testDeterministicOutput(): void
    {
        $input = 'Ünited Stätes café résumé';
        $id = 'Any-Latin; Latin-ASCII';

        $first = TransliteratorPolyfill::transliterate($id, $input);
        $second = TransliteratorPolyfill::transliterate($id, $input);
        $third = TransliteratorPolyfill::transliterate($id, $input);

        static::assertSame($first, $second);
        static::assertSame($second, $third);
    }

    public function testDeterministicWithFullPipeline(): void
    {
        $input = 'Αυτή είναι δοκιμή 中文 биологическом';
        $id = 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII';

        $first = TransliteratorPolyfill::transliterate($id, $input);
        $second = TransliteratorPolyfill::transliterate($id, $input);

        static::assertSame($first, $second);
        static::assertNotFalse($first);
    }

    // ─── Normalization step tests ───────────────────────────────────────

    public function testNfkcStepAlone(): void
    {
        // NFKC normalizes compatibility characters
        // ﬁ (U+FB01, LATIN SMALL LIGATURE FI) → fi
        $result = TransliteratorPolyfill::transliterate('NFKC', "\xEF\xAC\x81"); // ﬁ
        if (\class_exists('Normalizer', false)) {
            static::assertSame('fi', $result);
        } else {
            // Without Normalizer, NFKC is a no-op, original char preserved
            static::assertSame("\xEF\xAC\x81", $result);
        }
    }

    public function testMarkRemovalStep(): void
    {
        // e + combining acute accent → e (after mark removal)
        // \xCC\x81 is U+0301 COMBINING ACUTE ACCENT
        $result = TransliteratorPolyfill::transliterate('[:Nonspacing Mark:] Remove', "e\xCC\x81");
        static::assertSame('e', $result);
    }

    public function testNfkdPlusMarkRemoval(): void
    {
        // é (precomposed) → NFD decomposes to e + combining accent → mark removal strips accent
        $result = TransliteratorPolyfill::transliterate('NFD; [:Nonspacing Mark:] Remove', 'é');
        if (\class_exists('Normalizer', false)) {
            static::assertSame('e', $result);
        } else {
            // Without Normalizer, NFD is a no-op, mark removal doesn't help with precomposed chars
            static::assertSame('é', $result);
        }
    }

    // ─── Class availability test ────────────────────────────────────────

    public function testPolyfillClassExposesTransliterateMethod(): void
    {
        static::assertTrue(\method_exists(TransliteratorPolyfill::class, 'transliterate'));
    }

    // ─── Data provider for batch transliteration checks ─────────────────

    public static function transliterationBatchCases(): array
    {
        return [
            'simple accents'  => ['Any-Latin; Latin-ASCII', 'àáâãäåèéêëìíîïòóôõöùúûüýñ', 'aaaaaaeeeeiiiiooooouuuuyn'],
            'german umlauts'  => ['Latin-ASCII', 'Ä Ö Ü ä ö ü ß', 'A O U a o u ss'],
            'polish chars'    => ['Latin-ASCII', 'ąćęłńóśźż', 'acelnoszz'],
            'czech chars'     => ['Latin-ASCII', 'čďěňřšťůž', 'cdenrstuz'],
            'romanian chars'  => ['Latin-ASCII', 'ăâîșț', 'aaist'],
            'ascii passthru'  => ['Latin-ASCII', 'Hello 123!', 'Hello 123!'],
            'empty string'    => ['Latin-ASCII', '', ''],
            'whitespace only' => ['Latin-ASCII', '   ', '   '],
        ];
    }

    public function testTransliterationBatch(): void
    {
        foreach (self::transliterationBatchCases() as $label => $case) {
            [$id, $input, $expected] = $case;

            $result = TransliteratorPolyfill::transliterate($id, $input);
            static::assertSame($expected, $result, $label);
        }
    }
}
