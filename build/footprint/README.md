# ASCII mapping footprint (S1, S3, S4, S5)

The dense xNNN banks now return the exact same arrays via tab-separated strings and `explode`.
The sparse x1f1 bank is preserved. `ascii_ord.php` generates the 256 byte keys plus its
historical empty-string key. Paths, values, order, public API and PHP >=7.1 remain unchanged.

```sh
php build/footprint/compact.php          # non-mutating deterministic check
php build/footprint/compact.php --write  # regenerate representation from current arrays
php vendor/bin/phpunit -c phpunit.xml
PORTABLE_ASCII_RUN_PERFORMANCE_TESTS=1 php vendor/bin/phpunit --filter PerformanceRegressionTest
php build/footprint/icu-parity.php > /tmp/icu-parity.json
```

`tests/fixtures/mapping-contract.json` pins the immutable baseline at
`180a8d12055eb350859b628c64066a90a895bd10`. A representation regeneration must not update those
hashes. An intentional semantic mapping update is a separate change with separate review.
Every bank/family, including unused legacy language-max-key metadata, is accounted for.

Coordinated real Composer Dist/Source installs, eight extension-profile differential tests,
byte/Unicode/error contracts, API signatures, and the decision/release report live in the
sibling `portable-utf8-benchmark/tools/footprint/` checkout. ASCII Dist saves 145,941 logical
bytes; allocated size/file count are unchanged. ICU replacement and mandatory split packages
are NO-GO. Strict ICU behavior, default table behavior and all language overrides are retained.

Performance thresholds were not loosened: the opt-in short-Latin ratio is intermittently
sensitive to scheduling; raw baseline/candidate runs are recorded. Both configured PHPStan
analyses pass. Existing formatter config uses a retired PHP-CS-Fixer API. PHP7.1/7.4/8.5 remote
matrix jobs are prepared but unrun. These limitations remain visible in the release gate.

No tag or publication. No changes to the separate TransliteratorPolyfill extraction #162/#167
or U+FFA0 release PR #164/#166. The existing stable/master U+FFA0 output difference is recorded,
not silently fixed in this work. No major/data-path migration is needed for this representation.
