<?php

declare(strict_types=1);

namespace voku\tests;

/** @internal */
final class MappingRepresentationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Verify every baseline mapping using the provider's deliberately static contract.
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function testEveryMappingRetainsItsExactKeyValueAndOrderContract(): void
    {
        $contracts = \json_decode((string) \file_get_contents(__DIR__ . '/fixtures/mapping-contract.json'), true);
        $files = \glob(__DIR__ . '/../src/voku/helper/data/*.php');
        $extracted = \array_filter(\array_keys($contracts), static function (string $name): bool {
            return $name[0] === 'x';
        });
        static::assertCount(\count($contracts) - \count($extracted), $files);
        foreach ($files as $file) {
            $map = include $file; // NOSONAR: reload array data; include_once returns true after another test loads it.
            $contract = $contracts[\basename($file)];
            static::assertCount($contract['entries'], $map, $file);
            static::assertSame($contract['sha256'], \hash('sha256', \serialize($map)), $file);
        }
        foreach ($extracted as $name) {
            $map = \Voku\PortableAsciiMappingData\BankMap::load(\substr($name, 0, -4));
            static::assertCount($contracts[$name]['entries'], $map, $name);
            static::assertSame($contracts[$name]['sha256'], \hash('sha256', \serialize($map)), $name);
        }
    }

    /** Check every byte and the legacy empty-string key against the original contract. */
    public function testByteTableMatchesNativeOrdIncludingNumericStringKeys(): void
    {
        $map = include __DIR__ . '/../src/voku/helper/data/ascii_ord.php'; // NOSONAR: reload array data; include_once returns true after another test loads it.
        for ($byte = 0; $byte < 256; ++$byte) {
            static::assertSame($byte, $map[\chr($byte)]);
        }
        static::assertSame(0, $map['']);
        static::assertArrayNotHasKey('ab', $map);
    }
}
