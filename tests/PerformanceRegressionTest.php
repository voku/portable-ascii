<?php

declare(strict_types=1);

namespace voku\tests;

use voku\helper\ASCII;

/**
 * @internal
 */
final class PerformanceRegressionTest extends \PHPUnit\Framework\TestCase
{
    private const PERFORMANCE_ENV = 'PORTABLE_ASCII_RUN_PERFORMANCE_TESTS';

    public function testRepresentativeInputsStayCorrectUnderRepeatedCalls(): void
    {
        foreach (self::repeatedScenarioProvider() as $label => $scenario) {
            $result = null;

            for ($i = 0; $i < $scenario['iterations']; ++$i) {
                /** @var string $result */
                $result = \call_user_func_array([ASCII::class, $scenario['method']], $scenario['arguments']);
            }

            static::assertSame($scenario['expected'], $result, 'tested: ' . $label);
        }
    }

    /**
     * Opt-in benchmark test.
     *
     * Run with:
     * PORTABLE_ASCII_RUN_PERFORMANCE_TESTS=1 ./vendor/bin/phpunit --filter PerformanceRegressionTest
     */
    public function testPerformanceRatiosForRepresentativeInputs(): void
    {
        if (\getenv(self::PERFORMANCE_ENV) !== '1') {
            $this->markTestSkipped('Set PORTABLE_ASCII_RUN_PERFORMANCE_TESTS=1 to run performance benchmarks.');
        }

        $asciiShort = 'Plain ASCII text 123 test';
        $asciiLong = \str_repeat('Plain ASCII text 123 test ', 128);
        $greekLong = \str_repeat('Αυτή είναι μια δοκιμή ', 128);
        $myanmarLong = \str_repeat('တတျနိုငျသ ', 128);
        $chineseLong = \str_repeat('中文空白測試 ', 128);

        $benchmarks = [
            'to_ascii_ascii_short' => $this->benchmarkScenario(
                function () use ($asciiShort): string {
                    return ASCII::to_ascii($asciiShort, 'en', true);
                },
                25000
            ),
            'to_transliterate_ascii_short' => $this->benchmarkScenario(
                function () use ($asciiShort): string {
                    return ASCII::to_transliterate($asciiShort, '?', false);
                },
                25000
            ),
            'to_ascii_ascii_long' => $this->benchmarkScenario(
                function () use ($asciiLong): string {
                    return ASCII::to_ascii($asciiLong, 'en', true);
                },
                5000
            ),
            'to_transliterate_ascii_long' => $this->benchmarkScenario(
                function () use ($asciiLong): string {
                    return ASCII::to_transliterate($asciiLong, '?', false);
                },
                5000
            ),
            'to_ascii_greek_long' => $this->benchmarkScenario(
                function () use ($greekLong): string {
                    return ASCII::to_ascii($greekLong, 'el', true);
                },
                1500
            ),
            'to_ascii_greek_long_single_char_only' => $this->benchmarkScenario(
                function () use ($greekLong): string {
                    return ASCII::to_ascii($greekLong, 'el', true, false, false, true);
                },
                1500
            ),
            'to_ascii_myanmar_long' => $this->benchmarkScenario(
                function () use ($myanmarLong): string {
                    return ASCII::to_ascii($myanmarLong, 'my', true);
                },
                1000
            ),
            'to_ascii_myanmar_long_single_char_only' => $this->benchmarkScenario(
                function () use ($myanmarLong): string {
                    return ASCII::to_ascii($myanmarLong, 'my', true, false, false, true);
                },
                1000
            ),
            'to_ascii_chinese_long_transliterate' => $this->benchmarkScenario(
                function () use ($chineseLong): string {
                    return ASCII::to_ascii($chineseLong, 'en', true, false, true);
                },
                1000
            ),
            'to_transliterate_chinese_long' => $this->benchmarkScenario(
                function () use ($chineseLong): string {
                    return ASCII::to_transliterate($chineseLong, '?', false);
                },
                1000
            ),
        ];

        $this->writeBenchmarks($benchmarks);

        static::assertLessThan(
            6.0,
            $benchmarks['to_ascii_ascii_short'] / $benchmarks['to_transliterate_ascii_short'],
            'ASCII-only to_ascii() became disproportionately slower than the ASCII fast path in to_transliterate().'
        );
        static::assertLessThan(
            6.0,
            $benchmarks['to_ascii_ascii_long'] / $benchmarks['to_transliterate_ascii_long'],
            'Long ASCII-only to_ascii() inputs regressed relative to to_transliterate().'
        );
        static::assertLessThan(
            6.0,
            $benchmarks['to_ascii_greek_long'] / $benchmarks['to_ascii_greek_long_single_char_only'],
            'Default multi-character Greek replacements became disproportionately expensive.'
        );
        static::assertLessThan(
            10.0,
            $benchmarks['to_ascii_myanmar_long'] / $benchmarks['to_ascii_myanmar_long_single_char_only'],
            'Default multi-character Myanmar replacements became disproportionately expensive.'
        );
        static::assertLessThan(
            3.0,
            $benchmarks['to_ascii_chinese_long_transliterate'] / $benchmarks['to_transliterate_chinese_long'],
            'The transliteration fallback path inside to_ascii() regressed relative to direct to_transliterate().'
        );
    }

    public static function repeatedScenarioProvider(): array
    {
        return [
            'to_ascii ascii long' => [
                'method' => 'to_ascii',
                'arguments' => [
                    \str_repeat('Plain ASCII text 123 test ', 64),
                    'en',
                    true,
                ],
                'expected' => \str_repeat('Plain ASCII text 123 test ', 64),
                'iterations' => 150,
            ],
            'to_ascii latin long' => [
                'method' => 'to_ascii',
                'arguments' => [
                    \str_repeat('Déjà vu für Köln Straße ', 64),
                    'de',
                    true,
                ],
                'expected' => \str_repeat('Deja vu fuer Koeln Strasse ', 64),
                'iterations' => 75,
            ],
            'to_ascii greek long' => [
                'method' => 'to_ascii',
                'arguments' => [
                    \str_repeat('Αυτή είναι μια δοκιμή ', 32),
                    'el',
                    true,
                ],
                'expected' => \str_repeat('Auti inai mia dokimi ', 32),
                'iterations' => 50,
            ],
            'to_ascii myanmar long' => [
                'method' => 'to_ascii',
                'arguments' => [
                    \str_repeat('တတျနိုငျသ ', 32),
                    'my',
                    true,
                ],
                'expected' => \str_repeat('ttyanongyath ', 32),
                'iterations' => 50,
            ],
            'to_transliterate chinese long' => [
                'method' => 'to_transliterate',
                'arguments' => [
                    \str_repeat('中文空白 ', 32),
                    '?',
                    false,
                ],
                'expected' => \str_repeat('Zhong Wen Kong Bai  ', 32),
                'iterations' => 50,
            ],
        ];
    }

    /**
     * @param callable():string $callback
     */
    private function benchmarkScenario(callable $callback, int $iterations, int $rounds = 5): float
    {
        $samples = [];

        for ($round = 0; $round < $rounds; ++$round) {
            for ($warmup = 0; $warmup < 3; ++$warmup) {
                $callback();
            }

            $start = \microtime(true);

            for ($i = 0; $i < $iterations; ++$i) {
                $callback();
            }

            $samples[] = (\microtime(true) - $start) * 1000000 / $iterations;
        }

        \sort($samples);

        return $samples[(int) \floor(\count($samples) / 2)];
    }

    /**
     * @param array<string, float> $benchmarks
     */
    private function writeBenchmarks(array $benchmarks): void
    {
        if (!\defined('STDERR')) {
            return;
        }

        \fwrite(\STDERR, "\nportable-ascii performance profile (median us/op)\n");

        foreach ($benchmarks as $label => $microseconds) {
            \fwrite(\STDERR, \sprintf("- %-42s %9.3f\n", $label, $microseconds));
        }

        \fwrite(\STDERR, "\n");
    }
}
