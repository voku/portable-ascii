<?php

declare(strict_types=1);

namespace voku\tests;

use PHPUnit\Framework\TestCase;
use voku\helper\ASCII;

/**
 * @internal
 */
final class AsciiDecomposedUmlautTest extends TestCase
{
    public function testGermanDecomposedUmlautsMatchPrecomposedForms(): void
    {
        $cases = [
            ['ä', "a\u{0308}", 'ae'],
            ['ö', "o\u{0308}", 'oe'],
            ['ü', "u\u{0308}", 'ue'],
            ['Ä', "A\u{0308}", 'Ae'],
            ['Ö', "O\u{0308}", 'Oe'],
            ['Ü', "U\u{0308}", 'Ue'],
        ];

        foreach ($cases as [$precomposed, $decomposed, $expected]) {
            self::assertSame($expected, ASCII::to_ascii($precomposed, 'de'));
            self::assertSame($expected, ASCII::to_ascii($decomposed, 'de'));
        }
    }
}
