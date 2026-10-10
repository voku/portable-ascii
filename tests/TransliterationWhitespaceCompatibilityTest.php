<?php

declare(strict_types=1);

namespace voku\tests;

use PHPUnit\Framework\TestCase;
use voku\helper\ASCII;

/**
 * @internal
 */
final class TransliterationWhitespaceCompatibilityTest extends TestCase
{
    public function testDefaultFallbackPreservesUnicodeWhitespaceLike211(): void
    {
        $cases = [
            'halfwidth hangul filler' => "x\u{FFA0}y",
            'ogham space mark' => "x\u{1680}y",
            'ideographic space' => "x\u{3000}y",
        ];

        foreach ($cases as $label => $input) {
            static::assertSame('x y', ASCII::normalize_whitespace($input), $label);
            // Both cold and warm calls must retain the same word separator.
            static::assertSame('x y', ASCII::to_transliterate($input), $label . ' cold');
            static::assertSame('x y', ASCII::to_transliterate($input), $label . ' warm');
        }
    }

    public function testExplicitFallbackDoesNotGainNewWhitespaceNormalization(): void
    {
        $input = "x\u{FFA0}y";

        // Preserve the custom-fallback behavior from 2.1.1.
        static::assertSame('xy', ASCII::to_transliterate($input, 'X'));
        static::assertSame('xy', ASCII::to_transliterate($input, 'X'));
    }
}
