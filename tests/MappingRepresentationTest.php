<?php

declare(strict_types=1);

namespace voku\tests;

/** @internal */
final class MappingRepresentationTest extends \PHPUnit\Framework\TestCase
{
    public function testEveryMappingRetainsItsExactKeyValueAndOrderContract(): void
    {
        $contracts = \json_decode((string) \file_get_contents(__DIR__ . '/fixtures/mapping-contract.json'), true);
        $files = \glob(__DIR__ . '/../src/voku/helper/data/*.php');
        static::assertCount(\count($contracts), $files);
        foreach ($files as $file) {
            $map = include $file;
            $contract = $contracts[\basename($file)];
            static::assertCount($contract['entries'], $map, $file);
            static::assertSame($contract['sha256'], \hash('sha256', \serialize($map)), $file);
        }
    }

    public function testByteTableMatchesNativeOrdIncludingNumericStringKeys(): void
    {
        $map = include __DIR__ . '/../src/voku/helper/data/ascii_ord.php';
        for ($byte = 0; $byte < 256; ++$byte) {
            static::assertSame($byte, $map[\chr($byte)]);
        }
        static::assertSame(0, $map['']);
        static::assertArrayNotHasKey('ab', $map);
    }
}
