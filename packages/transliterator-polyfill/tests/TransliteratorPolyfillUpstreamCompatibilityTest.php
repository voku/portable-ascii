<?php

declare(strict_types=1);

namespace Voku\Transliterator\Tests;

use Voku\Transliterator\Transliterator;

/**
 * Nested-package smoke tests for the limited object wrapper surface.
 *
 * The full compatibility matrix lives in the root package tests. This scaffold
 * only proves that the extracted package surface stays wired to the same
 * limited behavior without copying the whole root test suite.
 *
 * @internal
 */
final class TransliteratorPolyfillUpstreamCompatibilityTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param callable $callback
     *
     * @return array{result: mixed, warning: string|null}
     */
    private static function callCapturingWarning(callable $callback): array
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
            $result = $callback();
        } finally {
            \restore_error_handler();
        }

        return ['result' => $result, 'warning' => $warning];
    }

    public function testCreateFromRulesReturnsNullWithWarning(): void
    {
        $captured = self::callCapturingWarning(static function () {
            return Transliterator::createFromRules('[\\p{White_Space}] hex');
        });

        static::assertNull($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('full ICU rule parser/executor', $captured['warning']);
    }

    public function testCreateInverseReturnsNullWithWarning(): void
    {
        $transliterator = Transliterator::create('Latin-ASCII');
        static::assertInstanceOf(Transliterator::class, $transliterator);

        $captured = self::callCapturingWarning(static function () use ($transliterator) {
            return $transliterator->createInverse();
        });

        static::assertNull($captured['result']);
        static::assertNotNull($captured['warning']);
        static::assertStringContainsString('does not support inverse transliterators', $captured['warning']);
    }
}
