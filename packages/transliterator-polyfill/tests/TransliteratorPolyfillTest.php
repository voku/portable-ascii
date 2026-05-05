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
}
