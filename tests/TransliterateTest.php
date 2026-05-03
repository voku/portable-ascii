<?php

declare(strict_types=1);

namespace voku\tests;

use voku\helper\ASCII;

/**
 * @internal
 */
final class TransliterateTest extends \PHPUnit\Framework\TestCase
{
    public function testUtf8()
    {
        $str = 'testiñg';
        static::assertSame('testing', ASCII::to_transliterate($str));
    }

    public function testAscii()
    {
        $str = 'testing';
        static::assertSame('testing', ASCII::to_transliterate($str));
    }

    public function testInvalidChar()
    {
        $str = "tes\xE9ting";
        static::assertSame('testing', ASCII::to_transliterate($str));
    }

    public function testMalformedUtf8SequencesAreDiscarded()
    {
        // Malformed UTF-8 is removed during cleaning, regardless of $unknown.
        $tests = [
            "\xC0\xAF"         => '',  // overlong 2-byte
            "\xE0\x80\xAF"     => '',  // overlong 3-byte
            "\xF0\x80\x80\xAF" => '',  // overlong 4-byte
            "\xF5\x80\x80\x80" => '',  // beyond-Unicode 4-byte
            "\xED\xA0\x80"     => '',  // surrogate
        ];

        foreach ($tests as $before => $after) {
            static::assertSame($after, ASCII::to_transliterate($before), 'first pass: ' . \bin2hex($before));
            // Re-run the same input to exercise the warm-cache path implicitly.
            static::assertSame($after, ASCII::to_transliterate($before), 'warm pass: ' . \bin2hex($before));
        }
    }

    public function testCleanRemovesOverlongTwoByteSequences()
    {
        // C0 and C1 are invalid UTF-8 starters (overlong encodings of ASCII chars).
        // clean() should strip them instead of treating them as valid 2-byte sequences.
        static::assertSame('ab', ASCII::clean("a\xC0\xAFb"));
        static::assertSame('ab', ASCII::clean("a\xC1\xBFb"));
        // C2 is a valid 2-byte starter and should be preserved
        static::assertSame("a\xC2\xA3b", ASCII::clean("a\xC2\xA3b")); // £ sign
    }

    public function testInvalidUtf8SequencesAreDiscardedRegardlessOfUnknown()
    {
        static::assertSame('', ASCII::to_transliterate("\xED\xA0\x80", '?', false));
        static::assertSame('', ASCII::to_transliterate("\xED\xA0\x80", 'X', false));
        static::assertSame('', ASCII::to_transliterate("\xE0\x80\xAF", '?', false));
        static::assertSame('', ASCII::to_transliterate("\xF0\x80\x80\xAF", '?', false));
        static::assertSame('', ASCII::to_transliterate("\xF4\x90\x80\x80", '?', false));
        static::assertSame('', ASCII::to_transliterate("\xC0\xAF", '?', false));
        static::assertSame('', ASCII::to_transliterate("\xC0\xAF", 'X', false));
        static::assertSame('', ASCII::to_transliterate("\xC1\xBF", '?', false));
        static::assertSame('', ASCII::to_transliterate("\xF5\x80\x80\x80", '?', false));
        static::assertSame('', ASCII::to_transliterate("\xF5\x80\x80\x80", 'X', false));
        static::assertSame('ab', ASCII::to_transliterate("a\xED\xA0\x80b", '?', false));
        static::assertSame('ab', ASCII::to_transliterate("a\xED\xA0\x80b", 'X', false));
        static::assertSame('ab', ASCII::to_transliterate("a\xC0\xAFb", '?', false));
        static::assertSame('ab', ASCII::to_transliterate("a\xC0\xAFb", 'X', false));
    }

    public function testLeadingMalformedUtf8IsDroppedWhileTrailingAsciiIsPreserved()
    {
        static::assertSame('x', ASCII::to_transliterate("\xC0\xAFx", '?', false));
        static::assertSame('x', ASCII::to_transliterate("\xC0\xAFx", 'X', false));
        static::assertSame('x', ASCII::to_transliterate("\xC0\xAFx", null, false));

        // Repeat the same input to cover the warm-path cache after the first pass.
        static::assertSame('x', ASCII::to_transliterate("\xC0\xAFx", '?', false));
    }

    public function testBasicControlCharactersArePreservedDuringTransliteration()
    {
        $input = "déjà\n\tvu\rStraße";

        static::assertSame("deja\n\tvu\rStrasse", ASCII::to_transliterate($input, '?', false));
        static::assertSame("deja\n\tvu\rStrasse", ASCII::to_transliterate($input, '?', false));
    }

    public function testNonBasicControlCharactersAreStillRemovedDuringTransliteration()
    {
        static::assertSame('dejavu', ASCII::to_transliterate("déjà\x01vu", '?', false));
        static::assertSame('dejavu', ASCII::to_transliterate("déjà\x0Bvu", '?', false));
        static::assertSame('dejavu', ASCII::to_transliterate("déjà\x7Fvu", '?', false));
    }

    public function testWarmUnknownFallbackDoesNotSkipControlByteCleanup()
    {
        static::assertSame('X', ASCII::to_transliterate("😀\x01", 'X', false));
        static::assertSame('X', ASCII::to_transliterate("😀\x01", 'X', false));

        static::assertSame("X\n", ASCII::to_transliterate("😀\n", 'X', false));
        static::assertSame("X\n", ASCII::to_transliterate("😀\n", 'X', false));
    }

    public function testCleanupSkippedForCustomUnknownWithoutMarkers()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('pre_clean_transliteration_input');
        $method->setAccessible(true);

        static::assertSame('déjà', $method->invoke(null, 'déjà', 'X'));
        static::assertSame('x y', $method->invoke(null, "x\xC2\xA0y", 'X'));
        static::assertSame("x'y", $method->invoke(null, "x\xE2\x80\x99y", 'X'));
        static::assertSame('xy', $method->invoke(null, "x\x01y", 'X'));
    }

    public function testCleanupAlwaysRunsForQuestionMarkUnknown()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('pre_clean_transliteration_input');
        $method->setAccessible(true);

        static::assertSame('x y', $method->invoke(null, "x\xC2\xA0y", '?'));
        static::assertSame('xy', $method->invoke(null, "x\x01y", '?'));
    }

    public function testTransliterateShortcutBranchesStayCorrectAcrossRepeatedCalls()
    {
        $cases = [
            'long printable ASCII fast path' => [
                'arguments' => [\str_repeat('Plain ASCII text 123 test ', 4), '?', false],
                'expected' => \str_repeat('Plain ASCII text 123 test ', 4),
            ],
            'basic controls remain preserved' => [
                'arguments' => ["a\n\tb\rc", '?', false],
                'expected' => "a\n\tb\rc",
            ],
            'warm unknown fallback cache for unmapped characters' => [
                'arguments' => ['😀', 'X', false],
                'expected' => 'X',
            ],
        ];

        foreach ($cases as $label => $scenario) {
            for ($pass = 1; $pass <= 2; ++$pass) {
                static::assertSame(
                    $scenario['expected'],
                    \call_user_func_array([ASCII::class, 'to_transliterate'], $scenario['arguments']),
                    $label . ' pass ' . $pass
                );
            }
        }
    }

    public function testTransliteratePrintableAsciiBoundaryAndFourByteCodepoints()
    {
        static::assertSame(\str_repeat('A', 64), ASCII::to_transliterate(\str_repeat('A', 64), '?', false));
        static::assertSame('A', ASCII::to_transliterate("\xF0\x9D\x90\x80", '?', false));
        static::assertSame('nn', ASCII::to_transliterate('ññ', '?', false));
    }

    public function testUnknownWithPregSpecialCharsIsLiteral()
    {
        static::assertSame('$0', ASCII::to_transliterate('😀', '$0', false));
        static::assertSame('\\1', ASCII::to_transliterate('😀', '\\1', false));
        static::assertSame('${1}', ASCII::to_transliterate('😀', '${1}', false));
    }

    public function testInvalidUtf8SequencesAreDiscardedWhenUnknownNull()
    {
        static::assertSame('', ASCII::to_transliterate("\xED\xA0\x80", null, false));
        static::assertSame('ab', ASCII::to_transliterate("a\xE0\x80\xAFb", null, false));
    }

    public function testUnknownFallbackWarmCacheRespectsUnknown()
    {
        static::assertSame('?', ASCII::to_transliterate('😀', '?', false));
        static::assertSame('X', ASCII::to_transliterate('😀', 'X', false));
        static::assertSame('😀', ASCII::to_transliterate('😀', null, false));
        static::assertSame('?', ASCII::to_transliterate('😀', '?', false));
    }

    public function testUnknownFallbackWarmCacheStoresUnmappedCharacters()
    {
        $input = \str_repeat('😀🚀', 32);

        static::assertSame(\str_repeat('????', 32), ASCII::to_transliterate($input, '??', false));
        static::assertSame(\str_repeat('ZZZZ', 32), ASCII::to_transliterate($input, 'ZZ', false));
        static::assertSame(\str_repeat('????', 32), ASCII::to_transliterate($input, '??', false));
    }

    public function testRepeatedNonAsciiInputStaysCorrect()
    {
        $input = \str_repeat('中文😀', 64);
        $withMalformedUtf8 = ASCII::to_transliterate(\str_repeat("中文\xED\xA0\x80", 64), '?', false);

        static::assertSame(\str_repeat('Zhong Wen ?', 64), ASCII::to_transliterate($input, '?', false));
        static::assertSame(\str_repeat('Zhong Wen ', 64), $withMalformedUtf8);
        static::assertStringNotContainsString('?', $withMalformedUtf8);
    }

    public function testNullUnknownAndNullByteUnknownUseDifferentWarmMaps()
    {
        static::assertSame('😀', ASCII::to_transliterate('😀', null, false));
        static::assertSame("\x00", ASCII::to_transliterate('😀', "\x00", false));
        static::assertSame('😀', ASCII::to_transliterate('😀', null, false));
    }

    public function testEmptyStr()
    {
        $str = '';
        static::assertEmpty(ASCII::to_transliterate($str));
    }

    public function testNulAndNon7Bit()
    {
        $str = "a\x00ñ\x00c";
        static::assertSame('anc', ASCII::to_transliterate($str));
    }

    public function testNul()
    {
        $str = "a\x00b\x00c";
        static::assertSame('abc', ASCII::to_transliterate($str));
    }

    public function testToTransliterate()
    {
        $testsStrict = [];
        if (\extension_loaded('intl') === true) {
            // ---

            $testString = \file_get_contents(__DIR__ . '/fixtures/sample-unicode-chart.txt');
            $resultString = \file_get_contents(__DIR__ . '/fixtures/sample-ascii-chart.txt');

            static::assertSame($resultString, ASCII::to_transliterate($testString, '?', true));

            // ---

            $testsStrict = [
                ' '                                        => ' ',
                ''                                         => '',
                'أبز'                                      => 'abz',
                "\xe2\x80\x99"                             => '\'',
                'Ɓtest'                                    => 'Btest',
                '  -ABC-中文空白-  '                           => '  -ABC-zhong wen kong bai-  ',
                "      - abc- \xc2\x87"                    => '      - abc- ++',
                'abc'                                      => 'abc',
                'deja vu'                                  => 'deja vu',
                'déjà vu'                                  => 'deja vu',
                'déjà σσς iıii'                            => 'deja sss iiii',
                "test\x80-\xBFöäü"                         => 'test-oau',
                'Internationalizaetion'                    => 'Internationalizaetion',
                "中 - &#20013; - %&? - \xc2\x80"            => 'zhong - &#20013; - %&? - EUR',
                'Un été brûlant sur la côte'               => 'Un ete brulant sur la cote',
                'Αυτή είναι μια δοκιμή'                    => 'Aute einai mia dokime',
                'أحبك'                                     => 'ahbk',
                'キャンパス'                                    => 'kyanpasu',
                'биологическом'                            => 'biologiceskom',
                '정, 병호'                                    => 'jeong, byeongho',
                'ますだ, よしひこ'                                => 'masuda, yoshihiko',
                'मोनिच'                                    => 'monica',
                'क्षȸ'                                     => 'kasadb',
                'أحبك 😀'                                   => 'ahbk ?',
                'ذرزسشصضطظعغػؼؽؾؿ 5.99€'                   => 'dhrzsshsdtz\'gh????? 5.99EUR',
                'ذرزسشصضطظعغػؼؽؾؿ £5.99'                   => 'dhrzsshsdtz\'gh????? PS5.99',
                '׆אבגדהוזחטיךכלםמן $5.99'                  => 'n\'bgdhwzhtykklmmn $5.99',
                '日一国会人年大十二本中長出三同 ¥5990'                    => 'ri yi guo hui ren nian da shi er ben zhong zhang chu san tong Y=5990',
                '5.99€ 日一国会人年大十 $5.99'                     => '5.99EUR ri yi guo hui ren nian da shi $5.99',
                'בגדה@ضطظعغػ.com'                          => 'bgdh@dtz\'gh?.com',
                '年大十@ضطظعغػ'                               => 'nian da shi@dtz\'gh?',
                'בגדה & 年大十'                               => 'bgdh & nian da shi',
                '国&ם at ضطظعغػ.הוז'                        => 'guo&m at dtz\'gh?.hwz',
                'my username is @בגדה'                     => 'my username is @bgdh',
                'The review gave 5* to ظعغػ'               => 'The review gave 5* to z\'gh?',
                'use 年大十@ضطظعغػ.הוז to get a 10% discount' => 'use nian da shi@dtz\'gh?.hwz to get a 10% discount',
                '日 = הط^2'                                 => 'ri = ht^2',
                'ךכלם 国会 غػؼؽ 9.81 m/s2'                   => 'kklm guo hui gh??? 9.81 m/s2',
                'The #会 comment at @בגדה = 10% of *&*'     => 'The #hui comment at @bgdh = 10% of *&*',
                '∀ i ∈ ℕ'                                  => '? i ? N',
                '👍 💩 😄 ❤ 👍 💩 😄 ❤أحبك'                      => '? ? ?  ? ? ? ahbk',
                'আমার সোনার বাংলা'                         => 'amara sonara banla',
                // Valid ASCII + Invalid Chars
                "a\xa0\xa1-öäü" => 'a-oau',
                // Valid 2 Octet Sequence
                "\xc3\xb1" => 'n', // ñ
                // Invalid 2 Octet Sequence
                "\xc3\x28" => '(',
                // Invalid
                "\x00"   => '',
                "a\xDFb" => 'ab',
                // Invalid Sequence Identifier
                "\xa0\xa1" => '',
                // Valid 3 Octet Sequence
                "\xe2\x82\xa1" => 'CL',
                // Invalid 3 Octet Sequence (in 2nd Octet)
                "\xe2\x28\xa1" => '(',
                // Invalid 3 Octet Sequence (in 3rd Octet)
                "\xe2\x82\x28" => '(',
                // Valid 4 Octet Sequence
                "\xf0\x90\x8c\xbc" => '?',
                // Invalid 4 Octet Sequence (in 2nd Invalid 4 Octet Sequence (in 2ndOctet)
                "\xf0\x28\x8c\xbc" => '(',
                // Invalid 4 Octet Sequence (in 3rd Octet)
                "\xf0\x90\x28\xbc" => '(',
                // Invalid 4 Octet Sequence (in 4th Octet)
                "\xf0\x28\x8c\x28" => '((',
                // Valid 5 Octet Sequence (but not Unicode!)
                "\xf8\xa1\xa1\xa1\xa1" => '',
                // Valid 6 Octet Sequence (but not Unicode!)
                "\xfc\xa1\xa1\xa1\xa1\xa1" => '',
                // Valid 6 Octet Sequence (but not Unicode!) + UTF-8 EN SPACE
                "\xfc\xa1\xa1\xa1\xa1\xa1\xe2\x80\x82" => ' ',
            ];
        }

        $tests = [
            // Valid defaults
            ''                      => '',
            ' '                     => ' ',
            null                    => '',
            '1a'                    => '1a',
            '2a'                    => '2a',
            '+1'                    => '+1',
            "      - abc- \xc2\x87" => '      - abc- ++',
            'abc'                   => 'abc',
            // Valid UTF-8
            'أبز'                           => 'abz',
            "\xe2\x80\x99"                  => '\'',
            'Ɓtest'                         => 'Btest',
            '  -ABC-中文空白-  '                => '  -ABC-Zhong Wen Kong Bai -  ',
            'deja vu'                       => 'deja vu',
            'déjà vu '                      => 'deja vu ',
            'déjà σσς iıii'                 => 'deja sss iiii',
            "test\x80-\xBFöäü"              => 'test-oau',
            'Internationalizaetion'         => 'Internationalizaetion',
            "中 - &#20013; - %&? - \xc2\x80" => 'Zhong  - &#20013; - %&? - EUR',
            'Un été brûlant sur la côte'    => 'Un ete brulant sur la cote',
            'Αυτή είναι μια δοκιμή'         => 'Aute einai mia dokime',
            'أحبك'                          => 'aHbk',
            'キャンパス'                         => 'kiyanpasu',
            'биологическом'                 => 'biologicheskom',
            '정, 병호'                         => 'jeong, byeongho',
            'ますだ, よしひこ'                     => 'masuda, yoshihiko',
            'मोनिच'                         => 'monic',
            'क्षȸ'                          => 'kssdb',
            'أحبك 😀'                        => 'aHbk ?',
            '∀ i ∈ ℕ'                       => '? i ? N',
            '👍 💩 😄 ❤ 👍 💩 😄 ❤أحبك'           => '? ? ?  ? ? ? aHbk',
            '纳达尔绝境下大反击拒绝冷门逆转晋级中网四强'         => 'Na Da Er Jue Jing Xia Da Fan Ji Ju Jue Leng Men Ni Zhuan Jin Ji Zhong Wang Si Qiang ',
            'κόσμε'                         => 'kosme',
            '中'                             => 'Zhong ',
            '«foobar»'                      => '<<foobar>>',
            'বাংলা'                         => 'baaNlaa',
            // Valid UTF-8 + UTF-8 NO-BREAK SPACE
            "κόσμε\xc2\xa0" => 'kosme ',
            // Valid UTF-8 + Invalid Chars
            "κόσμε\xa0\xa1-öäü" => 'kosme-oau',
            // Valid UTF-8 + ISO-Errors
            'DÃ¼sseldorf' => 'DA1/4sseldorf',
            // Valid invisible char
            '<x%0Conxxx=1' => '<x%0Conxxx=1',
            // Valid ASCII
            'a' => 'a',
            // Valid emoji (non-UTF-8)
            '😃'                                                          => '?',
            '🐵 🙈 🙉 🙊 | ❤️ 💔 💌 💕 💞 💓 💗 💖 💘 💝 💟 💜 💛 💚 💙 | 🚾 🆒 🆓 🆕 🆖 🆗 🆙 🏧' => '? ? ? ? | ? ? ? ? ? ? ? ? ? ? ? ? ? ? ? | ? ? ? ? ? ? ? ?',
            // Valid ASCII + Invalid Chars
            "a\xa0\xa1-öäü" => 'a-oau',
            // Valid 2 Octet Sequence
            "\xc3\xb1" => 'n', // ñ
            // Invalid 2 Octet Sequence
            "\xc3\x28" => '(',
            // Invalid
            "\x00"   => '',
            "a\xDFb" => 'ab',
            // Invalid Sequence Identifier
            "\xa0\xa1" => '',
            // Valid 3 Octet Sequence
            "\xe2\x82\xa1" => 'CL',
            // Invalid 3 Octet Sequence (in 2nd Octet)
            "\xe2\x28\xa1" => '(',
            // Invalid 3 Octet Sequence (in 3rd Octet)
            "\xe2\x82\x28" => '(',
            // Valid 4 Octet Sequence
            "\xf0\x90\x8c\xbc" => '?',
            // Invalid 4 Octet Sequence (in 2nd Invalid 4 Octet Sequence (in 2ndOctet)
            "\xf0\x28\x8c\xbc" => '(',
            // Invalid 4 Octet Sequence (in 3rd Octet)
            "\xf0\x90\x28\xbc" => '(',
            // Invalid 4 Octet Sequence (in 4th Octet)
            "\xf0\x28\x8c\x28" => '((',
            // Valid 5 Octet Sequence (but not Unicode!)
            "\xf8\xa1\xa1\xa1\xa1" => '',
            // Valid 6 Octet Sequence (but not Unicode!)
            "\xfc\xa1\xa1\xa1\xa1\xa1" => '',
            // Valid 6 Octet Sequence (but not Unicode!) + UTF-8 EN SPACE
            "\xfc\xa1\xa1\xa1\xa1\xa1\xe2\x80\x82" => ' ',
        ];

        for ($i = 0; $i <= 2; ++$i) { // keep this loop for simple performance tests
            foreach ($tests as $before => $after) {
                static::assertSame($after, ASCII::to_transliterate($before, '?', false), 'tested: ' . $before);
            }
        }

        for ($i = 0; $i <= 2; ++$i) { // keep this loop for simple performance tests
            foreach ($testsStrict as $before => $after) {
                static::assertSame($after, ASCII::to_transliterate($before, '?', true), 'tested: ' . $before);
            }
        }
    }

    public function testCurrency()
    {
        $tests = [
            '€' => 'EUR',
            '$' => '$',
            '₢' => 'Cr',
            '₣' => 'Fr.',
            '£' => 'PS',
            '₤' => 'L.',
            '₶' => 'L',
            'ℳ' => 'M',
            '₥' => 'mil',
            '₦' => 'N',
            '₧' => 'Pts',
            '₨' => 'Rs',
            '௹' => '?',
            '₩' => 'W',
            '₪' => 'NS',
            '₸' => 'T',
            '₫' => 'D',
            '֏' => '?',
            '₭' => 'K',
            '₼' => 'm',
            '₮' => 'T',
            '₯' => 'Dr',
            '₰' => 'Pf',
            '₷' => 'Sm',
            '₱' => 'P',
            'ރ' => 'r',
            '₲' => 'G',
            '₾' => 'l',
            '₳' => 'A',
            '₴' => 'UAH',
            '₽' => 'R',
            '₵' => 'C|',
            '₡' => 'CL',
            '¢' => 'C/',
            '¥' => 'Y=',
            '៛' => 'KR',
            '¤' => '$?',
            '฿' => 'Bh.',
            '؋' => '?',
        ];

        foreach ($tests as $before => $after) {
            static::assertSame($after, ASCII::to_transliterate($before, '?', true), 'tested: ' . $before);
            static::assertSame($after, ASCII::to_transliterate($before, '?', false), 'tested: ' . $before);
        }
    }

    public function testKeepInvalidCharsStrict()
    {
        if (\strtoupper(\substr(\PHP_OS, 0, 3)) === 'WIN') {
            static::markTestSkipped('TODO? -> not working on Windows???');
        }

        static::assertSame('ahbk 😀 ♥ 𐎁 𠾴 ᎈ y', \strtolower(ASCII::to_transliterate('أحبك 😀 ♥ 𐎁 𠾴 ᎈ ý', null, true)));
    }

    public function testKeepInvalidChars()
    {
        if (\strtoupper(\substr(\PHP_OS, 0, 3)) === 'WIN') {
            static::markTestSkipped('TODO? -> not working on Windows???');
        }

        static::assertSame('ahbk 😀 ♥ 𐎁 𠾴 ᎈ y', \strtolower(ASCII::to_transliterate('أحبك 😀 ♥ 𐎁 𠾴 ᎈ ý', null, false)));
    }

    public static function specialCharacterProvider(): array
    {
        return [
            ['ⓐⓑⓒⓓⓔⓕⓖⓗⓘⓙⓚⓛⓜⓝⓞⓟⓠⓡⓢⓣⓤⓥⓦⓧⓨⓩ', 'abcdefghijklmnopqrstuvwxyz'],
            ['⓪①②③④⑤⑥⑦⑧⑨⑩⑪⑫⑬⑭⑮⑯⑰⑱⑲⑳', '01234567891011121314151617181920'],
            ['⓵⓶⓷⓸⓹⓺⓻⓼⓽⓾', '12345678910'],
            ['⓿⓫⓬⓭⓮⓯⓰⓱⓲⓳⓴', '011121314151617181920'],
            ['abcdefghijklmnopqrstuvwxyz', 'abcdefghijklmnopqrstuvwxyz'],
            ['0123456789', '0123456789'],
        ];
    }

    /**
     * @dataProvider specialCharacterProvider
     */
    public function it_can_replace_special_characters(string $value, string $expected)
    {
        static::assertSame($expected, ASCII::to_transliterate($value));
    }
}
