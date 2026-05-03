<?php

declare(strict_types=1);

namespace voku\tests;

use voku\helper\ASCII;

/**
 * @internal
 */
final class AsciiTest extends \PHPUnit\Framework\TestCase
{
    private const TIMEOUT_SECONDS = 1;

    public function testToAsciiRemap()
    {
        static::assertSame(['testi' . \chr(128) . 'g', 'testing'], ASCII::to_ascii_remap('testiñg', 'testing'));
    }

    public function testToAsciiRemapAssignsDistinctBytesPerUniqueMultibyteCharacter()
    {
        static::assertSame(
            ['a' . \chr(128) . \chr(129) . \chr(128), \chr(129) . \chr(128) . 'a'],
            ASCII::to_ascii_remap('añ😀ñ', '😀ña')
        );
    }

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

    public function testToAsciiEmptyLanguage()
    {
        $testsStrict = [
            ' '                                        => ' ',
            ''                                         => '',
            'أبز'                                      => 'abz',
            "\xe2\x80\x99"                             => '\'',
            'Ɓtest'                                    => 'Btest',
            '  -ABC-中文空白-  '                           => '  -ABC-Zhong Wen Kong Bai -  ',
            "      - abc- \xc2\x87"                    => '      - abc- ++',
            'STRAẞE'                                   => 'STRASSE',
            'abc'                                      => 'abc',
            'deja vu'                                  => 'deja vu',
            'déjà vu'                                  => 'deja vu',
            'déjà σσς iıii'                            => 'deja sss iiii',
            "test\x80-\xBFöäü"                         => 'test-oau',
            'Internationalizaetion'                    => 'Internationalizaetion',
            "中 - &#20013; - %&? - \xc2\x80"            => 'Zhong  - &#20013; - %&? - EUR',
            'Un été brûlant sur la côte'               => 'Un ete brulant sur la cote',
            'Αυτή είναι μια δοκιμή'                    => 'Auti inai mia dokimi',
            'أحبك'                                     => 'ahbk',
            'キャンパス'                                    => 'kiyanpasu',
            'биологическом'                            => 'biologiceskom',
            '정, 병호'                                    => 'jeong, byeongho',
            'ますだ, よしひこ'                                => 'masuda, yoshihiko',
            'मोनिच'                                    => 'MaoNaiCa',
            'क्षȸ'                                     => 'KaShhadb',
            'أحبك 😀'                                   => 'ahbk ',
            'ذرزسشصضطظعغػؼؽؾؿ 5.99€'                   => 'thrzsshsdtthaagh 5.99EUR',
            'ذرزسشصضطظعغػؼؽؾؿ £5.99'                   => 'thrzsshsdtthaagh PS5.99',
            '׆אבגדהוזחטיךכלםמן $5.99'                  => 'nAbgdhvzKHtykklmmn $5.99',
            '日一国会人年大十二本中長出三同 ¥5990'                    => 'Ri Yi Guo Hui Ren Nian Da Shi Er Ben Zhong Chang Chu San Tong  YEN5990',
            '5.99€ 日一国会人年大十 $5.99'                     => '5.99EUR Ri Yi Guo Hui Ren Nian Da Shi  $5.99',
            'בגדה@ضطظعغػ.com'                          => 'bgdh@dtthaagh.com',
            '年大十@ضطظعغػ'                               => 'Nian Da Shi @dtthaagh',
            'בגדה & 年大十'                               => 'bgdh & Nian Da Shi ',
            '国&ם at ضطظعغػ.הוז'                        => 'Guo &m at dtthaagh.hvz',
            'my username is @בגדה'                     => 'my username is @bgdh',
            'The review gave 5* to ظعغػ'               => 'The review gave 5* to thaagh',
            'use 年大十@ضطظعغػ.הוז to get a 10% discount' => 'use Nian Da Shi @dtthaagh.hvz to get a 10% discount',
            '日 = הط^2'                                 => 'Ri  = ht^2',
            'ךכלם 国会 غػؼؽ 9.81 m/s2'                   => 'kklm Guo Hui  gh 9.81 m/s2',
            'The #会 comment at @בגדה = 10% of *&*'     => 'The #Hui  comment at @bgdh = 10% of *&*',
            '∀ i ∈ ℕ'                                  => ' i  N',
            '👍 💩 😄 ❤ 👍 💩 😄 ❤أحبك'                      => '       ahbk',
            'আমি'                                      => 'ami',
        ];

        foreach ($testsStrict as $before => $after) {
            static::assertSame($after, ASCII::to_ascii($before, ''), 'tested: ' . $before);
        }
    }

    public function testToAscii()
    {
        $testsStrict = [
            ' '                                        => ' ',
            ''                                         => '',
            'أبز'                                      => 'abz',
            "\xe2\x80\x99"                             => '\'',
            'Ɓtest'                                    => 'Btest',
            '  -ABC-中文空白-  '                           => '  -ABC--  ',
            "      - abc- \xc2\x87"                    => '      - abc- ',
            'STRAẞE'                                   => 'STRASSE',
            'abc'                                      => 'abc',
            'deja vu'                                  => 'deja vu',
            'déjà vu'                                  => 'deja vu',
            'déjà σσς iıii'                            => 'deja sss iiii',
            "test\x80-\xBFöäü"                         => 'test-',
            'Internationalizaetion'                    => 'Internationalizaetion',
            "中 - &#20013; - %&? - \xc2\x80"            => ' - &#20013; - %&? - ',
            'Un été brûlant sur la côte'               => 'Un ete brulant sur la cote',
            'Αυτή είναι μια δοκιμή'                    => 'Auti inai mia dokimi',
            'أحبك'                                     => 'ahbk',
            'キャンパス'                                    => '',
            'биологическом'                            => 'biologiceskom',
            '정, 병호'                                    => ', ',
            'ますだ, よしひこ'                                => ', ',
            'मोनिच'                                    => 'MaNaCa',
            'क्षȸ'                                     => 'KaShhadb',
            'أحبك 😀'                                   => 'ahbk ',
            'ذرزسشصضطظعغػؼؽؾؿ 5.99€'                   => 'thrzsshsdtthaagh 5.99EUR',
            'ذرزسشصضطظعغػؼؽؾؿ £5.99'                   => 'thrzsshsdtthaagh PS5.99',
            '׆אבגדהוזחטיךכלםמן $5.99'                  => ' $5.99',
            '日一国会人年大十二本中長出三同 ¥5990'                    => ' YEN5990',
            '5.99€ 日一国会人年大十 $5.99'                     => '5.99EUR  $5.99',
            'בגדה@ضطظعغػ.com'                          => '@dtthaagh.com',
            '年大十@ضطظعغػ'                               => '@dtthaagh',
            'בגדה & 年大十'                               => ' & ',
            '国&ם at ضطظعغػ.הוז'                        => '& at dtthaagh.',
            'my username is @בגדה'                     => 'my username is @',
            'The review gave 5* to ظعغػ'               => 'The review gave 5* to thaagh',
            'use 年大十@ضطظعغػ.הוז to get a 10% discount' => 'use @dtthaagh. to get a 10% discount',
            '日 = הط^2'                                 => ' = t^2',
            'ךכלם 国会 غػؼؽ 9.81 m/s2'                   => '  gh 9.81 m/s2',
            'The #会 comment at @בגדה = 10% of *&*'     => 'The # comment at @ = 10% of *&*',
            '∀ i ∈ ℕ'                                  => ' i  ',
            '👍 💩 😄 ❤ 👍 💩 😄 ❤أحبك'                      => '       ahbk',
            'আমি   '                                   => 'ami   ',
        ];

        foreach ($testsStrict as $before => $after) {
            static::assertSame($after, ASCII::to_ascii($before, 'en', true), 'tested: ' . $before);
        }
    }

    public function testToAsciiHandlesMixedAsciiAndNonAsciiMapKeysAcrossLengthBuckets()
    {
        $short = \str_repeat('A̧', 10);
        $medium = \str_repeat('A̧', 30);

        static::assertSame(\str_repeat('A', 10), ASCII::to_ascii($short, '', false), 'tested: short mixed key');
        static::assertSame(\str_repeat('A', 30), ASCII::to_ascii($medium, '', false), 'tested: medium mixed key');
    }

    public function testToAsciiHandlesAdditionalMixedAsciiAndNonAsciiMapKeysAtLengthBoundary()
    {
        $cases = [
            '63-byte grave-accent C key' => [
                'input' => \str_repeat('C̀', 21),
                'expected' => \str_repeat('C', 21),
            ],
            '66-byte diaeresis c key' => [
                'input' => \str_repeat('c̈', 22),
                'expected' => \str_repeat('c', 22),
            ],
            '63-byte dot-above U key' => [
                'input' => \str_repeat('U̇', 21),
                'expected' => \str_repeat('U', 21),
            ],
            '66-byte cedilla u key' => [
                'input' => \str_repeat('u̧', 22),
                'expected' => \str_repeat('u', 22),
            ],
        ];

        foreach ($cases as $label => $case) {
            for ($pass = 1; $pass <= 2; ++$pass) {
                static::assertSame(
                    $case['expected'],
                    ASCII::to_ascii($case['input'], '', false),
                    $label . ' pass ' . $pass
                );
            }
        }
    }

    public function testToAsciiShortcutBranchesStayCorrectAcrossRepeatedCalls()
    {
        $cases = [
            'pure printable ASCII fast path' => [
                'arguments' => ['Plain ASCII text 123 test', 'en', true],
                'expected' => 'Plain ASCII text 123 test',
            ],
            'secondary 7-bit path without cleanup' => [
                'arguments' => ["a\n\tb\rc", 'en', false],
                'expected' => "a\n\tb\rc",
            ],
            'secondary 7-bit path with cleanup' => [
                'arguments' => ["a\n\tb\rc", 'en', true],
                'expected' => 'a  b c',
            ],
            'short single-char replacement path' => [
                'arguments' => ['Düsseldorf', 'de', true],
                'expected' => 'Duesseldorf',
            ],
            'short filtered-map path' => [
                'arguments' => ['déjà σσς iıii', 'en', true],
                'expected' => 'deja sss iiii',
            ],
            'invalid UTF-8 fallback path' => [
                'arguments' => ["a\xC0\xAFb", 'en', true],
                'expected' => 'ab',
            ],
            'english transliteration shortcut for non-latin input' => [
                'arguments' => ['中文空白測試', 'en', true, false, true],
                'expected' => 'Zhong Wen Kong Bai Ce Shi ',
            ],
        ];

        foreach ($cases as $label => $scenario) {
            for ($pass = 1; $pass <= 2; ++$pass) {
                static::assertSame(
                    $scenario['expected'],
                    \call_user_func_array([ASCII::class, 'to_ascii'], $scenario['arguments']),
                    $label . ' pass ' . $pass
                );
            }
        }
    }

    public function testToAsciiLongSevenBitOnlyPathStaysCorrectAcrossRepeatedCalls()
    {
        $input = \str_repeat("a\n\tb\rc\x7F", 20);

        static::assertSame($input, ASCII::to_ascii($input, 'en', false), 'without cleanup');
        static::assertSame($input, ASCII::to_ascii($input, 'en', false), 'without cleanup warm');

        static::assertSame(\str_repeat('a  b c', 20), ASCII::to_ascii($input, 'en', true), 'with cleanup');
        static::assertSame(\str_repeat('a  b c', 20), ASCII::to_ascii($input, 'en', true), 'with cleanup warm');
    }
    
    public function testToAsciiLongDefaultPathDropsDegreeSymbolAcrossRepeatedCalls()
    {
        $input = 'Webinaire des transitions n°34 - Agir et mobiliser pour la biodiversité dans son entreprise';
        $expected = 'Webinaire des transitions n34 - Agir et mobiliser pour la biodiversite dans son entreprise';

        // Warm the transliteration and extra-symbol paths first to ensure they do
        // not leak degree-sign expansion into the default cleanup-only path.
        static::assertSame(
            'Webinaire des transitions ndeg34 - Agir et mobiliser pour la biodiversite dans son entreprise',
            ASCII::to_ascii($input, 'en', true, false, true),
            'transliteration warmup'
        );
        static::assertSame('20 Celsius ', ASCII::to_ascii('20°C', 'temperature', false, true), 'extra-symbol warmup');

        static::assertSame($expected, ASCII::to_ascii($input, 'en'), 'with cleanup');
        static::assertSame($expected, ASCII::to_ascii($input, 'en'), 'with cleanup warm');
    }

    public function testToAsciiLongEnglishTransliterationShortcutStaysCorrectAcrossRepeatedCalls()
    {
        $input = \str_repeat('中文空白測試 ', 8);
        $expected = \str_repeat('Zhong Wen Kong Bai Ce Shi  ', 8);

        static::assertSame($expected, ASCII::to_ascii($input, 'en', true, false, true), 'cold');
        static::assertSame($expected, ASCII::to_ascii($input, 'en', true, false, true), 'warm');
    }

    public function testToAsciiLongTransliterationExpandsDegreeSymbolAcrossRepeatedCalls()
    {
        // Regression test for https://github.com/voku/portable-ascii/issues/135
        // The transliteration-enabled path should keep expanding "°" to "deg"
        // regardless of the order in which other long-string paths were warmed.
        $input = 'Webinaire des transitions n°34 - Agir et mobiliser pour la biodiversité dans son entreprise';
        $expected = 'Webinaire des transitions ndeg34 - Agir et mobiliser pour la biodiversite dans son entreprise';

        static::assertSame(
            'Webinaire des transitions n34 - Agir et mobiliser pour la biodiversite dans son entreprise',
            ASCII::to_ascii($input, 'en'),
            'default-path warmup'
        );

        static::assertSame($expected, ASCII::to_ascii($input, 'en', true, false, true), 'first transliteration call');
        static::assertSame($expected, ASCII::to_ascii($input, 'en', true, false, true), 'second transliteration call (warm)');
    }

    public function testToAsciiLongRetentionPathKeepsDegreeSymbolAcrossRepeatedCalls()
    {
        $input = 'Webinaire des transitions n°34 - Agir et mobiliser pour la biodiversité dans son entreprise';
        $expected = 'Webinaire des transitions n°34 - Agir et mobiliser pour la biodiversite dans son entreprise';

        static::assertSame(
            'Webinaire des transitions ndeg34 - Agir et mobiliser pour la biodiversite dans son entreprise',
            ASCII::to_ascii($input, 'en', true, false, true),
            'transliteration warmup'
        );

        static::assertSame($expected, ASCII::to_ascii($input, 'en', false), 'first retention call');
        static::assertSame($expected, ASCII::to_ascii($input, 'en', false), 'second retention call (warm)');
    }

    public function testToAsciiUsesLanguageSpecificMultiCharacterMappings()
    {
        static::assertSame('EF', ASCII::to_ascii('ΕΥ', 'el', false), 'cold');
        static::assertSame('EF', ASCII::to_ascii('ΕΥ', 'el', false), 'warm');
    }

    public function testToAsciiUsesFiveCodepointLanguageSpecificMappings()
    {
        static::assertSame('nub', ASCII::to_ascii('န်ုပ်', 'my', false), 'cold');
        static::assertSame('nub', ASCII::to_ascii('န်ုပ်', 'my', false), 'warm');
    }

    public function testRemoveInvisibleCharactersHandlesCleanStrings()
    {
        $this->assertRemoveInvisibleCharactersSame('already clean', 'already clean');
    }

    public function testRemoveInvisibleCharactersHandlesReplacementMatchingPattern()
    {
        $this->assertRemoveInvisibleCharactersSame("\0a", "\0a", false, "\0");
        $this->assertRemoveInvisibleCharactersSame('%00a', '%00a', true, '%00');
    }

    public function testRemoveInvisibleCharacters()
    {
        $testArray = [
            "κόσ\0με"                                                                          => 'κόσμε',
            "Κόσμε\x20"                                                                        => 'Κόσμε ',
            "öäü-κόσμ\x0εκόσμε-äöü"                                                            => 'öäü-κόσμεκόσμε-äöü',
            'öäü-κόσμεκόσμε-äöüöäü-κόσμεκόσμε-äöü'                                             => 'öäü-κόσμεκόσμε-äöüöäü-κόσμεκόσμε-äöü',
            'äöüäöüäöü-κόσμεκόσμεäöüäöüäöü-Κόσμεκόσμεäöüäöüäöü-κόσμεκόσμεäöüäöüäöü-κόσμεκόσμε' => 'äöüäöüäöü-κόσμεκόσμεäöüäöüäöü-Κόσμεκόσμεäöüäöüäöü-κόσμεκόσμεäöüäöüäöü-κόσμεκόσμε',
            '  '                                                                               => '  ',
            ''                                                                                 => '',
        ];

        foreach ($testArray as $before => $after) {
            $this->assertRemoveInvisibleCharactersSame($after, $before, false, '', true, 'error by ' . $before);
            $this->assertRemoveInvisibleCharactersSame($after, $before, true, '', true, 'error by ' . $before);
            $this->assertRemoveInvisibleCharactersSame($after, $before, false, '', false, 'error by ' . $before);
        }

        $this->assertRemoveInvisibleCharactersSame('äöüäöüäöü-κόσμεκόσμεäöüäöüäöü κόσμεκόσμεäöüäöüäöü-κόσμεκόσμε', "äöüäöüäöü-κόσμεκόσμεäöüäöüäöü\xe1\x9a\x80κόσμεκόσμεäöüäöüäöü-κόσμεκόσμε");

        $this->assertRemoveInvisibleCharactersSame('%*ł€! ‎| | ', '%*ł€! ‎| | ');
        $this->assertRemoveInvisibleCharactersSame('%*ł€! |' . "\n|\t " . "\t", '%*ł€! ‎| | ' . "\t", false, '', false);

        $this->assertRemoveInvisibleCharactersSame('κόσ?με 	%00 | tes%20öäü%20\u00edtest', "κόσ\0με 	%00 | tes%20öäü%20\u00edtest", false, '?');
        $this->assertRemoveInvisibleCharactersSame('κόσμε 	 | tes%20öäü%20\u00edtest', "κόσ\0με 	%00 | tes%20öäü%20\u00edtest", true, '');
        static::assertSame('%00foo', ASCII::remove_invisible_characters('%00foo'));
        static::assertSame('foo', ASCII::remove_invisible_characters('%00foo', true));
    }

    private function assertRemoveInvisibleCharactersSame(
        string $expected,
        string $input,
        bool $urlEncoded = false,
        string $replacement = '',
        bool $keepBasicControlCharacters = true,
        string $message = ''
    ): void {
        if ($this->isPcntlAvailable()) {
            \pcntl_async_signals(true);
            \pcntl_signal(\SIGALRM, static function (): void {
                throw new \RuntimeException('ASCII::remove_invisible_characters() timed out.');
            });
            \pcntl_alarm(self::TIMEOUT_SECONDS);
        }

        try {
            static::assertSame(
                $expected,
                ASCII::remove_invisible_characters($input, $urlEncoded, $replacement, $keepBasicControlCharacters),
                $message
            );
        } finally {
            if ($this->isPcntlAvailable()) {
                \pcntl_alarm(0);
                \pcntl_async_signals(false);
            }
        }
    }

    private function invokeToAsciiReplace(
        string $str,
        string $language,
        bool $replaceExtraSymbols,
        bool $replaceSingleCharsOnly,
        ?bool &$isValidUtf8
    ): string {
        $reflection = new \ReflectionClass(ASCII::class);
        $method = $reflection->getMethod('to_ascii_replace');
        $method->setAccessible(true);

        return $method->invokeArgs(null, [$str, $language, $replaceExtraSymbols, $replaceSingleCharsOnly, &$isValidUtf8]);
    }

    private function isPcntlAvailable(): bool
    {
        return \function_exists('pcntl_async_signals')
            && \function_exists('pcntl_alarm')
            && \function_exists('pcntl_signal');
    }

    public function testGetSupportedLanguages()
    {
        $languages = ASCII::getAllLanguages();

        static::assertArrayHasKey('german', $languages, \print_r($languages, true));
        static::assertSame('de', $languages['german']);
        static::assertArrayHasKey('extra_latin_chars_language_code', $languages, \print_r($languages, true));
        static::assertSame('latin', $languages['extra_latin_chars_language_code']);

        $languages = ASCII::getAllLanguages();
        static::assertSame('de', $languages['german']);
        static::assertSame('latin', $languages['extra_latin_chars_language_code']);
    }

    public function testInvalidCharToAscii()
    {
        $str = "tes\xe9ting";
        static::assertSame('testing', ASCII::to_transliterate($str));

        // ---

        $str = "tes\xe9ting";
        static::assertSame('testing', ASCII::to_ascii($str));
    }

    public function testMalformedUtf8ToAsciiViaTransliteration()
    {
        static::assertSame('', ASCII::to_ascii("\xC0\xAF", 'en', true, false, true));
        static::assertSame('', ASCII::to_ascii("\xED\xA0\x80", 'en', true, false, true));
    }

    /**
     * Overlong sequences and UTF-16 surrogate halves must never be decoded into
     * ASCII characters by to_transliterate() (or to_ascii() when transliteration
     * is used). The raw-byte regex UTF8_MULTIBYTE_SEQUENCE_RX now uses the same
     * strict grammar as clean(), so these sequences are never matched and therefore
     * cannot be fed to the ordinal arithmetic that would otherwise convert them into
     * their ASCII equivalents (e.g. "\xC0\xAF" → "/").
     */
    public function testOverlongAndSurrogateSequencesAreNotDecodedToAscii()
    {
        // --- 2-byte overlong sequences (C0/C1 lead bytes) ---
        // "\xC0\xAF" would decode to U+002F = "/" via (0xC0-0xC0)*64+(0xAF-0x80) without the fix
        static::assertSame('', ASCII::to_transliterate("\xC0\xAF", null, false));
        static::assertSame('', ASCII::to_transliterate("\xC1\x81", null, false));
        // must not silently become "/" with an unknown fallback either
        static::assertNotSame('/', ASCII::to_transliterate("\xC0\xAF", '?', false));

        // --- 3-byte overlong sequences (E0 + 80–9F …) ---
        static::assertSame('', ASCII::to_transliterate("\xE0\x80\xAF", null, false));
        static::assertSame('', ASCII::to_transliterate("\xE0\x9F\xBF", null, false));
        static::assertNotSame('/', ASCII::to_transliterate("\xE0\x80\xAF", '?', false));

        // --- 4-byte overlong sequences (F0 + 80–8F …) ---
        static::assertSame('', ASCII::to_transliterate("\xF0\x80\x80\x80", null, false));
        static::assertSame('', ASCII::to_transliterate("\xF0\x8F\xBF\xBF", null, false));

        // --- UTF-16 surrogate halves (ED A0–BF …) ---
        static::assertSame('', ASCII::to_transliterate("\xED\xA0\x80", null, false)); // U+D800
        static::assertSame('', ASCII::to_transliterate("\xED\xBF\xBF", null, false)); // U+DFFF

        // --- Code points above U+10FFFF ---
        static::assertSame('', ASCII::to_transliterate("\xF4\x90\x80\x80", null, false));

        // --- Mixed: invalid bytes embedded in otherwise valid ASCII ---
        static::assertSame('hello world', ASCII::to_transliterate("hello\xC0\xAF world", null, false));
        static::assertSame('hello world', ASCII::to_transliterate("hello\xE0\x80\xAF world", null, false));

        // --- Sanity: valid sequences still transliterate correctly ---
        static::assertSame('deja vu', ASCII::to_transliterate('déjà vu', null, false));
        static::assertSame('/', ASCII::to_transliterate('/', null, false));
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

        $str = "\xc2\x92\x00\n\x01\n\x7f\xe2\x80\x99";
        static::assertSame("'\n\n'", ASCII::to_transliterate($str));

        // ---

        $str = "a\nb\nc";
        static::assertSame("a\nb\nc", ASCII::to_ascii($str, 'en', false));

        // ---

        $str = "a\nb\nc";
        static::assertSame('a b c', ASCII::to_ascii($str, 'en', true));

        // ---

        $str = 'ä-ö-ü';
        static::assertSame('ae-oe-ue', ASCII::to_ascii($str, 'de', true));
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

    public function testToAsciiSingleCharOnlyUsesExpectedMappings()
    {
        static::assertSame('Aiti einai mia dokimi ', ASCII::to_ascii('Αυτή είναι μια δοκιμή ', 'el', true, false, false, true));
        static::assertSame('ttyaniungyath ', ASCII::to_ascii('တတျနိုငျသ ', 'my', true, false, false, true));
    }

    /**
     * When language='' or 'en', to_ascii() merges ALL language replacement maps,
     * which includes Myanmar (my, maxKey=5) and Bengali (bn, maxKey=5).  The old code
     * read maxKeyLength from ascii_language_max_key for 'en' (=0) / '' (missing, also 0)
     * and only bumped that value to 2 — causing the 3/4/5-char replacement loops to be
     * skipped entirely, so multi-character Myanmar/Bengali ligatures were transliterated
     * character-by-character and produced wrong output instead of the correct merged-map
     * replacement.
     *
     * Expected fix: when the requested language causes the full merged map to be used
     * (language='' or 'en'), maxKeyLength should reflect the true maximum key length
     * present in that merged map (5).
     */
    public function testMergedLanguageMapUsesCorrectMaxKeyLength()
    {
        // Myanmar 4-char ligature "ောင်" (e180b1 e180ac e18084 e180ba) => "aung"
        // With language='my' this works.  With 'en' or '' the merged map still
        // contains the key, but the maxKeyLength gate previously prevented it.
        static::assertSame(
            'aung',
            ASCII::to_ascii('ောင်', 'en', false),
            'Myanmar 4-char key must be found when language=en uses merged map'
        );
        static::assertSame(
            'aung',
            ASCII::to_ascii('ောင်', '', false),
            'Myanmar 4-char key must be found when language="" uses merged map'
        );

        // Myanmar 5-char ligature "န်ုပ်" => "nub"
        static::assertSame(
            'nub',
            ASCII::to_ascii('န်ုပ်', 'en', false),
            'Myanmar 5-char key must be found when language=en uses merged map'
        );
        static::assertSame(
            'nub',
            ASCII::to_ascii('န်ုပ်', '', false),
            'Myanmar 5-char key must be found when language="" uses merged map'
        );

        // Bengali 3-char ligature "ভ্ল" => "vl"
        static::assertSame(
            'vl',
            ASCII::to_ascii('ভ্ল', 'en', false),
            'Bengali 3-char key must be found when language=en uses merged map'
        );
        static::assertSame(
            'vl',
            ASCII::to_ascii('ভ্ল', '', false),
            'Bengali 3-char key must be found when language="" uses merged map'
        );
    }

    /**
     * The extra-symbols replacement map (replace_extra_symbols=true) contains keys
     * up to 3 characters long (e.g. temperature units: "°De" => " Delisle ",
     * "°Re" => " Reaumur ", "°Ro" => " Romer ").
     *
     * The old code bumped maxKeyLength to at most 2 when replace_extra_symbols=true,
     * so 3-character extra-symbol keys were silently skipped and the string was
     * processed character-by-character instead, yielding "degDe" instead of
     * " Delisle ".
     *
     * Expected fix: when replace_extra_symbols=true, maxKeyLength must be at least 3
     * to accommodate the longest extra-symbol keys.
     */
    public function testExtraSymbolsThreeCharKeysAreReplaced()
    {
        // "°De" (U+00B0 + "De") is a 3-char key in the temperature extras map.
        static::assertSame(
            '350 Delisle ',
            ASCII::to_ascii('350°De', 'temperature', false, true),
            '3-char extra-symbol key °De must be replaced when replace_extra_symbols=true'
        );
        static::assertSame(
            '100 Reaumur ',
            ASCII::to_ascii('100°Re', 'temperature', false, true),
            '3-char extra-symbol key °Re must be replaced when replace_extra_symbols=true'
        );
        static::assertSame(
            '0 Romer ',
            ASCII::to_ascii('0°Ro', 'temperature', false, true),
            '3-char extra-symbol key °Ro must be replaced when replace_extra_symbols=true'
        );

        // Sanity: 2-char extra-symbol keys should still work as before.
        static::assertSame(
            '20 Celsius ',
            ASCII::to_ascii('20°C', 'temperature', false, true),
            '2-char extra-symbol key °C must still be replaced'
        );
        static::assertSame(
            '98 Fahrenheit ',
            ASCII::to_ascii('98°F', 'temperature', false, true),
            '2-char extra-symbol key °F must still be replaced'
        );
    }

    /**
     * ASCII::clean() must strip every byte-sequence that is not valid UTF-8
     * per RFC 3629, while preserving sequences that are valid.
     *
     * Categories covered:
     *  - C0/C1 overlong double-byte: security classic (e.g. C0 AF → "/" bypass)
     *  - E0 80..9F overlong triple-byte: known attack vector against BMP chars
     *  - ED A0..BF UTF-16 surrogates: can crash JSON parsers / PCRE
     *  - F0 80..8F overlong quad-byte
     *  - F5–F7 completely outside Unicode range
     *  - Valid sequences must survive unchanged (regression guard)
     *  - Mixed strings: invalid bytes removed, valid bytes kept
     *
     * @dataProvider provideInvalidUtf8Sequences
     */
    public function testCleanRemovesInvalidUtf8Sequences(string $input, string $expected): void
    {
        static::assertSame($expected, ASCII::clean($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideInvalidUtf8Sequences(): array
    {
        return [
            // --- Overlong double-byte (C0/C1 are never valid lead bytes) ---
            'overlong C0 80 (overlong U+0000)' => ["\xC0\x80", ''],
            'overlong C1 BF (overlong U+007F)' => ["\xC1\xBF", ''],

            // --- Overlong triple-byte via E0 (second byte must be A0..BF) ---
            'overlong E0 80 80 (overlong U+0000)' => ["\xE0\x80\x80", ''],
            'overlong E0 9F BF (overlong U+07FF)' => ["\xE0\x9F\xBF", ''],

            // --- UTF-16 surrogates via ED (second byte must be 80..9F) ---
            'surrogate ED A0 80 (U+D800)' => ["\xED\xA0\x80", ''],
            'surrogate ED BF BF (U+DFFF)' => ["\xED\xBF\xBF", ''],

            // --- Overlong quad-byte via F0 (second byte must be 90..BF) ---
            'overlong F0 80 80 80 (overlong U+0000)' => ["\xF0\x80\x80\x80", ''],
            'overlong F0 8F BF BF (overlong U+FFFF)' => ["\xF0\x8F\xBF\xBF", ''],

            // --- Out-of-range quad-byte: F5–F7 exceed U+10FFFF ---
            'out-of-range F5 80 80 80' => ["\xF5\x80\x80\x80", ''],
            'out-of-range F7 BF BF BF' => ["\xF7\xBF\xBF\xBF", ''],

            // --- Valid sequences must be preserved (regression guard) ---
            'valid ascii'                  => ['Hello', 'Hello'],
            'valid 2-byte U+00C4 (Ä)'      => ["\xC3\x84", "\xC3\x84"],
            'valid 3-byte U+20AC (€)'      => ["\xE2\x82\xAC", "\xE2\x82\xAC"],
            'valid 4-byte U+1F600 (😀)'    => ["\xF0\x9F\x98\x80", "\xF0\x9F\x98\x80"],
            'valid max codepoint U+10FFFF' => ["\xF4\x8F\xBF\xBF", "\xF4\x8F\xBF\xBF"],

            // --- Mixed strings: invalid bytes removed, valid bytes kept ---
            'mixed valid and overlong C0'     => ["Hello\xC0\x80World", 'HelloWorld'],
            'mixed valid and surrogate ED'    => ["\xED\xA0\x80Hallo", 'Hallo'],
            'mixed valid and out-of-range F5' => ["Test\xF5\x80\x80\x80End", 'TestEnd'],
        ];
    }

    public function testToSlugifyWithCustomSeparator()
    {
        // Test with underscore separator
        static::assertSame('hello_world', ASCII::to_slugify('Hello World', '_'));

        // Test with underscore and no lowercase (CamelCase)
        // 'CamelCase' -> 'Camel-Case' -> 'Camel_Case'
        static::assertSame('Camel_Case', ASCII::to_slugify('CamelCase', '_', 'en', [], false, false));
    }

    public function testToSlugifyWithSpecialCharsInSeparator()
    {
        // Test with separator that needs regex quoting (e.g. '.')
        static::assertSame('hello.world', ASCII::to_slugify('Hello World', '.'));
        static::assertSame('Camel.Case', ASCII::to_slugify('CamelCase', '.', 'en', [], false, false));
    }

    public function testToSlugifyEnglishDefaultDoesNotStartTransliteratingDroppedUnicode()
    {
        // Regression guard: a pure-ASCII fast path in to_slugify() must not
        // change the behavior for English inputs that still contain Unicode.
        static::assertSame('a', ASCII::to_slugify('A中'));
        static::assertSame('coxi', ASCII::to_slugify('ç😊中ö!xi'));
        static::assertSame('iaxzozc-ezssa-ups', ASCII::to_slugify("İäxzözC é🚀Жßà-ü£\n"));
    }

    public function testToSlugifyShortcutBranchesStayCorrectAcrossRepeatedCalls()
    {
        $cases = [
            'pure printable ASCII English shortcut' => [
                'arguments' => ['Using strings like foo bar'],
                'expected' => 'using-strings-like-foo-bar',
            ],
            'localized slugify path still goes through transliteration' => [
                'arguments' => ['Fußgängerübergänge in Düsseldorf Altstadt', '-', 'de'],
                'expected' => 'fussgaengeruebergaenge-in-duesseldorf-altstadt',
            ],
            'english unicode input still follows legacy dropping rules' => [
                'arguments' => ['A中'],
                'expected' => 'a',
            ],
        ];

        foreach ($cases as $label => $scenario) {
            for ($pass = 1; $pass <= 2; ++$pass) {
                static::assertSame(
                    $scenario['expected'],
                    \call_user_func_array([ASCII::class, 'to_slugify'], $scenario['arguments']),
                    $label . ' pass ' . $pass
                );
            }
        }
    }

    public function testToSlugifyReplacesAtSignsWithSeparator()
    {
        static::assertSame('foo-bar', ASCII::to_slugify('foo@bar'));
        static::assertSame('foo_bar', ASCII::to_slugify('foo@bar', '_'));
        static::assertSame('zhong-wen', ASCII::to_slugify('中文', '-', 'en', [], false, true, true));
    }

    public function testToAsciiWithExtraSymbols()
    {
        // This uses the new to_ascii_replace logic
        $str = 'test © test';
        // Note: the map for '©' is ' (c) ', so with spaces around it in input, we get double spaces.
        // This is consistent with the mapping data.
        static::assertSame('test  (c)  test', ASCII::to_ascii($str, 'en', false, true));
    }

    public function testToAsciiWithExtraSymbolsAndCleanup()
    {
        $str = 'test © test';
        // Cleanup (remove_unsupported_chars=true) doesn't collapse spaces.
        static::assertSame('test  (c)  test', ASCII::to_ascii($str, 'en', true, true));
    }

    public function testToAsciiWithExtraSymbolsAndSingleCharsOnly()
    {
        $str = 'test © test';
        static::assertSame('test  (c)  test', ASCII::to_ascii($str, 'en', false, true, false, true));
    }

    public function testToSlugifyWithExtraSymbols()
    {
        $str = 'test © test';
        // to_slugify collapses multiple separators/spaces.
        static::assertSame('test-c-test', ASCII::to_slugify($str, '-', 'en', [], true));
    }

    public function testToTransliterateWithStrict()
    {
        // If intl is available, it should use it.
        $str = 'déjà vu';
        static::assertSame('deja vu', ASCII::to_transliterate($str, '?', true));
    }

    public function testToTransliterateWithUnknownNull()
    {
        // When unknown is null, it should keep the original character if it can't transliterate.
        // But for many emojis, it DOES have a transliteration (to empty string or space sometimes).
        // Let's find a character that is NOT in the maps.
        // U+10FFFF is likely not in any map.
        $str = "\xF4\x8F\xBF\xBF";
        // If it's valid UTF-8 but not in map, and unknown is null, it should be kept.
        static::assertSame($str, ASCII::to_transliterate($str, null, false));

        // If it's NOT valid UTF-8, it should be removed by clean() even if unknown is null.
        static::assertSame('', ASCII::to_transliterate("\xC0\xAF", null, false));
    }

    public function testCleanWithRemoveInvalidUtf8False()
    {
        $invalidUtf8 = "a\xC0\xAFb";
        // Default is true, so it's removed
        static::assertSame('ab', ASCII::clean($invalidUtf8));
        // If false, it should be kept (by the regex part that matches single bytes if they are not part of a valid sequence)
        static::assertSame($invalidUtf8, ASCII::clean($invalidUtf8, true, false, true, true, false));
    }

    public function testCleanRemovesInvisibleCharsByDefault()
    {
        static::assertSame('hello', ASCII::clean("\x01hello"));
        static::assertSame('hello', ASCII::clean("hell\x00o", true, false, true));
        static::assertSame("\x01hello", ASCII::clean("\x01hello", true, false, true, false));
    }

    public function testToAsciiRemapStillWorks()
    {
        static::assertSame(['testi' . \chr(128) . 'g', 'testing'], ASCII::to_ascii_remap('testiñg', 'testing'));
    }

    public function testToAsciiWithMalformedUtf8()
    {
        // Test how to_ascii handles malformed UTF-8 with its new fast paths
        $invalidUtf8 = "a\xC0\xAFb";
        // to_ascii calls clean() by default if it's not pure ASCII.
        // \xC0\xAF is not pure ASCII.
        static::assertSame('ab', ASCII::to_ascii($invalidUtf8));
    }

    public function testToAsciiReplaceMarksInvalidUtf8InShortPath()
    {
        $isValidUtf8 = null;
        $input = \str_repeat('a', 62) . "\xC0\xAF";

        static::assertSame($input, $this->invokeToAsciiReplace($input, 'en', false, false, $isValidUtf8));
        static::assertFalse($isValidUtf8);
    }

    public function testToAsciiExactly64ByteBoundaryStaysCorrect()
    {
        $input64 = \str_repeat('a', 62) . 'ñ';
        $input65 = \str_repeat('a', 63) . 'ñ';

        static::assertSame(\str_repeat('a', 62) . 'n', ASCII::to_ascii($input64, 'en', true), '64-byte path');
        static::assertSame(\str_repeat('a', 63) . 'n', ASCII::to_ascii($input65, 'en', true), '65-byte path');
    }

    public function testToTransliterateWithEmptyUnknown()
    {
        $str = '😀';
        static::assertSame('', ASCII::to_transliterate($str, '', false));
    }

    public function testToTransliteratePrefixesUnknownWarmMapCacheKeys()
    {
        $unknown = "\x00null";

        static::assertSame($unknown, ASCII::to_transliterate('😀', $unknown, false));

        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('to_transliterate');
        $method->setAccessible(true);

        $warmMaps = $method->getStaticVariables()['WARM_MAPS'];
        static::assertArrayHasKey("\x01" . $unknown, $warmMaps);
        static::assertArrayNotHasKey($unknown . "\x01", $warmMaps);
    }

    public function testToAsciiWithUnsupportedChars()
    {
        // Emoji is not in the 'en' replacement map.
        $str = 'hello 😀';
        // By default, it's removed.
        static::assertSame('hello ', ASCII::to_ascii($str, 'en', true));
        // If remove_unsupported_chars=false, it's kept?
        // No, to_ascii only keeps ASCII.
        // Wait, what does to_ascii do if remove_unsupported_chars=false?
        // It should NOT run the final cleanup.
        static::assertSame('hello 😀', ASCII::to_ascii($str, 'en', false));

        // If use_transliterate=true, it should use to_transliterate for the emoji.
        static::assertSame('hello ', ASCII::to_ascii($str, 'en', true, false, true));
    }

    public function testToAsciiWithLongStringAndFilteredMap()
    {
        // A long string to exercise the MAP_BY_FIRST_BYTE optimization
        $str = \str_repeat('abc ', 100) . '©' . \str_repeat(' def', 100);
        $result = ASCII::to_ascii($str, 'en', true, true);
        static::assertStringContainsString('(c)', $result);
    }

    public function testToAsciiPrivateShortHelperAsciiOnlyInputAndQueueEviction()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $replace = $rc->getMethod('to_ascii_replace');
        $replace->setAccessible(true);

        $isValidUtf8 = null;
        static::assertSame(
            'Plain ASCII text 123 test',
            $replace->invokeArgs(null, ['Plain ASCII text 123 test', 'de', false, false, &$isValidUtf8])
        );
        static::assertTrue($isValidUtf8);

        $accented = ['à', 'á', 'â', 'ã', 'ä', 'å', 'ā', 'ă', 'ą', 'ç', 'ć', 'ĉ', 'ċ', 'č', 'ď', 'đ', 'è', 'é', 'ê', 'ë', 'ē', 'ĕ', 'ė', 'ę'];
        $pairs = [];
        foreach ($accented as $left) {
            foreach ($accented as $right) {
                if ($left === $right) {
                    continue;
                }
                $pairs[] = $left . $right;
                if (\count($pairs) === 257) {
                    break 2;
                }
            }
        }

        foreach ($pairs as $pair) {
            $valid = null;
            $out = $replace->invokeArgs(null, [$pair, 'de', false, false, &$valid]);
            static::assertNotSame($pair, $out, 'pair should be remapped: ' . $pair);
            static::assertTrue($valid);
        }

        $staticVars = $replace->getStaticVariables();
        $shortCache = $staticVars['SHORT_FILTERED_MAP_CACHE'];
        $shortQueue = $staticVars['SHORT_FILTERED_MAP_CACHE_QUEUE'];

        static::assertCount(256, $shortCache);
        static::assertCount(256, $shortQueue);

        \preg_match_all('/./u', $pairs[0], $firstPairChars);
        \preg_match_all('/./u', $pairs[1], $secondPairChars);
        \preg_match_all('/./u', $pairs[256], $lastPairChars);

        $firstShortCacheKey = 'de-0-0:' . \implode('|', $firstPairChars[0]);
        $secondShortCacheKey = 'de-0-0:' . \implode('|', $secondPairChars[0]);
        $lastShortCacheKey = 'de-0-0:' . \implode('|', $lastPairChars[0]);

        static::assertArrayNotHasKey($firstShortCacheKey, $shortCache);
        static::assertArrayHasKey($secondShortCacheKey, $shortCache);
        static::assertArrayHasKey($lastShortCacheKey, $shortCache);
        static::assertSame($secondShortCacheKey, $shortQueue[0]);
        static::assertSame($lastShortCacheKey, $shortQueue[255]);
    }

    public function testAsciiLanguageReplacementMapCacheIsHitOnRepeatedLookup()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('getAsciiLanguageReplacementMap');
        $method->setAccessible(true);

        $first = $method->invoke(null, 'de', false, false);
        $second = $method->invoke(null, 'de', false, false);

        static::assertSame($first, $second);
        static::assertArrayHasKey('ü', $second);
    }

    public function testGetLanguageNormalizesVariantsAcrossRepeatedLookups()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('get_language');
        $method->setAccessible(true);

        static::assertSame('de', $method->invoke(null, 'DE'));
        static::assertSame('de', $method->invoke(null, 'de-DE'));
        static::assertSame('de_at', $method->invoke(null, 'de_at'));
        static::assertSame('en_us', $method->invoke(null, 'EN-us'));

        static::assertSame('de', $method->invoke(null, 'DE'));
        static::assertSame('en_us', $method->invoke(null, 'EN-us'));

        $cache = $method->getStaticVariables()['LANGUAGE_CACHE'];
        static::assertSame('de', $cache['DE']);
        static::assertSame('de', $cache['de-DE']);
        static::assertSame('de_at', $cache['de_at']);
        static::assertSame('en_us', $cache['EN-us']);
    }

    public function testGetLanguageEmptyInputDoesNotPopulateCache()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('get_language');
        $method->setAccessible(true);

        $before = $method->getStaticVariables()['LANGUAGE_CACHE'] ?? [];

        static::assertSame('', $method->invoke(null, ''));

        $after = $method->getStaticVariables()['LANGUAGE_CACHE'] ?? [];
        static::assertSame($before, $after);
        static::assertArrayNotHasKey('', $after);
    }

    public function testAsciiAllReplacementMapCacheSeparatesExtraAndSingleCharacterFlags()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('getAsciiAllReplacementMap');
        $method->setAccessible(true);

        $baseMap = $method->invoke(null, false, false);
        $singleCharBaseMap = $method->invoke(null, false, true);
        $extraMap = $method->invoke(null, true, false);
        $singleCharExtraMap = $method->invoke(null, true, true);

        static::assertSame('EUR', $baseMap['€']);
        static::assertSame('EUR', $singleCharBaseMap['€']);
        static::assertArrayNotHasKey('∞', $baseMap);
        static::assertArrayNotHasKey('∞', $singleCharBaseMap);

        static::assertSame(' Euro ', $extraMap['€']);
        static::assertSame(' Euro ', $singleCharExtraMap['€']);
        static::assertSame('∞', $extraMap['∞']);
        static::assertSame('∞', $singleCharExtraMap['∞']);

        static::assertGreaterThan(\count($baseMap), \count($extraMap));
        static::assertGreaterThan(\count($singleCharExtraMap), \count($extraMap));
        static::assertGreaterThan(\count($singleCharBaseMap), \count($baseMap));
    }

    public function testAsciiAllReplacementMapCacheRemainsSeparatedAcrossWarmLookups()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('getAsciiAllReplacementMap');
        $method->setAccessible(true);

        $method->invoke(null, true, true);
        $baseMap = $method->invoke(null, false, false);
        $method->invoke(null, false, true);
        $extraMap = $method->invoke(null, true, false);
        $singleCharBaseMap = $method->invoke(null, false, true);
        $singleCharExtraMap = $method->invoke(null, true, true);

        static::assertSame('EUR', $baseMap['€']);
        static::assertSame(' Euro ', $extraMap['€']);
        static::assertSame('EUR', $singleCharBaseMap['€']);
        static::assertSame(' Euro ', $singleCharExtraMap['€']);

        static::assertArrayNotHasKey('∞', $baseMap);
        static::assertArrayNotHasKey('∞', $singleCharBaseMap);
        static::assertSame('∞', $extraMap['∞']);
        static::assertSame('∞', $singleCharExtraMap['∞']);

        $cache = $method->getStaticVariables()['CACHE'];
        static::assertSame('EUR', $cache['0-0']['€']);
        static::assertSame('EUR', $cache['0-1']['€']);
        static::assertSame(' Euro ', $cache['1-0']['€']);
        static::assertSame(' Euro ', $cache['1-1']['€']);
    }

    public function testAsciiLanguageReplacementMapCacheSeparatesLanguageAndExtraFlags()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('getAsciiLanguageReplacementMap');
        $method->setAccessible(true);

        $germanBase = $method->invoke(null, 'de', false, false);
        $germanExtra = $method->invoke(null, 'de', true, false);
        $dutchExtra = $method->invoke(null, 'nl', true, false);
        $germanSingleCharBase = $method->invoke(null, 'de', false, true);

        static::assertSame('ae', $germanBase['ä']);
        static::assertArrayNotHasKey('∞', $germanBase);

        static::assertSame('ae', $germanExtra['ä']);
        static::assertSame(' undendlich ', $germanExtra['∞']);

        static::assertSame('a', $dutchExtra['ä']);
        static::assertSame(' oneindig ', $dutchExtra['∞']);

        static::assertSame('ae', $germanSingleCharBase['ä']);
        static::assertArrayNotHasKey('∞', $germanSingleCharBase);
    }

    public function testAsciiLanguageReplacementMapCacheHandlesNumericLanguageSuffixes()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('getAsciiLanguageReplacementMap');
        $method->setAccessible(true);

        $method->invoke(null, 'ru__gost_2000_b', true, true);
        $gostBase = $method->invoke(null, 'ru__gost_2000_b', false, false);
        $passportExtra = $method->invoke(null, 'ru__passport_2013', true, false);
        $passportSingleBase = $method->invoke(null, 'ru__passport_2013', false, true);
        $gostExtra = $method->invoke(null, 'ru__gost_2000_b', true, false);

        static::assertSame('Shh', $gostBase['Щ']);
        static::assertArrayNotHasKey('∞', $gostBase);

        static::assertSame('Shh', $gostExtra['Щ']);
        static::assertSame(' beskonecnost\' ', $gostExtra['∞']);

        static::assertSame('Shch', $passportExtra['Щ']);
        static::assertSame(' beskonecnost\' ', $passportExtra['∞']);

        static::assertSame('Shch', $passportSingleBase['Щ']);
        static::assertArrayNotHasKey('∞', $passportSingleBase);

        $cache = $method->getStaticVariables()['CACHE'];
        static::assertSame('Shh', $cache['ru__gost_2000_b-0-0']['Щ']);
        static::assertSame(' beskonecnost\' ', $cache['ru__gost_2000_b-1-0']['∞']);
        static::assertSame('Shch', $cache['ru__passport_2013-0-1']['Щ']);
        static::assertSame(' beskonecnost\' ', $cache['ru__passport_2013-1-0']['∞']);
    }

    public function testToAsciiReplaceCacheSeparatesNumericLanguageSuffixesAndFlags()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('to_ascii_replace');
        $method->setAccessible(true);

        $valid = null;
        $method->invokeArgs(null, ['Щ∞', 'ru__gost_2000_b', true, true, &$valid]);

        $valid = null;
        $gostBase = $method->invokeArgs(null, ['Щ∞', 'ru__gost_2000_b', false, false, &$valid]);
        static::assertTrue($valid);
        static::assertSame('Shh∞', $gostBase);

        $valid = null;
        $passportExtra = $method->invokeArgs(null, ['Щ∞', 'ru__passport_2013', true, false, &$valid]);
        static::assertTrue($valid);
        static::assertSame('Shch beskonecnost\' ', $passportExtra);

        $valid = null;
        $passportSingleBase = $method->invokeArgs(null, ['Щ∞', 'ru__passport_2013', false, true, &$valid]);
        static::assertTrue($valid);
        static::assertSame('Shch∞', $passportSingleBase);

        $valid = null;
        $gostExtra = $method->invokeArgs(null, ['Щ∞', 'ru__gost_2000_b', true, false, &$valid]);
        static::assertTrue($valid);
        static::assertSame('Shh beskonecnost\' ', $gostExtra);
    }

    public function testFilterAsciiReplacementMapKeepsOnlySingleUtf8CodePoints()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('filterAsciiReplacementMap');
        $method->setAccessible(true);

        $filtered = $method->invoke(null, [
            'ä' => 'ae',
            '😀' => 'smile',
            '😀😀' => 'double-emoji',
            'A̧' => 'combining',
            'abc' => 'ascii-string',
        ], true);

        static::assertSame([
            'ä' => 'ae',
            '😀' => 'smile',
        ], $filtered);
    }

    public function testFilterAsciiReplacementMapKeepsDotAllNewlineKeys()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('filterAsciiReplacementMap');
        $method->setAccessible(true);

        $filtered = $method->invoke(null, [
            "\n" => 'newline',
            "\n\n" => 'two-newlines',
            "😀\n" => 'emoji-plus-newline',
            'abcde' => 'five-bytes',
        ], true);

        static::assertSame([
            "\n" => 'newline',
            "\n\n" => 'two-newlines',
        ], $filtered);
    }

    public function testPrepareAsciiAndExtrasMapsInitializesBaseAndExtraMaps()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $method = $rc->getMethod('prepareAsciiAndExtrasMaps');
        $method->setAccessible(true);

        $mapsProperty = $rc->getProperty('ASCII_MAPS');
        $mapsAndExtrasProperty = $rc->getProperty('ASCII_MAPS_AND_EXTRAS');
        $extrasProperty = $rc->getProperty('ASCII_EXTRAS');
        $mapsProperty->setAccessible(true);
        $mapsAndExtrasProperty->setAccessible(true);
        $extrasProperty->setAccessible(true);

        $originalMaps = $mapsProperty->getValue();
        $originalMapsAndExtras = $mapsAndExtrasProperty->getValue();
        $originalExtras = $extrasProperty->getValue();

        $mapsProperty->setValue(null, null);
        $mapsAndExtrasProperty->setValue(null, null);
        $extrasProperty->setValue(null, null);

        try {
            $method->invoke(null);

            $maps = $mapsProperty->getValue();
            $mapsAndExtras = $mapsAndExtrasProperty->getValue();
            $extras = $extrasProperty->getValue();

            static::assertIsArray($maps);
            static::assertIsArray($mapsAndExtras);
            static::assertIsArray($extras);
            static::assertSame('ae', $maps['de']['ä']);
            static::assertSame(' undendlich ', $extras['de']['∞']);
            static::assertSame('ae', $mapsAndExtras['de']['ä']);
            static::assertSame(' undendlich ', $mapsAndExtras['de']['∞']);
        } finally {
            $mapsProperty->setValue(null, $originalMaps);
            $mapsAndExtrasProperty->setValue(null, $originalMapsAndExtras);
            $extrasProperty->setValue(null, $originalExtras);
        }
    }

    public function testCharsArrayWithExtraSymbolsIncludesBaseAndExtraMappings()
    {
        $dutch = ASCII::charsArrayWithOneLanguage('nl', true, false);

        static::assertSame('a', $dutch['ä']);
        static::assertSame(' oneindig ', $dutch['∞']);
    }

    public function testCharsArrayInitializesBaseMapsWhenEmpty()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $mapsProperty = $rc->getProperty('ASCII_MAPS');
        $mapsProperty->setAccessible(true);

        $originalMaps = $mapsProperty->getValue();
        $mapsProperty->setValue(null, null);

        try {
            $maps = ASCII::charsArray();

            static::assertIsArray($maps);
            static::assertSame('b', $maps['ru']['б']);
            static::assertIsArray($mapsProperty->getValue());
        } finally {
            $mapsProperty->setValue(null, $originalMaps);
        }
    }

    public function testCharsArrayWithExtraSymbolsInitializesMergedMapsWhenEmpty()
    {
        $rc = new \ReflectionClass(ASCII::class);
        $mapsProperty = $rc->getProperty('ASCII_MAPS');
        $mapsAndExtrasProperty = $rc->getProperty('ASCII_MAPS_AND_EXTRAS');
        $extrasProperty = $rc->getProperty('ASCII_EXTRAS');
        $mapsProperty->setAccessible(true);
        $mapsAndExtrasProperty->setAccessible(true);
        $extrasProperty->setAccessible(true);

        $originalMaps = $mapsProperty->getValue();
        $originalMapsAndExtras = $mapsAndExtrasProperty->getValue();
        $originalExtras = $extrasProperty->getValue();

        $mapsProperty->setValue(null, null);
        $mapsAndExtrasProperty->setValue(null, null);
        $extrasProperty->setValue(null, null);

        try {
            $maps = ASCII::charsArray(true);

            static::assertIsArray($maps);
            static::assertSame('b', $maps['ru']['б']);
            static::assertSame(' i ', $maps['ru']['&']);
            static::assertIsArray($mapsProperty->getValue());
            static::assertIsArray($mapsAndExtrasProperty->getValue());
            static::assertIsArray($extrasProperty->getValue());
        } finally {
            $mapsProperty->setValue(null, $originalMaps);
            $mapsAndExtrasProperty->setValue(null, $originalMapsAndExtras);
            $extrasProperty->setValue(null, $originalExtras);
        }
    }

    public function testToAsciiWithLanguageSpecificExtraSymbols()
    {
        // '∞' in Dutch (nl) is ' oneindig '
        // '∞' in Italian (it) is ' infinito '
        $str = '∞';
        static::assertSame(' oneindig ', ASCII::to_ascii($str, 'nl', true, true));
        static::assertSame(' infinito ', ASCII::to_ascii($str, 'it', true, true));
    }
}
