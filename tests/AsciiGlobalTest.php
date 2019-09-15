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
            ['foo-bar', ' foo  bar '],
            ['foo-bar', 'foo -.-"-...bar'],
            ['another-and-foo-bar', 'another..& foo -.-"-...bar'],
            ['foo-dbar', " Foo d'Bar "],
            ['a-string-with-dashes', 'A string-with-dashes'],
            ['user-at-host', 'user@host'],
            ['using-strings-like-foo-bar', 'Using strings like fòô bàř'],
            ['numbers-1234', 'numbers 1234'],
            ['perevirka-rjadka', 'перевірка рядка'],
            ['bukvar-s-bukvoj-y', 'букварь с буквой ы'],
            ['podehal-k-podezdu-moego-doma', 'подъехал к подъезду моего дома'],
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

        static::assertSame(['β', 'б', 'ဗ', 'ბ', 'ب'], $array['b']);

        // ---

        $array = ASCII::charsArrayWithMultiLanguageValues(true);

        static::assertSame(['β', 'б', 'ဗ', 'ბ', 'ب'], $array['b']);
        static::assertSame(['&'], $array['&']);
        static::assertSame(['€'], $array[' Euro ']);
    }

    public function testCharsArrayWithOneLanguage()
    {
        $array = ASCII::charsArrayWithOneLanguage('de');

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
        $str = ASCII::to_slugify($str, $replacement);

        static::assertSame($expected, $str);
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
        $result = ASCII::to_ascii($str, $language, $removeUnsupported);

        static::assertSame($expected, $result);
    }

    public function toAsciiProvider(): array
    {
        return [
            ['foo bar', 'fòô bàř'],
            [' TEST ', ' ŤÉŚŢ '],
            ['f = z = 3', 'φ = ź = 3'],
            ['perevirka', 'перевірка'],
            ['lysaja gora', 'лысая гора'],
            ['shhuka', 'щука'],
            ['shhuka', 'щука', 'ru'],
            ['Ellhniko alfabhto', 'Ελληνικό αλφάβητο', 'el'],
            ['uThaHaRaNae', 'उदाहरण', 'hi'],
            ['IGaR', 'IGÅR', 'sv'],
            ['gorusmek', 'görüşmek', 'tr'],
            ['primer', 'пример', 'bg'],
            ['vasarlo', 'vásárló', 'hu'],
            ['ttyanongyath', 'တတျနိုငျသ', 'by'],
            ['sveucilist', 'sveučilišt', 'hr'],
            ['paivakoti', 'päiväkoti', 'fr'],
            ['bavshvebi', 'ბავშვები', 'ka'],
            ['diti', 'діти', 'uk'],
            ['universitet', 'университет', 'kk'],
            ['univerzitni', 'univerzitní', 'cs'],
            ['besoegende', 'besøgende', 'da'],
            ['Odwiedzajacy', 'Odwiedzający', 'pl'],
            ['gradinita', 'grădiniță', 'ro'],
            ['infangxardeno', 'infanĝardeno', 'eo'],
            ['Ulikool', 'Ülikool', 'et'],
            ['bernudarzs', 'bērnudārzs', 'lv'],
            ['vaiku darzelis', 'vaikų darželis', 'lt'],
            ['kundestoette', 'kundestøtte', 'no'],
            ['truong hoc', 'trường học', 'vi'],
            ['gamaa', 'جامعة', 'ar'],
            ['danshgah', 'دانشگاه', 'fa'],
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
            ['𐍉', '𐍉', 'en', false],
            ['aouAOU', 'äöüÄÖÜ'],
            ['aeoeueAeOeUe', 'äöüÄÖÜ', 'de'],
            ['aeoeueAeOeUe', 'äöüÄÖÜ', 'de_DE'],
            ['h H sht Sht a A ia yo', 'х Х щ Щ ъ Ъ ия йо', 'bg'],
        ];
    }

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
