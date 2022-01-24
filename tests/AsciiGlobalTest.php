<?php

declare(strict_types=1);

namespace voku\tests;

use voku\helper\ASCII;

/**
 * @internal
 */
final class AsciiGlobalTest extends \PHPUnit\Framework\TestCase
{
    public function slugifyProvider(): array
    {
        return [
            ['', ''],
            ['', ' '],
            ['bar', 'foooooo'], // "foooooo" will be replaced in the method call
            ['foo-bar', ' foo  bar '],
            ['foo-bar', 'foo -.-"-...bar'],
            ['another-and-foo-bar', 'another..& foo -.-"-...bar'],
            ['foo-dbar', " Foo d'Bar "],
            ['a-string-with-dashes', 'A string-with-dashes'],
            ['user-at-host', 'user@host'],
            ['using-strings-like-foo-bar', 'Using strings like fòô bàř'],
            ['numbers-1234', 'numbers 1234'],
            ['perevirka-riadka', 'перевірка рядка'],
            ['bukvar-s-bukvoi-y', 'букварь с буквой ы'],
            ['podieexal-k-podieezdu-moego-doma', 'подъехал к подъезду моего дома'],
            ['foo:bar:baz', 'Foo bar baz', ':'],
            ['a_string_with_underscores', 'A_string with_underscores', '_'],
            ['a_string_with_dashes', 'A string-with-dashes', '_'],
            ['one_euro_or_a_dollar', 'one € or a $', '_'],
            ['sometext', 'some text', ''],
            ['a\string\with\dashes', 'A string-with-dashes', '\\'],
            ['an_odd_string', '--   An odd__   string-_', '_'],
            ['Stoynostta-tryabva-da-bade-lazha', 'Стойността трябва да бъде лъжа', '-', 'bg', false],
            ['Dieser-Wert-sollte-groesser-oder-gleich', 'Dieser Wert sollte größer oder gleich', '-', 'de', false],
            ['Dieser-Wert-sollte-groeszer-oder-gleich', 'Dieser Wert sollte größer oder gleich', '-', 'de_AT', false],
            ['Auti-i-timi-prepi-na-inai-psefdis', 'Αυτή η τιμή πρέπει να είναι ψευδής', '-', 'el', false],
            ['Gai-Bian-Liang-De-Zhi-Ying-Wei', '该变量的值应为', '-', ASCII::CHINESE_LANGUAGE_CODE, false, false, true],
            ['Gai-Bian-Shu-De-Zhi-Ying-Wei', '該變數的值應為', '-', 'zh_TW', false, false, true],
            ['Gai-Bian-Liang-De-Zhi-Ying-Wei', '该变量的值应为', '-', ASCII::CHINESE_LANGUAGE_CODE, false, true, true],
            ['Gai-Bian-Shu-De-Zhi-Ying-Wei', '該變數的值應為', '-', 'zh_TW', false, true, true],
            ['ami-banglay-ktha-bli-ngkx', 'আমি বাংলায় কথা বলি ... ঙ্ক্ষ', '-', ASCII::BENGALI_LANGUAGE_CODE, true, true, true],
        ];
    }

    public function testCharsArrayWithMultiLanguageValues()
    {
        $array = ASCII::charsArrayWithMultiLanguageValues();

        static::assertSame(
            [
                0 => 'б',
                1 => 'բ',
                2 => 'ဗ',
                3 => 'ბ',
                4 => 'ب',
                5 => 'ব',
            ],
            $array['b']
        );

        // -- check the static cache

        $array = ASCII::charsArrayWithMultiLanguageValues();

        static::assertSame(
            [
                0 => 'б',
                1 => 'բ',
                2 => 'ဗ',
                3 => 'ბ',
                4 => 'ب',
                5 => 'ব',
            ],
            $array['b']
        );

        // ---

        $array = ASCII::charsArrayWithMultiLanguageValues(true);

        static::assertSame(
            [
                0 => 'б',
                1 => 'բ',
                2 => 'ဗ',
                3 => 'ბ',
                4 => 'ب',
                5 => 'ব',
            ],
            $array['b']
        );
        static::assertSame(
            [
                0 => '&',
                1 => '﹠',
                2 => '＆',
            ],
            $array['&']
        );
        static::assertSame(['€'], $array[' Euro ']);

        // -- check the static cache

        $array = ASCII::charsArrayWithMultiLanguageValues(true);

        static::assertSame(
            [
                0 => 'б',
                1 => 'բ',
                2 => 'ဗ',
                3 => 'ბ',
                4 => 'ب',
                5 => 'پ',
                5 => 'ব',
            ],
            $array['b']
        );
        static::assertSame(
            [
                0 => '&',
                1 => '﹠',
                2 => '＆',
            ],
            $array['&']
        );
        static::assertSame(['€'], $array[' Euro ']);
    }

    public function testCharsArrayWithOneLanguage()
    {
        $array = ASCII::charsArrayWithOneLanguage('abcde');

        static::assertSame([], $array['replace']);
        static::assertSame([], $array['orig']);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('####');

        static::assertSame([], $array['replace']);
        static::assertSame([], $array['orig']);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('de_at');

        static::assertContains('Ae', $array['replace']);
        static::assertContains('sz', $array['replace']);
        static::assertNotContains('ss', $array['replace']);
        static::assertContains('ß', $array['orig']);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('de-CH');

        static::assertContains('Ae', $array['replace']);
        static::assertContains('ss', $array['replace']);
        static::assertNotContains('sz', $array['replace']);
        static::assertContains('ß', $array['orig']);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('de');

        static::assertContains('Ae', $array['replace']);
        static::assertNotContains('yo', $array['replace']);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('de_DE');

        static::assertContains('Ae', $array['replace']);
        static::assertNotContains('yo', $array['replace']);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('de-DE');

        static::assertContains('Ae', $array['replace']);
        static::assertNotContains('yo', $array['replace']);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('ru');

        static::assertNotContains('Ae', $array['replace']);
        static::assertContains('yo', $array['replace']);

        $tmpKey = \array_search('yo', $array['replace'], true);
        static::assertSame('ё', $array['orig'][$tmpKey]);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('de', true);

        static::assertContains('Ae', $array['replace']);
        static::assertNotContains('yo', $array['replace']);
        static::assertContains(' und ', $array['replace']);
        static::assertNotContains(' и ', $array['replace']);

        $tmpKey = \array_search(' und ', $array['replace'], true);
        static::assertSame('&', $array['orig'][$tmpKey]);

        // ---

        $array = ASCII::charsArrayWithOneLanguage('ru', true);

        static::assertContains('yo', $array['replace']);
        static::assertNotContains('Ae', $array['replace']);
        static::assertContains(' i ', $array['replace']);
        static::assertNotContains(' und ', $array['replace']);

        $tmpKey = \array_search(' i ', $array['replace'], true);
        static::assertSame('&', $array['orig'][$tmpKey]);
    }

    public function testCharsArrayWithSingleLanguageValues()
    {
        $array = ASCII::charsArrayWithSingleLanguageValues();

        static::assertContains('hnaik', $array['replace']);
        static::assertContains('yo', $array['replace']);

        $tmpKey = \array_search('hnaik', $array['replace'], true);
        static::assertSame('၌', $array['orig'][$tmpKey]);

        // ---

        $array = ASCII::charsArrayWithSingleLanguageValues(true);

        static::assertContains('hnaik', $array['replace']);
        static::assertContains('yo', $array['replace']);
        static::assertContains(' pound ', $array['replace']);

        $tmpKey = \array_search(' pound ', $array['replace'], true);
        static::assertSame('£', $array['orig'][$tmpKey]);
    }

    public function testCharsArray()
    {
        $array = ASCII::charsArray();

        static::assertSame('b', $array['ru']['б']);

        // ---

        $arrayMore = ASCII::charsArray(true);

        static::assertSame('b', $arrayMore['ru']['б']);

        static::assertSame(' i ', $arrayMore['ru']['&']);

        // ---

        static::assertGreaterThan(\count($array), \count($arrayMore));
    }

    public function testFilterFile()
    {
        $testArray = [
            "test-\xe9\x00\x0é大般若經.txt"      => 'test-.txt',
            'test-大般若經.txt'                  => 'test-.txt',
            'фото.jpg'                       => '.jpg',
            'Фото.jpg'                       => '.jpg',
            'öäü  - test'                    => 'test',
            'שדגשדג.png'                     => '.png',
            '—©®±àáâãäåæÒÓÔÕÖ¼½¾§µçðþú–.jpg' => '.jpg',
            '000—©—©.txt'                    => '000.txt',
            ' '                              => '',
        ];

        foreach ($testArray as $before => $after) {
            static::assertSame($after, ASCII::to_filename($before, false));
        }

        // ---

        $testArray = [
            "test-\xe9\x00\x0é大般若經.txt"      => 'test-eDa-Ban-Ruo-Jing-.txt',
            'test-大般若經.txt'                  => 'test-Da-Ban-Ruo-Jing-.txt',
            'фото.jpg'                       => 'foto.jpg',
            'Фото.jpg'                       => 'Foto.jpg',
            'öäü  - test'                    => 'oau-test',
            'שדגשדג.png'                     => 'SHdgSHdg.png',
            '—©®±àáâãäåæÒÓÔÕÖ¼½¾§µçðþú–.jpg' => 'cr-aaaaaaaeOOOOO141234SSucdthu-.jpg',
            '000—©—©.txt'                    => '000-c-c.txt',
            ' '                              => '',
        ];

        foreach ($testArray as $before => $after) {
            static::assertSame($after, ASCII::to_filename($before, true));
        }
    }

    /**
     * @dataProvider slugifyProvider()
     *
     * @param string $expected
     * @param string $str
     * @param string $replacement
     * @param string $lang
     * @param bool   $use_str_to_lower
     * @param bool   $replace_extra_symbols
     * @param bool   $use_transliterate
     */
    public function testSlugify(
        $expected,
        $str,
        $replacement = '-',
        $lang = 'en',
        $use_str_to_lower = true,
        $replace_extra_symbols = true,
        $use_transliterate = false
    ) {
        $result = ASCII::to_slugify(
            $str,
            $replacement,
            $lang,
            ['foooooo' => 'bar'],
            $replace_extra_symbols,
            $use_str_to_lower,
            $use_transliterate
        );

        static::assertSame($expected, $result, 'tested: ' . $str);
    }

    /**
     * @dataProvider toAsciiProvider()
     *
     * @param string $expected
     * @param string $str
     * @param string $language
     * @param bool   $remove_unsupported_chars
     * @param bool   $replace_extra_symbols
     * @param bool   $use_transliterate
     */
    public function testToAscii(
        $expected,
        $str,
        $language = 'en',
        $remove_unsupported_chars = true,
        $replace_extra_symbols = false,
        $use_transliterate = false
    ) {
        for ($i = 0; $i <= 2; ++$i) { // keep this loop for simple performance tests
            $result = ASCII::to_ascii(
                $str,
                $language,
                $remove_unsupported_chars,
                $replace_extra_symbols,
                $use_transliterate
            );
        }

        static::assertSame($expected, $result, 'tested: ' . $str);
    }

    public function testIsAsciiTest()
    {
        $a = ASCII::charsArrayWithMultiLanguageValues(false);

        foreach ($a as $k => $v) {
            static::assertTrue(ASCII::is_ascii((string) $k), 'tested: ' . $k . ' - ' . \print_r($v, true));
        }

        // ---

        $a = ASCII::charsArrayWithMultiLanguageValues(true);

        $skip = [
            '∑' => '∑',
            '∆' => '∆',
            '∞' => '∞',
            '♥' => '♥',
        ];
        foreach ($a as $k => $v) {
            if (\in_array($k, $skip, true)) {
                continue;
            }

            static::assertTrue(ASCII::is_ascii((string) $k), 'tested: ' . $k . ' - ' . \print_r($v, true));
        }
    }

    public function toAsciiProvider(): array
    {
        return [
            ['      ! " # $ % & \' ( ) * + , @ `', " \v \t \n" . ' ! " # $ % & \' ( ) * + , @ `'], // ascii symbols
            ['foo bar |  | ~', 'fòô bàř | 🅉 | ~'],
            [' TEST 3C', ' ŤÉŚŢ 3°C'],
            [' TEST 3 Celsius ', ' ŤÉŚŢ 3°C', ASCII::ENGLISH_LANGUAGE_CODE, true, true],
            ['f = z = 3', 'φ = ź = 3'],
            ['perevirka', 'перевірка'],
            ['lysaia gora', 'лысая гора'],
            ['I  ', 'I ♥ 字'],
            ['I  ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE],
            ['I ♥ 字', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, false],
            ['I  love  字', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, false, true],
            ['I ♥ 字', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, false, false],
            ['I  love  字', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, false, true, false],
            ['I  love  Zi ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, false, true, true],
            ['I ♥ 字', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, false, false, false],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, false, false, true],
            ['I  ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, true],
            ['I  love  ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, true, true],
            ['I  ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, true, false],
            ['I  love  ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, true, true, false],
            ['I  love  Zi ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, true, true, true],
            ['I  ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, true, false, false],
            ['I  Zi ', 'I ♥ 字', ASCII::ENGLISH_LANGUAGE_CODE, true, false, true],
            ['I  Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, false],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, false, true],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, false, false],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, false, true, false],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, false, true, true],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, false, false, false],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, false, false, true],
            ['I  Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, true],
            ['I  Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, true, true],
            ['I  Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, true, false],
            ['I  Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, true, true, false],
            ['I  Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, true, true, true],
            ['I  Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, true, false, false],
            ['I  Zi ', 'I ♥ 字', ASCII::CHINESE_LANGUAGE_CODE, true, false, true],
            ['I  ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE],
            ['I ♥ 字', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, false],
            ['I  liebe  字', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, false, true],
            ['I ♥ 字', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, false, false],
            ['I  liebe  字', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, false, true, false],
            ['I  liebe  Zi ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, false, true, true],
            ['I ♥ 字', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, false, false, false],
            ['I ♥ Zi ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, false, false, true],
            ['I  ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, true],
            ['I  liebe  ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, true, true],
            ['I  ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, true, false],
            ['I  liebe  ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, true, true, false],
            ['I  liebe  Zi ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, true, true, true],
            ['I  ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, true, false, false],
            ['I  Zi ', 'I ♥ 字', ASCII::GERMAN_LANGUAGE_CODE, true, false, true],
            ['Uzbek', 'Ўзбек', ASCII::UZBEK_LANGUAGE_CODE],
            ['Turkmen', 'Түркмен', ASCII::TURKMEN_LANGUAGE_CODE],
            ['aithy', 'ไทย', ASCII::THAI_LANGUAGE_CODE],
            ['pSto', 'پښتو', ASCII::PASHTO_LANGUAGE_CODE],
            ['odd\'iaa', 'ଓଡ଼ିଆ', ASCII::ORIYA_LANGUAGE_CODE],
            ['Mongol xel', 'Монгол хэл', ASCII::MONGOLIAN_LANGUAGE_CODE],
            ['hangugeo', '한국어', ASCII::KOREAN_LANGUAGE_CODE],
            ['Kyrgyzca', 'Кыргызча', ASCII::KIRGHIZ_LANGUAGE_CODE],
            ['Hayeren', 'Հայերեն', ASCII::ARMENIAN_LANGUAGE_CODE],
            ['bangla', 'বাংলা', ASCII::BENGALI_LANGUAGE_CODE],
            ['belaruskaia', 'беларуская', ASCII::BELARUSIAN_LANGUAGE_CODE],
            ['\'amaarenyaa', 'አማርኛ', ASCII::AMHARIC_LANGUAGE_CODE],
            ['Ri Ben Yu  (nihongo)', '日本語 (にほんご)', ASCII::JAPANESE_LANGUAGE_CODE],
            ['een oplossing - aou', 'één oplossing - äöü', ASCII::DUTCH_LANGUAGE_CODE],
            ['Universita', 'Università', ASCII::ITALIAN_LANGUAGE_CODE],
            ['Makedonska azbuka', 'Македонска азбука', ASCII::MACEDONIAN_LANGUAGE_CODE],
            ['Eu nao falo portugues.', 'Eu não falo português.', ASCII::PORTUGUESE_LANGUAGE_CODE],
            ['lysaya gora', 'лысая гора', ASCII::RUSSIAN_LANGUAGE_CODE],
            ['lysaia gora', 'лысая гора', ASCII::RUSSIAN_PASSPORT_2013_LANGUAGE_CODE],
            ['ly\'saya gora', 'лысая гора', ASCII::RUSSIAN_GOST_2000_B_LANGUAGE_CODE],
            ['shhuka', 'щука'],
            ['shhuka', 'щука', ASCII::EXTRA_LATIN_CHARS_LANGUAGE_CODE],
            ['Elliniko alfavito', 'Ελληνικό αλφάβητο', ASCII::GREEK_LANGUAGE_CODE],
            ['Athina', 'Αθήνα', ASCII::GREEK_LANGUAGE_CODE],
            [
                'As prostheso ki eghw oti ta teleftaia dyo khronia pu ekana Xristoughenna stin Thessaloniki ta mona paidia',
                'Ας προσθέσω κι εγώ ότι τα τελευταία δύο χρόνια που έκανα Χριστούγεννα στην Θεσσαλονίκη τα μόνα παιδιά',
                ASCII::GREEK_LANGUAGE_CODE,
            ],
            [
                'pu irthan na mas pun ta kallanta itan prosfighopula, koritsia sinithos, apo tin Georghia.',
                'που ήρθαν να μας πουν τα κάλλαντα ήταν προσφυγόπουλα, κορίτσια συνήθως, από την Γεωργία.',
                ASCII::GREEK_LANGUAGE_CODE,
            ],
            ['Athhna', 'Αθήνα', ASCII::GREEKLISH_LANGUAGE_CODE],
            [
                'As prosthesw ki egw oti ta teleutaia dyo xronia pou ekana Xristougenna sthn Thessalonikh ta mona paidia',
                'Ας προσθέσω κι εγώ ότι τα τελευταία δύο χρόνια που έκανα Χριστούγεννα στην Θεσσαλονίκη τα μόνα παιδιά',
                ASCII::GREEKLISH_LANGUAGE_CODE,
            ],
            [
                'pou hrthan na mas poun ta kallanta htan prosfygopoula, koritsia synhthws, apo thn Gewrgia.',
                'που ήρθαν να μας πουν τα κάλλαντα ήταν προσφυγόπουλα, κορίτσια συνήθως, από την Γεωργία.',
                ASCII::GREEKLISH_LANGUAGE_CODE,
            ],
            ['Elliniko alfavito', 'Ελληνικό αλφάβητο', ASCII::GREEK_LANGUAGE_CODE],
            ['uThaHaRaNae', 'उदाहरण', ASCII::HINDI_LANGUAGE_CODE],
            ['IGAR', 'IGÅR', ASCII::SWEDISH_LANGUAGE_CODE],
            ['Gronland', 'Grø̈nland', ASCII::SWEDISH_LANGUAGE_CODE],
            ['gorusmek', 'görüşmek', ASCII::TURKISH_LANGUAGE_CODE],
            ['primer', 'пример', ASCII::BULGARIAN_LANGUAGE_CODE],
            ['vasarlo', 'vásárló', ASCII::HUNGARIAN_LANGUAGE_CODE],
            ['ttyanongyath', 'တတျနိုငျသ', ASCII::MYANMAR_LANGUAGE_CODE],
            ['sveucilist', 'sveučilišt', ASCII::CROATIAN_LANGUAGE_CODE],
            ['paivakoti', 'päiväkoti', ASCII::FINNISH_LANGUAGE_CODE],
            ['bavshvebi', 'ბავშვები', ASCII::GEORGIAN_LANGUAGE_CODE],
            ['schuka', 'щука', ASCII::RUSSIAN_LANGUAGE_CODE],
            ['shchuka', 'щука', ASCII::RUSSIAN_PASSPORT_2013_LANGUAGE_CODE],
            ['shhuka', 'щука', ASCII::RUSSIAN_GOST_2000_B_LANGUAGE_CODE],
            ['dity', 'діти', ASCII::UKRAINIAN_LANGUAGE_CODE],
            ['horokh', 'горох', ASCII::UKRAINIAN_LANGUAGE_CODE],
            ['shchastia', 'щастя', ASCII::UKRAINIAN_LANGUAGE_CODE],
            ['Chernivtsi', 'Чернівці', ASCII::UKRAINIAN_LANGUAGE_CODE],
            ['shtany', 'штани', ASCII::UKRAINIAN_LANGUAGE_CODE],
            ['universitet', 'университет', ASCII::KAZAKH_LANGUAGE_CODE],
            ['univerzitni', 'univerzitní', ASCII::CZECH_LANGUAGE_CODE],
            ['besoegende', 'besøgende', ASCII::DANISH_LANGUAGE_CODE],
            ['Odwiedzajacy', 'Odwiedzający', ASCII::POLISH_LANGUAGE_CODE],
            ['gradinita', 'grădiniță', ASCII::ROMANIAN_LANGUAGE_CODE],
            ['infangxardeno', 'infanĝardeno', ASCII::ESPERANTO_LANGUAGE_CODE],
            ['Ulikool', 'Ülikool', ASCII::ESTONIAN_LANGUAGE_CODE],
            ['bernudarzs', 'bērnudārzs', ASCII::LATVIAN_LANGUAGE_CODE],
            ['vaiku darzelis', 'vaikų darželis', ASCII::LITHUANIAN_LANGUAGE_CODE],
            ['kundestoette', 'kundestøtte', ASCII::NORWEGIAN_LANGUAGE_CODE],
            ['truong hoc', 'trường học', ASCII::VIETNAMESE_LANGUAGE_CODE],
            ['gamaa', 'جامعة', ASCII::ARABIC_LANGUAGE_CODE],
            ['danshgah', 'دانشگاه', ASCII::PERSIAN_LANGUAGE_CODE],
            ['univerzitet', 'универзитет', ASCII::SERBIAN_LANGUAGE_CODE],
            ['univerzitet', 'универзитет', ASCII::SERBIAN_CYRILLIC_LANGUAGE_CODE],
            ['univerzitet', 'универзитет', ASCII::SERBIAN_LATIN_LANGUAGE_CODE],
            ['musteri', 'müştəri', ASCII::AZERBAIJANI_LANGUAGE_CODE],
            ['zakaznik', 'zákazník', ASCII::SLOVAK_LANGUAGE_CODE],
            ['francais', 'français', ASCII::FRENCH_LANGUAGE_CODE],
            ['bangla', 'বাংলা', ASCII::BENGALI_LANGUAGE_CODE],
            ['user@host', 'user@host'],
            ['', '漢字'],
            ['xin chao the gioi', 'xin chào thế giới'],
            ['XIN CHAO THE GIOI', 'XIN CHÀO THẾ GIỚI'],
            ['dam phat chet luon', 'đấm phát chết luôn'],
            [' ', ' '], // no-break space (U+00A0)
            ['           ', '           '], // spaces U+2000 to U+200A
            [' ', ' '], // narrow no-break space (U+202F)
            [' ', ' '], // medium mathematical space (U+205F)
            [' ', '　'], // ideographic space (U+3000)
            ['', '𐍉'], // some uncommon, unsupported character (U+10349)
            ['𐍉', '𐍉', ASCII::ENGLISH_LANGUAGE_CODE, false],
            ['aouAOUss', 'äöüÄÖÜß'],
            ['aeoeueAeOeUess', 'äöüÄÖÜß', 'de_DE'],
            ['aeoeueAeOeUess ', 'äöüÄÖÜß ', 'de_DE'],
            ['aeoeueAeOeUess ', 'äöüÄÖÜß ®', 'de_DE'],
            ['aeoeueAeOeUess ®', 'äöüÄÖÜß ®', 'de_DE', false],
            ['aeoeueAeOeUess  (r) ', 'äöüÄÖÜß ®', 'de_DE', false, true],
            ['aeoeueAeOeUess  (r) ', 'äöüÄÖÜß ®', 'de_DE', true, true],
            ['aeoeueAeOeUess  (r) ', 'äöüÄÖÜß ®', 'de_DE', true, true, true],
            ['aeoeueAeOeUess  (r) ', 'äöüÄÖÜß ®', 'de_DE', false, true, true],
            ['aeoeueAeOeUess (r)', 'äöüÄÖÜß ®', 'de_DE', true, false, true],
            ['aeoeueAeOeUess (r)', 'äöüÄÖÜß ®', 'de_DE', false, false, true],
            ['aeoeueAeOeUess', 'äöüÄÖÜß', ASCII::GERMAN_LANGUAGE_CODE],
            ['aeoeueAeOeUesz', 'äöüÄÖÜß', ASCII::GERMAN_AUSTRIAN_LANGUAGE_CODE],
            ['aeoeueAeOeUess', 'äöüÄÖÜß', ASCII::GERMAN_SWITZERLAND_LANGUAGE_CODE],
            ['aouAOUss', 'äöüÄÖÜß', ASCII::FRENCH_LANGUAGE_CODE],
            ['aouAOUsz', 'äöüÄÖÜß', ASCII::FRENCH_AUSTRIAN_LANGUAGE_CODE],
            ['aouAOUss', 'äöüÄÖÜß', ASCII::FRENCH_SWITZERLAND_LANGUAGE_CODE],
            ['h H sht Sht a A ia yo', 'х Х щ Щ ъ Ъ иа йо', 'bg'],
            // Valid ASCII + Invalid Chars
            ['a-', "a\xa0\xa1-öäü"],
            // Valid 2 Octet Sequence
            ['n', "\xc3\xb1"], // ñ
            // Invalid 2 Octet Sequence
            ['(', "\xc3\x28"],
            // Invalid
            ['', "\x00"],
            ['ab', "a\xDFb"],
            // Invalid Sequence Identifier
            ['', "\xa0\xa1"],
            // Valid 3 Octet Sequence
            ['CL', "\xe2\x82\xa1"],
            // Invalid 3 Octet Sequence (in 2nd Octet)
            ['(', "\xe2\x28\xa1"],
            // Invalid 3 Octet Sequence (in 3rd Octet)
            ['(', "\xe2\x82\x28"],
            // Valid 4 Octet Sequence
            ['', "\xf0\x90\x8c\xbc"],
            // Invalid 4 Octet Sequence (in 2nd Invalid 4 Octet Sequence (in 2ndOctet)
            ['(', "\xf0\x28\x8c\xbc"],
            // Invalid 4 Octet Sequence (in 3rd Octet)
            ['(', "\xf0\x90\x28\xbc"],
            // Invalid 4 Octet Sequence (in 4th Octet)
            ['((', "\xf0\x28\x8c\x28"],
            // Valid 5 Octet Sequence (but not Unicode!)
            ['', "\xf8\xa1\xa1\xa1\xa1"],
            // Valid 6 Octet Sequence (but not Unicode!)
            ['', "\xfc\xa1\xa1\xa1\xa1\xa1"],
            // Valid 6 Octet Sequence (but not Unicode!) + UTF-8 EN SPACE
            ['', "\xfc\xa1\xa1\xa1\xa1\xa1\xe2\x80\x82"],
        ];
    }

    /**
     * @noinspection DuplicatedCode
     */
    public function testCleanParameter()
    {
        $dirtyTestString = "\xEF\xBB\xBF„Abcdef\xc2\xa0\x20…” — 😃";

        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString));
        static::assertSame('﻿„Abcdef  …” — 😃', ASCII::clean($dirtyTestString, false, true, false, false));
        static::assertSame('﻿„Abcdef  …” — 😃', ASCII::clean($dirtyTestString, false, false, false, true));
        static::assertSame('﻿„Abcdef  …” — 😃', ASCII::clean($dirtyTestString, false, false, false, false));
        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString, false, false, true, true));
        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString, false, false, true, false));
        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString, false, true, true, false));
        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString, false, true, true, true));
        static::assertSame('﻿„Abcdef  …” — 😃', ASCII::clean($dirtyTestString, true, false, false, false));
        static::assertSame('﻿„Abcdef  …” — 😃', ASCII::clean($dirtyTestString, true, false, false, true));
        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString, true, false, true, false));
        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString, true, false, true, true));
        static::assertSame('﻿„Abcdef  …” — 😃', ASCII::clean($dirtyTestString, true, true, false, false));
        static::assertSame('﻿„Abcdef  …” — 😃', ASCII::clean($dirtyTestString, true, true, false, true));
        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString, true, true, true, false));
        static::assertSame('﻿"Abcdef  ..." - 😃', ASCII::clean($dirtyTestString, true, true, true, true));
    }

    public function testLanguageFiles()
    {
        $ascii_by_languages = include __DIR__ . '/../src/voku/helper/data/ascii_by_languages.php';
        $ascii_extras_by_languages = include __DIR__ . '/../src/voku/helper/data/ascii_extras_by_languages.php';

        $notFound = [];
        foreach ($ascii_by_languages as $lang => $tmp) {
            if (\array_key_exists($lang, $ascii_extras_by_languages) === false) {
                $notFound[$lang] = ' Extra Language was not found! ';
            }
        }

        // remove false-positive results
        unset(
            $notFound['latin'],
            $notFound[' '],
            $notFound['msword'],
            $notFound['currency_short']
        );

        static::assertCount(0, $notFound, \print_r($notFound, true));
    }

    public function testNormalizeMsword()
    {
        $tests = [
            ''                                                                         => '',
            ' '                                                                        => ' ',
            '«foobar»'                                                                 => '<<foobar>>',
            '中文空白 ‟'                                                                   => '中文空白 "',
            "<ㅡㅡ></ㅡㅡ><div>…</div><input type='email' name='user[email]' /><a>wtf</a>" => "<ㅡㅡ></ㅡㅡ><div>...</div><input type='email' name='user[email]' /><a>wtf</a>",
            '– DÃ¼sseldorf —'                                                          => '- DÃ¼sseldorf -',
            '„Abcdef…”'                                                                => '"Abcdef..."',
        ];

        foreach ($tests as $before => $after) {
            static::assertSame($after, ASCII::normalize_msword($before));
        }
    }

    public function testNormalizeWhitespace()
    {
        $tests = [
            ''                                                                                    => '',
            ' '                                                                                   => ' ',
            ' foo ' . "\xe2\x80\xa8" . ' öäü' . "\xe2\x80\xa9"                                    => ' foo   öäü ',
            "«\xe2\x80\x80foobar\xe2\x80\x80»"                                                    => '« foobar »',
            '中文空白 ‟'                                                                              => '中文空白 ‟',
            "<ㅡㅡ></ㅡㅡ><div>\xe2\x80\x85</div><input type='email' name='user[email]' /><a>wtf</a>" => "<ㅡㅡ></ㅡㅡ><div> </div><input type='email' name='user[email]' /><a>wtf</a>",
            "–\xe2\x80\x8bDÃ¼sseldorf\xe2\x80\x8b—"                                               => '– DÃ¼sseldorf —',
            "„Abcdef\xe2\x81\x9f”"                                                                => '„Abcdef ”',
            " foo\t foo "                                                                         => ' foo	 foo ',
        ];

        for ($i = 0; $i < 2; ++$i) { // keep this loop for simple performance tests
            foreach ($tests as $before => $after) {
                static::assertSame($after, ASCII::normalize_whitespace($before));
            }
        }

        // replace "non breaking space"
        static::assertSame('abc- -öäü- -', ASCII::normalize_whitespace("abc-\xc2\xa0-öäü-\xe2\x80\xaf-\xE2\x80\xAC"));

        // keep "non breaking space"
        static::assertSame("abc-\xc2\xa0-öäü- -", ASCII::normalize_whitespace("abc-\xc2\xa0-öäü-\xe2\x80\xaf-\xE2\x80\xAC", true));

        // ... and keep "bidirectional text chars"
        static::assertSame("abc-\xc2\xa0-öäü- -\xE2\x80\xAC", ASCII::normalize_whitespace("abc-\xc2\xa0-öäü-\xe2\x80\xaf-\xE2\x80\xAC", true, true));
    }
}
