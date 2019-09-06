<?php

declare(strict_types=1);

namespace voku\tests;

use voku\helper\ASCII;

/**
 * @internal
 */
final class AsciiTest extends \PHPUnit\Framework\TestCase
{
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

    public function testInvalidCharToAscii()
    {
        $str = "tes\xe9ting";
        static::assertSame('testing', ASCII::to_transliterate($str));

        // ---

        $str = "tes\xe9ting";
        static::assertSame('', ASCII::to_ascii($str));
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

        $str = "a\nb\nc";
        static::assertSame("a\nb\nc", ASCII::to_ascii($str, 'en', false));

        // ---

        $str = "a\nb\nc";
        static::assertSame('a b c', ASCII::to_ascii($str, 'en', true));
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
}
