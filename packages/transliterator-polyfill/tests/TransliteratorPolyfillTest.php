<?php

declare(strict_types=1);

namespace Voku\Transliterator\Tests;

use Voku\Transliterator\Transliterator;
use Voku\Transliterator\TransliteratorPolyfill;

/**
 * @internal
 */
final class TransliteratorPolyfillTest extends \PHPUnit\Framework\TestCase
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

    public function testFacadeStillSupportsBasicStringPipeline(): void
    {
        $result = TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'déjà vu');

        static::assertSame('deja vu', $result);
    }

    public function testPackageSurfaceExposesTransliterateMethod(): void
    {
        static::assertTrue(\method_exists(TransliteratorPolyfill::class, 'transliterate'));
    }

    public function testObjectWrapperCanBeCreatedForSupportedIds(): void
    {
        $transliterator = Transliterator::create('Latin-ASCII');

        static::assertInstanceOf(Transliterator::class, $transliterator);
        static::assertSame('Latin-ASCII', $transliterator->getId());
    }

    public function testObjectWrapperSupportsMethodAndStaticFacadeUsage(): void
    {
        $transliterator = Transliterator::create('Latin-ASCII');

        static::assertInstanceOf(Transliterator::class, $transliterator);
        static::assertSame('cafe', $transliterator->transliterate('café'));
        static::assertSame('cafe', TransliteratorPolyfill::transliterate($transliterator, 'café'));
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
