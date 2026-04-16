# portable-ascii Performance Benchmark

**Branch:** `copilot/blind-spot-analysis-performance`
**Commit:** `a283373`
**PHP Version:** `8.3.30`
**Method:** Median µs/op across 5 sampled rounds per scenario, with 3 warm-up calls before each sample.
**Machine:** Linux x86-64
**Master baseline:** current git `master` (`8b51d91`)
**Run Date:** 2026-04-17

The PHPUnit class behind these numbers has two modes:
`testRepresentativeInputsStayCorrectUnderRepeatedCalls()` checks correctness over the multilingual sample set, and
`testPerformanceRatiosForRepresentativeInputs()` emits the benchmark numbers below when `PORTABLE_ASCII_RUN_PERFORMANCE_TESTS=1`.

---

## How To Reproduce

Use the benchmark test in `tests/PerformanceRegressionTest.php` and run it twice: once on the branch you want to document, and once on current `master`.

### Branch Sample

```bash
PORTABLE_ASCII_RUN_PERFORMANCE_TESTS=1 ./vendor/bin/phpunit --filter PerformanceRegressionTest
```

### Master Sample

Run the same command on a clean checkout of `master`. A separate worktree keeps the comparison honest:

```bash
git worktree add --detach /tmp/portable-ascii-master master
cd /tmp/portable-ascii-master
composer install --no-interaction
PORTABLE_ASCII_RUN_PERFORMANCE_TESTS=1 ./vendor/bin/phpunit --filter PerformanceRegressionTest
```

The benchmark file itself is not part of `master` yet, so for the comparison run I copied `tests/PerformanceRegressionTest.php` into the `master` worktree as an untracked file. That keeps the harness identical across both runs while still measuring the `master` code at `HEAD`.

### How To Read The Output

The benchmark writes a block like this:

```text
portable-ascii performance profile (median µs/op)
- to_ascii_ascii_short                           0.094
- to_transliterate_ascii_short                   0.203
...
```

Use the `portable-ascii performance profile` lines only.

* Ignore the PHPUnit progress dots and the final summary.
* Each value is the median microseconds per operation for that scenario.
* The branch sample fills the `Text Families` and `Multilingual Load Curves` tables.
* The `master` sample is only used for the `Load-Curve Headlines` comparison table.

### How To Combine The Results

For each headline row, compute:

```text
delta = (branch / master - 1) * 100
```

* Negative values mean the branch is faster.
* Positive values mean the branch is slower.
* Keep the arrow direction as `master → branch` so the comparison reads left to right.
* Preserve the same loop counts: `N=1`, `N=10`, `N=100`, `N=1000`, and `N=10000`.

When updating this file, also refresh the metadata at the top:

* `Branch`
* `Commit`
* `PHP Version`
* `Method`
* `Machine`
* `Master baseline`
* `Run Date`

---

## Optimization Focus

| Optimization | Description |
|---|---|
| **ASCII fast-path** | `preg_match('/[^\x20-\x7E]/', $str)` guard at the top of `to_ascii()` for pure-ASCII input |
| **Short valid UTF-8 fast path** | For short inputs (≤ 64 bytes), `to_ascii()` goes straight through `to_ascii_replace()` and skips the separate UTF-8 validation pass unless malformed input is detected |
| **Latin short-string filtered-map cache** | For short strings with multibyte chars, build and cache a tiny filtered `strtr()` map keyed by the exact UTF-8 sequences present in the input |
| **Single-char short-string shortcut** | When a short string contains exactly one replaceable multibyte sequence, `to_ascii_replace()` returns via `str_replace()` before building the filtered-cache key |
| **MAP_BY_FIRST_BYTE** | Pre-indexes the replacement map by leading byte; for long strings (> 200 bytes), `count_chars()` filters the `strtr()` map to only bytes present |
| **Printable-ASCII cleanup skip** | After replacement/transliteration, cleanup is skipped when the result is already printable ASCII |
| **Printable-ASCII transliteration fast path** | `to_transliterate()` returns early for long printable ASCII strings before setting up warm caches or broader validation |
| **WARM_MAPS cache** | Static per-`$unknown` cache in `to_transliterate()` so the replacement map is assembled once |
| **Tightened UTF-8 validation** | `clean()` rejects C0/C1 overlongs, E0 overlongs, ED surrogates, F0 overlongs, and F5-F7 out-of-range sequences |

---

## Text Families

### Latin And ASCII

| Scenario | Median µs/op |
|---|---:|
| ASCII `to_ascii` | 0.074 |
| ASCII `to_transliterate` | 0.137 |
| Latin short `to_ascii` | 0.692 |
| Latin short `to_transliterate` | 1.660 |
| German short `to_ascii` | 0.579 |
| Mixed short `to_ascii` | 0.858 |
| ASCII short `to_slugify` | 0.511 |
| Latin short `to_slugify` | 1.400 |
| ASCII long `to_ascii` | 0.896 |
| ASCII long `to_transliterate` | 1.015 |

---

### Non-Latin And Fallbacks

| Scenario | Median µs/op |
|---|---:|
| Greek long `to_ascii` | 34.491 |
| Greek long single-char-only `to_ascii` | 34.217 |
| Myanmar long `to_ascii` | 42.192 |
| Myanmar long single-char-only `to_ascii` | 20.529 |
| Chinese long transliterate `to_ascii` | 11.166 |
| Chinese long `to_transliterate` | 18.104 |
| Unknown long fixed fallback | 8.501 |
| Unknown long changing fallback | 76.039 |

---

## Multilingual Load Curves

| Language | N=1 | N=10 | N=100 | N=1000 | N=10000 |
|---|---:|---:|---:|---:|---:|
| en | 0.954 | 0.501 | 0.510 | 0.555 | 0.540 |
| de | 2.146 | 1.287 | 1.380 | 1.449 | 1.432 |
| fr | 2.146 | 1.502 | 1.369 | 1.471 | 1.487 |
| es | 1.907 | 1.502 | 1.431 | 1.598 | 1.523 |
| ru | 5.007 | 4.792 | 4.690 | 4.929 | 5.039 |
| tr | 0.954 | 1.407 | 1.450 | 1.694 | 1.450 |

---

## Key Takeaways

### Load-Curve Headlines

| Sample Text | Headline | N=1 | N=10 | N=100 | N=1000 | N=10000 | Reading |
|---|---|---:|---:|---:|---:|---:|---|
| `Using strings like foo bar` | Cold start ties, warm runs go branch | `0.954 → 0.954` (0%) | `1.192 → 0.501` (−58%) | `1.161 → 0.510` (−56%) | `1.277 → 0.555` (−57%) | `1.202 → 0.540` (−55%) | Tie at `N=1`; branch wins `N≥10` |
| `Fußgängerübergänge in Düsseldorf Altstadt` | Branch wins from the first call onward | `2.861 → 2.146` (−25%) | `3.195 → 1.287` (−60%) | `3.321 → 1.380` (−58%) | `3.496 → 1.449` (−59%) | `3.395 → 1.432` (−58%) | Branch wins all counts |
| `Événement spécial à l'Opéra de Montréal` | Branch now owns the whole curve | `4.053 → 2.146` (−47%) | `4.005 → 1.502` (−62%) | `4.060 → 1.369` (−66%) | `3.942 → 1.471` (−63%) | `4.058 → 1.487` (−63%) | Branch wins all counts |
| `Niño y acción en corazón de Bogotá` | No master-only pocket remains | `3.815 → 1.907` (−50%) | `3.386 → 1.502` (−56%) | `3.541 → 1.431` (−60%) | `3.342 → 1.598` (−52%) | `3.652 → 1.523` (−58%) | Branch wins all counts |
| `Тестовый заголовок для новостей в Москве` | Branch keeps the largest steady lead | `18.120 → 5.007` (−72%) | `17.309 → 4.792` (−72%) | `17.059 → 4.690` (−73%) | `17.196 → 4.929` (−71%) | `18.049 → 5.039` (−72%) | Branch wins all counts |
| `Iğdır İstanbul için çağrı` | Branch wins cleanly across the range | `4.053 → 0.954` (−76%) | `4.101 → 1.407` (−66%) | `4.129 → 1.450` (−65%) | `4.070 → 1.694` (−58%) | `4.269 → 1.450` (−66%) | Branch wins all counts |

### What It Means

* The benchmark coverage is multilingual by design, not just English.
* In this refresh, the branch wins every `master → branch` load-curve comparison except the English cold start, which is still an exact tie.
* The strongest relative gains are on the non-Latin conversion rows and the localized slugify curves, especially Russian and Turkish.
* The German, French, Spanish, Russian, and Turkish slugify samples are all decisively branch-positive across every loop count in this run.
* The `master` comparison run still matters even when it fails a benchmark assertion; here it tripped the Myanmar ratio threshold but still printed the full profile block before exiting.

---

## Interpretation

| Category | Impact | Status |
|---|---|---|
| Pure-ASCII input | Strong fast path, `0.074 µs/op` in `to_ascii()` and `0.137 µs/op` in `to_transliterate()` | ✅ Delivered |
| Short Latin input | Transliteration stays under `1.7 µs/op`, and `to_ascii()` is down to `0.692 µs/op` | ✅ Delivered |
| Long ASCII input | `to_ascii()` remains cheaper than direct transliteration (`0.896` vs `1.015 µs/op`) | ✅ Delivered |
| Long Greek / Myanmar input | Cost is still dominated by replacement work; Myanmar benefits strongly from the single-char-only path, while Greek stays close to parity in this sample | ✅ Delivered |
| Chinese transliteration fallback | The fallback path stays faster than direct transliteration (`11.166` vs `18.104 µs/op`) | ✅ Delivered |
| Unknown fallback cache | The warm-path cache remains decisive, even though the fixed-fallback row moved up to `8.501 µs/op` in this run | ✅ Delivered |
| Slugify across languages | Branch wins every tracked load-curve comparison in this refresh; English ties only at `N=1` | ✅ Delivered |

---

*Last updated: 2026-04-17*
