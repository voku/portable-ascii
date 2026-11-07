<?php

// Research only: compare raw native ICU, strict public API, and table public API.
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
use voku\helper\ASCII;

if (!extension_loaded('intl')) {
    fwrite(STDERR, "Native ICU unavailable; cannot claim native parity.\n");
    exit(2);
}
$root = $argv[1] ?? dirname(__DIR__, 2) . '/src/voku/helper/data';
$native = transliterator_create('NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII;');
$groups = [];
$hash = hash_init('sha256');
$skipped = 0;
foreach (glob($root . '/x*.php') as $file) {
    $bank = hexdec(substr(basename($file), 1, -4));
    $group = ['keys' => 0, 'table_vs_native' => 0, 'strict_vs_table' => 0, 'examples' => []];
    foreach (include $file as $offset => $value) { // NOSONAR: reload bank arrays already loaded by earlier ASCII calls.
        $code = ($bank << 8) + $offset;
        $char = IntlChar::chr($code);
        if ($char === null || !preg_match('//u', $char)) {
            ++$skipped;
            continue;
        }
        $table = ASCII::to_transliterate($char, '?', false);
        $strict = ASCII::to_transliterate($char, '?', true);
        $icu = transliterator_transliterate($native, $char);
        ++$group['keys'];
        $group['table_vs_native'] += (int) ($table !== $icu);
        $group['strict_vs_table'] += (int) ($strict !== $table);
        if ($table !== $icu && count($group['examples']) < 3) {
            $group['examples'][] = ['codepoint' => sprintf('U+%04X', $code), 'input' => $char, 'table' => $table, 'strict' => $strict, 'native' => $icu];
        }
        hash_update($hash, serialize([$code, $table, $strict, $icu]));
    }
    $groups[basename($file)] = $group;
}
$languages = [];
foreach (include $root . '/ascii_by_languages.php' as $language => $map) { // NOSONAR: ASCII calls have already included this array-returning file.
    $counts = ['keys' => 0, 'to_ascii_vs_native' => 0, 'to_slugify_vs_native_slug' => 0];
    foreach ($map as $char => $replacement) {
        $char = (string) $char;
        $icu = transliterator_transliterate($native, $char);
        ++$counts['keys'];
        $counts['to_ascii_vs_native'] += (int) (ASCII::to_ascii($char, $language) !== $icu);
        $counts['to_slugify_vs_native_slug'] += (int) (ASCII::to_slugify($char, '-', $language) !== ASCII::to_slugify((string) $icu, '-', 'en'));
    }
    $languages[$language] = $counts;
}
echo json_encode(['php' => PHP_VERSION, 'icu' => INTL_ICU_VERSION, 'icu_data' => INTL_ICU_DATA_VERSION, 'rules' => 'NFKC; [:Nonspacing Mark:] Remove; NFKC; Any-Latin; Latin-ASCII;', 'comparison' => 'Native output is raw ICU; strict includes the library cleanup/table fallback policy.', 'banks' => $groups, 'languages' => $languages, 'invalid_codepoints_skipped' => $skipped, 'sha256' => hash_final($hash)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL;
