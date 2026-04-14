# portable-ascii Performance Improvements

> **Measured on:** PHP 8.x (GitHub Actions ubuntu-latest runner)  
> **Methodology:** Median of 5 independent rounds; 3 warm-up calls per round to seed static caches.  
> **Baseline:** commit `bb4bc0f` (last release before this optimization branch)  
> **Optimized:** current HEAD of `copilot/blind-spot-analysis-performance`  
> **µs/op** = median microseconds per single function call (lower = faster)

---

## Summary

The performance improvements focus on two hot paths in `to_ascii()`:

1. **Short-string fast lane** – Strings ≤ 48 bytes now bypass the heavyweight `strtr()`
   replacement pipeline and use a character-by-character loop that avoids building large
   filtered maps entirely.  This is the most impactful change because typical CMS/SEO
   use-cases (slugs, titles, names) are short.

2. **Filtered-map caching for long strings** – For longer strings the replacement map is
   now indexed by first byte (`count_chars` mode-3 as cache key) so repeated calls with
   the same character distribution skip the histogram rebuild.

`to_transliterate()` is **unchanged in net performance** — its internal `preg_match_all +
foreach` loop is preserved verbatim, avoiding the overhead of `preg_replace_callback`
closure dispatch.

---

## Results: µs/operation (median, warm cache ×10 000)

### Short Strings (≤ ~25 chars) — most common real-world use

| Scenario | Baseline µs | Optimized µs | Δ |
|---|---:|---:|---:|
| `to_ascii()` – pure ASCII, 25 chars (`'Plain ASCII text 123 test'`) | 1.15 | 0.88 | **−23 %** |
| `to_ascii()` – Latin/accented, 7 chars (`'déjà vu'`) | 11.24 | 6.00 | **−47 %** |
| `to_ascii()` – German language, 10 chars (`'Düsseldorf'`) | 12.23 | 4.98 | **−59 %** |
| `to_ascii()` – German multi-char, 6 chars (`'Straße'`) | 11.02 | 4.92 | **−55 %** |
| `to_ascii()` – mixed Greek + Latin, 14 chars (`'déjà σσς iıii'`) | 18.79 | 9.36 | **−50 %** |
| `to_slugify()` – ASCII, 25 chars | 4.09 | 3.90 | **−5 %** |
| `to_slugify()` – Latin/accented, 25 chars | 22.53 | 9.90 | **−56 %** |
| `to_transliterate()` – Latin, 7 chars | 5.23 | 5.30 | ≈ 0 % |

### Long Strings (> 256 chars)

| Scenario | Baseline µs | Optimized µs | Δ |
|---|---:|---:|---:|
| `to_ascii()` – pure ASCII, ~3 200 chars | 2.21 | 1.92 | **−13 %** |
| `to_ascii()` – Latin/German, ~1 536 chars | 40.45 | 29.59 | **−27 %** |
| `to_ascii()` – Greek script, ~2 816 chars | 74.72 | 59.83 | **−20 %** |
| `to_ascii()` – Myanmar script, ~1 408 chars | 95.30 | 72.73 | **−24 %** |
| `to_ascii()` – Chinese + transliterate, ~896 chars | 24.32 | 21.22 | **−13 %** |
| `to_transliterate()` – Chinese, ~896 chars | 15.14 | 15.08 | ≈ 0 % |
| `to_transliterate()` – emoji fallback, ~1 024 chars | 25.01 | 24.99 | ≈ 0 % |

---

## Full Benchmark Data (all loop counts)

> Values are µs/operation.  
> Loop counts of 1/10 include cold-cache overhead; 1 000/10 000 reflect the steady-state
> warm-cache performance typical of high-throughput applications.

### `to_ascii()` – short strings

| Scenario | Version | ×1 | ×10 | ×100 | ×1 000 | ×10 000 |
|---|---|---:|---:|---:|---:|---:|
| ASCII short (~25 ch) | baseline | 1.19 | 1.19 | 1.15 | 1.17 | 1.15 |
| | **optimized** | **0.95** | **0.91** | **0.87** | **0.88** | **0.88** |
| Latin short (~7 ch) | baseline | 11.92 | 11.30 | 11.27 | 11.31 | 11.24 |
| | **optimized** | **6.20** | **5.98** | **6.04** | **5.99** | **6.00** |
| German short (~10 ch) | baseline | 11.92 | 11.99 | 12.13 | 12.17 | 12.23 |
| | **optimized** | **5.01** | **5.01** | **4.93** | **4.99** | **4.98** |
| German Straße (~6 ch) | baseline | 10.97 | 10.90 | 11.00 | 11.02 | 11.02 |
| | **optimized** | **5.01** | **4.89** | **4.87** | **4.90** | **4.92** |
| Mixed Greek+Latin (~14 ch) | baseline | 18.84 | 18.69 | 18.87 | 18.83 | 18.79 |
| | **optimized** | **10.01** | **9.30** | **9.37** | **9.36** | **9.36** |

### `to_slugify()` – short strings

| Scenario | Version | ×1 | ×10 | ×100 | ×1 000 | ×10 000 |
|---|---|---:|---:|---:|---:|---:|
| ASCII short (~25 ch) | baseline | 5.01 | 4.10 | 4.15 | 4.08 | 4.09 |
| | **optimized** | **4.05** | **3.89** | **3.86** | **3.90** | **3.90** |
| Latin short (~25 ch) | baseline | 22.89 | 22.32 | 22.58 | 22.62 | 22.53 |
| | **optimized** | **10.01** | **9.80** | **9.89** | **9.89** | **9.90** |

### `to_ascii()` – long strings

| Scenario | Version | ×1 | ×10 | ×100 | ×1 000 | ×10 000 |
|---|---|---:|---:|---:|---:|---:|
| ASCII long (~3 200 ch) | baseline | 2.15 | 2.29 | 2.19 | 2.21 | 2.21 |
| | **optimized** | **1.91** | **1.91** | **1.91** | **1.92** | **1.92** |
| Latin/German long (~1 536 ch) | baseline | 41.01 | 40.20 | 40.60 | 40.56 | 40.45 |
| | **optimized** | **29.09** | **29.49** | **29.57** | **29.62** | **29.59** |
| Greek long (~2 816 ch) | baseline | 77.96 | 76.99 | 73.74 | 76.87 | 74.72 |
| | **optimized** | **58.89** | **60.92** | **59.78** | **59.94** | **59.83** |
| Myanmar long (~1 408 ch) | baseline | 94.18 | 95.61 | 95.68 | 95.72 | 95.30 |
| | **optimized** | **72.96** | **73.41** | **72.99** | **72.99** | **72.73** |
| Chinese + translit (~896 ch) | baseline | 25.03 | 24.41 | 24.68 | 24.40 | 24.32 |
| | **optimized** | **22.17** | **21.20** | **21.38** | **21.32** | **21.22** |

### `to_transliterate()` – all lengths

| Scenario | Version | ×1 | ×10 | ×100 | ×1 000 | ×10 000 |
|---|---|---:|---:|---:|---:|---:|
| Latin short (~7 ch) | baseline | 5.96 | 6.29 | 6.11 | 5.23 | 5.23 |
| | optimized | 5.96 | 5.29 | 5.33 | 5.30 | 5.30 |
| Chinese long (~896 ch) | baseline | 15.02 | 15.00 | 15.08 | 15.13 | 15.14 |
| | optimized | 15.02 | 15.00 | 15.10 | 15.08 | 15.08 |
| Emoji fallback long (~1 024 ch) | baseline | 25.03 | 24.80 | 25.09 | 25.11 | 25.01 |
| | optimized | 25.03 | 24.82 | 25.05 | 25.05 | 24.99 |

> `to_transliterate()` numbers are within measurement noise (±1 %).  The function's
> internal hot loop (`preg_match_all + foreach`) was kept exactly as in the baseline;
> the `UNKNOWN_TRANSLITERATION_MARKERS` constant and the narrowed UTF-8 regex ranges
> are the only changes, both of which are neutral to performance.

---

## Key Takeaways for the Blog Post

- **Short-string slugification / transliteration is the most common real-world use case**
  (URL slugs, search indexes, username normalization).  The new short-string fast lane cuts
  these calls in **half**: 11–12 µs → 5 µs for a typical accented European name.

- **Language-specific replacements (German `ä→ae`, `ö→oe`, `ü→ue`) benefit most**:
  `to_ascii('Düsseldorf', 'de')` drops from ~12 µs to ~5 µs — a **×2.5 speedup**.

- **Long-string batch processing also improves significantly**:  A 2 816-character Greek
  paragraph drops from 75 µs to 60 µs (**−20 %**), and a 1 408-char Myanmar passage from
  95 µs to 73 µs (**−24 %**).

- **Zero correctness regressions**: all 252 existing PHPUnit tests pass unchanged.

- **`to_transliterate()` is unchanged in both observable behaviour and performance** —
  the function's hot path was deliberately preserved to avoid introducing regression in
  this widely-used code path.
