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
    public function testHalfwidthHangulFillerPreservesWhitespace(): void
    {
        $input = "x\u{FFA0}y";

        // normalize_whitespace() already specifies this conversion.
        static::assertSame('x y', ASCII::normalize_whitespace($input));

        // 2.1.1 ran whitespace cleanup before transliterating valid UTF-8
        // with the default unknown-character fallback.
        static::assertSame('x y', ASCII::to_transliterate($input));
    }
}
