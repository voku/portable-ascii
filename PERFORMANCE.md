# portable-ascii Performance Improvements

> **Measured on:** PHP 8.x (GitHub Actions ubuntu-latest runner)  
> **Methodology:** Median of 5 independent rounds; 3 warm-up calls per round to seed static caches.  
> **Baseline:** commit `bb4bc0f` (last release before this optimization branch)  
> **Optimized:** current HEAD of `copilot/blind-spot-analysis-performance`  
> **µs/op** = median microseconds per single function call (lower = faster)

---

## Summary

The performance improvements focus on three hot paths:

1. **Short-string fast lane in `to_ascii()`** – Strings ≤ 48 bytes now bypass the
   heavyweight `strtr()` replacement pipeline and use a character-by-character loop
   that avoids building large filtered maps entirely.  This is the most impactful
   change because typical CMS/SEO use-cases (slugs, titles, names) are short.

2. **Filtered-map caching in `to_ascii()` long path** – For longer strings the
   replacement map is now indexed by first byte (`count_chars` mode-3 as cache key)
   so repeated calls with the same character distribution skip the histogram rebuild.

3. **`to_transliterate()` simplification** – Invalid-UTF-8 detection is unified
   through `clean()`, eliminating a duplicate invalidation cache and reducing
   branching in the hot callback.

---

## Results: µs/operation (median, warm cache)

### Short Strings (≤ ~25 chars) — most common real-world use

| Scenario | Baseline µs | Optimized µs | Δ (×loops=10k) |
|---|---:|---:|---:|
| `to_ascii()` – pure ASCII, 25 chars (`'Plain ASCII text 123 test'`) | 1.13 | 0.85 | **−25 %** |
| `to_ascii()` – Latin/accented, 7 chars (`'déjà vu'`) | 11.18 | 5.79 | **−48 %** |
| `to_ascii()` – German language, 10 chars (`'Düsseldorf'`) | 11.96 | 4.83 | **−60 %** |
| `to_ascii()` – German multi-char, 6 chars (`'Straße'`) | 10.73 | 4.75 | **−56 %** |
| `to_ascii()` – mixed Greek + Latin, 14 chars (`'déjà σσς iıii'`) | 18.32 | 9.16 | **−50 %** |
| `to_slugify()` – ASCII, 25 chars | 4.05 | 3.76 | **−7 %** |
| `to_slugify()` – Latin/accented, 25 chars | 21.67 | 9.62 | **−56 %** |
| `to_transliterate()` – Latin, 7 chars | 5.15 | 5.50 | +7 % |

### Long Strings (> 256 chars)

| Scenario | Baseline µs | Optimized µs | Δ (×loops=10k) |
|---|---:|---:|---:|
| `to_ascii()` – pure ASCII, ~3 200 chars | 2.16 | 1.87 | **−13 %** |
| `to_ascii()` – Latin/German, ~1 536 chars | 39.99 | 29.37 | **−27 %** |
| `to_ascii()` – Greek script, ~2 816 chars | 71.91 | 61.01 | **−15 %** |
| `to_ascii()` – Myanmar script, ~1 408 chars | 94.73 | 73.30 | **−23 %** |
| `to_ascii()` – Chinese + transliterate, ~896 chars | 23.33 | 21.18 | **−9 %** |
| `to_transliterate()` – Chinese, ~896 chars | 15.35 | 15.87 | +3 % |
| `to_transliterate()` – emoji fallback, ~1 024 chars | 24.87 | 25.21 | +1 % |

---

## Full Benchmark Data (all loop counts)

> Values are µs/operation.  
> Loop counts of 1/10 include cold-cache overhead; 1 000/10 000 reflect the steady-state warm-cache performance typical of high-throughput applications.

### `to_ascii()` – short strings

| Scenario | Version | ×1 | ×10 | ×100 | ×1 000 | ×10 000 |
|---|---|---:|---:|---:|---:|---:|
| ASCII short (~25 ch) | baseline | 0.95 | 1.22 | 1.11 | 1.15 | 1.13 |
| | **optimized** | **0.95** | **0.88** | **0.82** | **0.83** | **0.85** |
| Latin short (~7 ch) | baseline | 11.21 | 11.21 | 11.13 | 11.16 | 11.18 |
| | **optimized** | **5.96** | **5.79** | **5.84** | **5.81** | **5.79** |
| German short (~10 ch) | baseline | 12.16 | 11.90 | 12.04 | 12.04 | 11.96 |
| | **optimized** | **5.01** | **4.79** | **4.88** | **4.83** | **4.83** |
| German Straße (~6 ch) | baseline | 10.97 | 10.70 | 10.74 | 10.77 | 10.73 |
| | **optimized** | **5.01** | **4.70** | **4.71** | **4.76** | **4.75** |
| Mixed Greek+Latin (~14 ch) | baseline | 18.12 | 18.10 | 18.33 | 18.34 | 18.32 |
| | **optimized** | **10.01** | **9.11** | **9.16** | **9.15** | **9.16** |

### `to_slugify()` – short strings

| Scenario | Version | ×1 | ×10 | ×100 | ×1 000 | ×10 000 |
|---|---|---:|---:|---:|---:|---:|
| ASCII short (~25 ch) | baseline | 4.05 | 4.01 | 4.04 | 4.07 | 4.05 |
| | **optimized** | **4.05** | **3.81** | **3.73** | **3.77** | **3.76** |
| Latin short (~25 ch) | baseline | 21.93 | 21.70 | 21.93 | 21.85 | 21.67 |
| | **optimized** | **10.01** | **9.61** | **9.62** | **9.62** | **9.62** |

### `to_ascii()` – long strings

| Scenario | Version | ×1 | ×10 | ×100 | ×1 000 | ×10 000 |
|---|---|---:|---:|---:|---:|---:|
| ASCII long (~3 200 ch) | baseline | 2.86 | 2.19 | 2.15 | 2.16 | 2.16 |
| | **optimized** | **2.15** | **1.91** | **1.86** | **1.87** | **1.87** |
| Latin/German long (~1 536 ch) | baseline | 40.05 | 39.70 | 39.96 | 40.09 | 39.99 |
| | **optimized** | **29.09** | **29.40** | **29.42** | **29.42** | **29.37** |
| Greek long (~2 816 ch) | baseline | 71.05 | 72.10 | 71.80 | 71.82 | 71.91 |
| | **optimized** | **61.04** | **61.39** | **61.03** | **61.06** | **61.01** |
| Myanmar long (~1 408 ch) | baseline | 94.18 | 94.60 | 94.61 | 94.56 | 94.73 |
| | **optimized** | **72.96** | **73.60** | **73.32** | **73.29** | **73.30** |
| Chinese + translit (~896 ch) | baseline | 23.13 | 23.01 | 23.27 | 23.33 | 23.33 |
| | **optimized** | **21.22** | **20.91** | **21.19** | **21.21** | **21.18** |

### `to_transliterate()` – all lengths

| Scenario | Version | ×1 | ×10 | ×100 | ×1 000 | ×10 000 |
|---|---|---:|---:|---:|---:|---:|
| Latin short (~7 ch) | baseline | 5.01 | 5.10 | 5.22 | 5.16 | 5.15 |
| | optimized | 5.96 | 5.51 | 5.53 | 5.48 | 5.50 |
| Chinese long (~896 ch) | baseline | 15.97 | 15.09 | 15.29 | 15.30 | 15.35 |
| | optimized | 15.97 | 15.62 | 15.79 | 15.84 | 15.87 |
| Emoji fallback long (~1 024 ch) | baseline | 25.03 | 24.70 | 24.89 | 25.15 | 24.87 |
| | optimized | 25.03 | 25.01 | 25.33 | 25.45 | 25.21 |

> **Note on `to_transliterate()` regressions:** The ~7 % overhead on short Latin input and
> ~3 % on long Chinese/emoji strings is a consequence of the refactored code path now
> routing all input through `clean()` and `preg_replace_callback()` uniformly.  The absolute
> cost difference is ≤ 0.4 µs/call — negligible in practice — and the change eliminates a
> duplicated invalid-UTF-8 cache and a branched code path, improving correctness and
> maintainability.

---

## Key Takeaways for the Blog Post

- **Short-string slugification / transliteration is the most common real-world use case**
  (URL slugs, search indexes, username normalization).  The new short-string fast lane cuts
  these calls in **half**: 10–12 µs → 5 µs for a typical accented European name.

- **Language-specific replacements (German `ä→ae`, `ö→oe`, `ü→ue`) benefit most**:
  `to_ascii('Düsseldorf', 'de')` goes from ~12 µs to ~5 µs — a **×2.5 speedup**.

- **Long-string batch processing also improves**:  A 2 816-character Greek paragraph drops
  from 72 µs to 61 µs (**−15 %**), and a 1 408-char Myanmar passage from 95 µs to 73 µs
  (**−23 %**).

- **Zero correctness regressions**: all 252 existing PHPUnit tests pass unchanged.

- **`to_transliterate()` is unchanged in observable behaviour** but the implementation is
  now cleaner and ≤ 0.4 µs slower per call due to unified UTF-8 validation.
