<?php

declare(strict_types=1);

namespace voku\tests;

use voku\helper\ASCII;

/**
 * @internal
 */
final class AsciiTest extends \PHPUnit\Framework\TestCase
{
    public function testToAsciiRemap()
    {
        static::assertSame(['testi' . \chr(128) . 'g', 'testing'], ASCII::to_ascii_remap('testiñg', 'testing'));
    }

    public function testUtf8()
    {
        $str = 'testiñg';
        static::assertFalse(ASCII::is_ascii($str));
    }

    public function testAscii()
    {
        $str = 'testing';
        static::assertTrue(ASCII::is_ascii($str));
    }

    public function testInvalidChar()
    {
        $str = "tes\xe9ting";
        static::assertFalse(ASCII::is_ascii($str));
    }

    public function testEmptyStr()
    {
        $str = '';
        static::assertTrue(ASCII::is_ascii($str));
    }

    public function testNewLine()
    {
        $str = "a\nb\nc";
        static::assertTrue(ASCII::is_ascii($str));
    }

    public function testTab()
    {
        $str = "a\tb\tc";
        static::assertTrue(ASCII::is_ascii($str));
    }

    public function testUtf8ToAscii()
    {
        $str = 'testiñg';
        static::assertSame('testing', ASCII::to_ascii($str));
    }

    public function testAsciiToAscii()
    {
        $str = 'testing';
        static::assertSame('testing', ASCII::to_ascii($str));
    }

    public function testToAsciiEmptyLanguage()
    {
        $testsStrict = [
            ' '                                        => ' ',
            ''                                         => '',
            'أبز'                                      => 'abz',
            "\xe2\x80\x99"                             => '\'',
            'Ɓtest'                                    => 'Btest',
            '  -ABC-中文空白-  '                           => '  -ABC-Zhong Wen Kong Bai -  ',
            "      - abc- \xc2\x87"                    => '      - abc- ++',
            'STRAẞE'                                   => 'STRASSE',
            'abc'                                      => 'abc',
            'deja vu'                                  => 'deja vu',
            'déjà vu'                                  => 'deja vu',
            'déjà σσς iıii'                            => 'deja sss iiii',
            "test\x80-\xBFöäü"                         => 'test-oau',
            'Internationalizaetion'                    => 'Internationalizaetion',
            "中 - &#20013; - %&? - \xc2\x80"            => 'Zhong  - &#20013; - %&? - EUR',
            'Un été brûlant sur la côte'               => 'Un ete brulant sur la cote',
            'Αυτή είναι μια δοκιμή'                    => 'Auti inai mia dokimi',
            'أحبك'                                     => 'ahbk',
            'キャンパス'                                    => 'kiyanpasu',
            'биологическом'                            => 'biologiceskom',
            '정, 병호'                                    => 'jeong, byeongho',
            'ますだ, よしひこ'                                => 'masuda, yoshihiko',
            'मोनिच'                                    => 'MaoNaiCa',
            'क्षȸ'                                     => 'KaShhadb',
            'أحبك 😀'                                   => 'ahbk ',
            'ذرزسشصضطظعغػؼؽؾؿ 5.99€'                   => 'thrzsshsdtthaagh 5.99EUR',
            'ذرزسشصضطظعغػؼؽؾؿ £5.99'                   => 'thrzsshsdtthaagh PS5.99',
            '׆אבגדהוזחטיךכלםמן $5.99'                  => 'nAbgdhvzKHtykklmmn $5.99',
            '日一国会人年大十二本中長出三同 ¥5990'                    => 'Ri Yi Guo Hui Ren Nian Da Shi Er Ben Zhong Chang Chu San Tong  YEN5990',
            '5.99€ 日一国会人年大十 $5.99'                     => '5.99EUR Ri Yi Guo Hui Ren Nian Da Shi  $5.99',
            'בגדה@ضطظعغػ.com'                          => 'bgdh@dtthaagh.com',
            '年大十@ضطظعغػ'                               => 'Nian Da Shi @dtthaagh',
            'בגדה & 年大十'                               => 'bgdh & Nian Da Shi ',
            '国&ם at ضطظعغػ.הוז'                        => 'Guo &m at dtthaagh.hvz',
            'my username is @בגדה'                     => 'my username is @bgdh',
            'The review gave 5* to ظعغػ'               => 'The review gave 5* to thaagh',
            'use 年大十@ضطظعغػ.הוז to get a 10% discount' => 'use Nian Da Shi @dtthaagh.hvz to get a 10% discount',
            '日 = הط^2'                                 => 'Ri  = ht^2',
            'ךכלם 国会 غػؼؽ 9.81 m/s2'                   => 'kklm Guo Hui  gh 9.81 m/s2',
            'The #会 comment at @בגדה = 10% of *&*'     => 'The #Hui  comment at @bgdh = 10% of *&*',
            '∀ i ∈ ℕ'                                  => ' i  N',
            '👍 💩 😄 ❤ 👍 💩 😄 ❤أحبك'                      => '       ahbk',
            'আমি'                                      => 'ami',
        ];

        foreach ($testsStrict as $before => $after) {
            static::assertSame($after, ASCII::to_ascii($before, ''), 'tested: ' . $before);
        }
    }

    public function testToAscii()
    {
        $testsStrict = [
            ' '                                        => ' ',
            ''                                         => '',
            'أبز'                                      => 'abz',
            "\xe2\x80\x99"                             => '\'',
            'Ɓtest'                                    => 'Btest',
            '  -ABC-中文空白-  '                           => '  -ABC--  ',
            "      - abc- \xc2\x87"                    => '      - abc- ',
            'STRAẞE'                                   => 'STRASSE',
            'abc'                                      => 'abc',
            'deja vu'                                  => 'deja vu',
            'déjà vu'                                  => 'deja vu',
            'déjà σσς iıii'                            => 'deja sss iiii',
            "test\x80-\xBFöäü"                         => 'test-',
            'Internationalizaetion'                    => 'Internationalizaetion',
            "中 - &#20013; - %&? - \xc2\x80"            => ' - &#20013; - %&? - ',
            'Un été brûlant sur la côte'               => 'Un ete brulant sur la cote',
            'Αυτή είναι μια δοκιμή'                    => 'Auti inai mia dokimi',
            'أحبك'                                     => 'ahbk',
            'キャンパス'                                    => '',
            'биологическом'                            => 'biologiceskom',
            '정, 병호'                                    => ', ',
            'ますだ, よしひこ'                                => ', ',
            'मोनिच'                                    => 'MaNaCa',
            'क्षȸ'                                     => 'KaShhadb',
            'أحبك 😀'                                   => 'ahbk ',
            'ذرزسشصضطظعغػؼؽؾؿ 5.99€'                   => 'thrzsshsdtthaagh 5.99EUR',
            'ذرزسشصضطظعغػؼؽؾؿ £5.99'                   => 'thrzsshsdtthaagh PS5.99',
            '׆אבגדהוזחטיךכלםמן $5.99'                  => ' $5.99',
            '日一国会人年大十二本中長出三同 ¥5990'                    => ' YEN5990',
            '5.99€ 日一国会人年大十 $5.99'                     => '5.99EUR  $5.99',
            'בגדה@ضطظعغػ.com'                          => '@dtthaagh.com',
            '年大十@ضطظعغػ'                               => '@dtthaagh',
            'בגדה & 年大十'                               => ' & ',
            '国&ם at ضطظعغػ.הוז'                        => '& at dtthaagh.',
            'my username is @בגדה'                     => 'my username is @',
            'The review gave 5* to ظعغػ'               => 'The review gave 5* to thaagh',
            'use 年大十@ضطظعغػ.הוז to get a 10% discount' => 'use @dtthaagh. to get a 10% discount',
            '日 = הط^2'                                 => ' = t^2',
            'ךכלם 国会 غػؼؽ 9.81 m/s2'                   => '  gh 9.81 m/s2',
            'The #会 comment at @בגדה = 10% of *&*'     => 'The # comment at @ = 10% of *&*',
            '∀ i ∈ ℕ'                                  => ' i  ',
            '👍 💩 😄 ❤ 👍 💩 😄 ❤أحبك'                      => '       ahbk',
            'আমি   '                                   => 'ami   ',
        ];

        foreach ($testsStrict as $before => $after) {
            static::assertSame($after, ASCII::to_ascii($before, 'en', true), 'tested: ' . $before);
        }
    }

    public function testRemoveInvisibleCharacters()
    {
        $testArray = [
            "κόσ\0με"                                                                          => 'κόσμε',
            "Κόσμε\x20"                                                                        => 'Κόσμε ',
            "öäü-κόσμ\x0εκόσμε-äöü"                                                            => 'öäü-κόσμεκόσμε-äöü',
            'öäü-κόσμεκόσμε-äöüöäü-κόσμεκόσμε-äöü'                                             => 'öäü-κόσμεκόσμε-äöüöäü-κόσμεκόσμε-äöü',
            'äöüäöüäöü-κόσμεκόσμεäöüäöüäöü-Κόσμεκόσμεäöüäöüäöü-κόσμεκόσμεäöüäöüäöü-κόσμεκόσμε' => 'äöüäöüäöü-κόσμεκόσμεäöüäöüäöü-Κόσμεκόσμεäöüäöüäöü-κόσμεκόσμεäöüäöüäöü-κόσμεκόσμε',
            '  '                                                                               => '  ',
            ''                                                                                 => '',
        ];

        foreach ($testArray as $before => $after) {
            static::assertSame($after, ASCII::remove_invisible_characters($before), 'error by ' . $before);
            static::assertSame($after, ASCII::remove_invisible_characters($before, true, '', true), 'error by ' . $before);
            static::assertSame($after, ASCII::remove_invisible_characters($before, false, '', false), 'error by ' . $before);
        }

        static::assertSame('äöüäöüäöü-κόσμεκόσμεäöüäöüäöü κόσμεκόσμεäöüäöüäöü-κόσμεκόσμε', ASCII::remove_invisible_characters("äöüäöüäöü-κόσμεκόσμεäöüäöüäöü\xe1\x9a\x80κόσμεκόσμεäöüäöüäöü-κόσμεκόσμε"));

        static::assertSame('%*ł€! ‎| | ', ASCII::remove_invisible_characters('%*ł€! ‎| | '));
        static::assertSame('%*ł€! |' . "\n|\t " . "\t", ASCII::remove_invisible_characters('%*ł€! ‎| | ' . "\t", false, '', false));

        static::assertSame('κόσ?με 	%00 | tes%20öäü%20\u00edtest', ASCII::remove_invisible_characters("κόσ\0με 	%00 | tes%20öäü%20\u00edtest", false, '?'));
        static::assertSame('κόσμε 	 | tes%20öäü%20\u00edtest', ASCII::remove_invisible_characters("κόσ\0με 	%00 | tes%20öäü%20\u00edtest", true, ''));
    }

    public function testGetSupportedLanguages()
    {
        $languages = ASCII::getAllLanguages();

        static::assertArrayHasKey('german', $languages, \print_r($languages, true));
        static::assertSame('de', $languages['german']);

        $languages = ASCII::getAllLanguages();
        static::assertSame('de', $languages['german']);
    }

    public function testInvalidCharToAscii()
    {
        $str = "tes\xe9ting";
        static::assertSame('testing', ASCII::to_transliterate($str));

        // ---

        $str = "tes\xe9ting";
        static::assertSame('testing', ASCII::to_ascii($str));
    }

    public function testMalformedUtf8ToAsciiViaTransliteration()
    {
        static::assertSame('', ASCII::to_ascii("\xC0\xAF", 'en', true, false, true));
        static::assertSame('', ASCII::to_ascii("\xED\xA0\x80", 'en', true, false, true));
    }

    public function testEmptyStrToAscii()
    {
        $str = '';
        static::assertSame('', ASCII::to_ascii($str));
    }

    public function testNulAndNon7Bit()
    {
        $str = "a\x00ñ\x00c";
        static::assertSame('anc', ASCII::to_ascii($str));
    }

    public function testNul()
    {
        $str = "a\x00b\x00c";
        static::assertSame('abc', ASCII::to_ascii($str));
    }

    public function testNewLineToAscii()
    {
        $str = "a\nb\nc";
        static::assertSame("a\nb\nc", ASCII::to_transliterate($str));

        // ---

        $str = "\xc2\x92\x00\n\x01\n\x7f\xe2\x80\x99";
        static::assertSame("'\n\n'", ASCII::to_transliterate($str));

        // ---

        $str = "a\nb\nc";
        static::assertSame("a\nb\nc", ASCII::to_ascii($str, 'en', false));

        // ---

        $str = "a\nb\nc";
        static::assertSame('a b c', ASCII::to_ascii($str, 'en', true));

        // ---

        $str = 'ä-ö-ü';
        static::assertSame('ae-oe-ue', ASCII::to_ascii($str, 'de', true));
    }

    public function testTabToAscii()
    {
        $str = "a\tb\tc";
        static::assertSame("a\tb\tc", ASCII::to_transliterate($str));

        // ---

        $str = "a\tb\tc";
        static::assertSame("a\tb\tc", ASCII::to_ascii($str, 'en', false));

        // ---

        $str = "a\tb\tc";
        static::assertSame('a b c', ASCII::to_ascii($str, 'en', true));
    }

    public function testToAsciiSingleCharOnlyUsesExpectedMappings()
    {
        static::assertSame('Aiti einai mia dokimi ', ASCII::to_ascii('Αυτή είναι μια δοκιμή ', 'el', true, false, false, true));
        static::assertSame('ttyaniungyath ', ASCII::to_ascii('တတျနိုငျသ ', 'my', true, false, false, true));
    }
}
