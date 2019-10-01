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
            ['bar', 'foooooo'], // "foooooo" will be replaced in the method call
            ['foo-bar', ' foo  bar '],
            ['foo-bar', 'foo -.-"-...bar'],
            ['another-and-foo-bar', 'another..& foo -.-"-...bar'],
            ['foo-dbar', " Foo d'Bar "],
            ['a-string-with-dashes', 'A string-with-dashes'],
            ['user-at-host', 'user@host'],
            ['using-strings-like-foo-bar', 'Using strings like fòô bàř'],
            ['numbers-1234', 'numbers 1234'],
            ['perevirka-ryadka', 'перевірка рядка'],
            ['bukvar-s-bukvoi-y', 'букварь с буквой ы'],
            ['podexal-k-podezdu-moego-doma', 'подъехал к подъезду моего дома'],
            ['foo:bar:baz', 'Foo bar baz', ':'],
            ['a_string_with_underscores', 'A_string with_underscores', '_'],
            ['a_string_with_dashes', 'A string-with-dashes', '_'],
            ['one_euro_or_a_dollar', 'one € or a $', '_'],
            ['a\string\with\dashes', 'A string-with-dashes', '\\'],
            ['an_odd_string', '--   An odd__   string-_', '_'],
        ];
    }

    public function testCharsArrayWithMultiLanguageValues()
    {
        $array = ASCII::charsArrayWithMultiLanguageValues();

        static::assertSame(
            [
                0 => 'б',
                1 => 'ဗ',
                2 => 'ბ',
                3 => 'ب',
                4 => 'پ',
            ],
            $array['b']
        );

        // ---

        $array = ASCII::charsArrayWithMultiLanguageValues(true);

        static::assertSame(['β', 'б', 'ဗ', 'ბ', 'ب', 'پ'], $array['b']);
        static::assertSame(['&'], $array['&']);
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
        static::assertContains(' и ', $array['replace']);
        static::assertNotContains(' und ', $array['replace']);

        $tmpKey = \array_search(' и ', $array['replace'], true);
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
            'שדגשדג.png'                     => 'shdgshdg.png',
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
     * @param mixed $expected
     * @param mixed $str
     * @param mixed $replacement
     */
    public function testSlugify($expected, $str, $replacement = '-')
    {
        $result = ASCII::to_slugify($str, $replacement, 'en', ['foooooo' => 'bar']);

        static::assertSame($expected, $result, 'tested: ' . $str);
    }

    /**
     * @dataProvider toAsciiProvider()
     *
     * @param mixed $expected
     * @param mixed $str
     * @param mixed $language
     * @param mixed $removeUnsupported
     */
    public function testToAscii(
        $expected,
        $str,
        $language = 'en',
        $removeUnsupported = true
    ) {
        for ($i = 0; $i <= 2; ++$i) { // keep this loop for simple performance tests
            $result = ASCII::to_ascii($str, $language, $removeUnsupported);
        }

        static::assertSame($expected, $result, 'tested: ' . $str);
    }

    public function toAsciiProvider(): array
    {
        return [
            ['      ! " # $ % & \' ( ) * + , @ `', " \v \t \n" . ' ! " # $ % & \' ( ) * + , @ `'], // ascii symbols
            ['foo bar', 'fòô bàř'],
            [' TEST ', ' ŤÉŚŢ '],
            ['f = z = 3', 'φ = ź = 3'],
            ['perevirka', 'перевірка'],
            ['ly\'saya gora', 'лысая гора'],
            ['lysaja gora', 'лысая гора', ASCII::RUSSIAN_LANGUAGE_CODE],
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
            ['A8hna', 'Αθήνα', ASCII::GREEKLISH_LANGUAGE_CODE],
            [
                'As nros8esw ki egw oti ta teleutaia dyo xronia nou ekana Xristougenna sthn 8essalonikh ta mona naidia',
                'Ας προσθέσω κι εγώ ότι τα τελευταία δύο χρόνια που έκανα Χριστούγεννα στην Θεσσαλονίκη τα μόνα παιδιά',
                ASCII::GREEKLISH_LANGUAGE_CODE,
            ],
            [
                'nou hr8an na mas noun ta kallanta htan nrosfygonoula, koritsia synh8ws, ano thn Gewrgia.',
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
            ['shhuka', 'щука', ASCII::RUSSIAN_LANGUAGE_CODE],
            ['shchuka', 'щука', ASCII::RUSSIAN_PASSPORT_2013_LANGUAGE_CODE],
            ['shhuka', 'щука', ASCII::RUSSIAN_GOST_2000_B_LANGUAGE_CODE],
            ['diti', 'діти', ASCII::UKRAINIAN_LANGUAGE_CODE],
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
            ['musteri', 'müştəri', ASCII::AZERBAIJANI_LANGUAGE_CODE],
            ['zakaznik', 'zákazník', ASCII::SLOVAK_LANGUAGE_CODE],
            ['francais', 'français', ASCII::FRENCH_LANGUAGE_CODE],
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
            ['aeoeueAeOeUess', 'äöüÄÖÜß', ASCII::GERMAN_LANGUAGE_CODE],
            ['aeoeueAeOeUesz', 'äöüÄÖÜß', ASCII::GERMAN_AUSTRIAN_LANGUAGE_CODE],
            ['aeoeueAeOeUess', 'äöüÄÖÜß', ASCII::GERMAN_SWITZERLAND_LANGUAGE_CODE],
            ['aouAOUss', 'äöüÄÖÜß', ASCII::FRENCH_LANGUAGE_CODE],
            ['aouAOUsz', 'äöüÄÖÜß', ASCII::FRENCH_AUSTRIAN_LANGUAGE_CODE],
            ['aouAOUss', 'äöüÄÖÜß', ASCII::FRENCH_SWITZERLAND_LANGUAGE_CODE],
            ['h H sht Sht a A ia yo', 'х Х щ Щ ъ Ъ иа йо', 'bg'],
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
}
