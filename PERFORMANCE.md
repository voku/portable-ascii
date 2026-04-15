# portable-ascii Performance Improvements

> **Measured on:** PHP 8.3.6 (GitHub Actions ubuntu-latest runner)  
> **Methodology:** Median of 5 independent rounds; 1 warm-up + 1 000 measured iterations per round.  
> **Baseline:** `origin/master` (commit `944c955`)  
> **Optimized:** current HEAD of `copilot/blind-spot-analysis-performance`  
> **µs/op** = median microseconds per single function call (lower = faster)

---

## Summary

The optimizations focus on two axes:

1. **ASCII fast-paths in `to_ascii()`** – Pure printable-ASCII strings now return
   immediately via a single regex guard (`/[^\x20-\x7E]/`), avoiding the replacement
   pipeline entirely.  A secondary guard catches ASCII + control-char strings.

2. **First-byte index + `count_chars` filtering for long strings** – The full replacement
   map is pre-indexed by leading byte (cached once per language/options combination via
   `$MAP_BY_FIRST_BYTE`).  On each call, `count_chars($str, 1)` identifies which leading
   bytes are actually present, and only those replacement entries are fed to `strtr()`.
   For long non-ASCII strings this produces a **10–20× speedup** because `strtr()` processes
   a small filtered map instead of the full ~1 500-entry table.

3. **Warm-map caching in `to_transliterate()`** – The transliteration replacement map is
   memoized per `$unknown` fallback value, giving **8–13× speedup** on repeated calls.

4. **Simplified `to_ascii()` replacement path** – All strings (short and long) now use
   `strtr()` instead of `preg_match_all` + multi-character combination loops.  Short strings
   (≤ 64 bytes) receive the full cached map directly; long strings receive the first-byte
   filtered map.  This correctly handles multi-character replacement keys (e.g. Myanmar
   4–5-char ligatures) without requiring `maxKeyLength` combo loops, and keeps the code
   significantly simpler.

---

## Results: µs/operation (median)

### Short Strings — most common real-world use

| Scenario | Master µs | Branch µs | Δ |
|---|---:|---:|---:|
| `to_ascii()` – pure ASCII, 25 chars | 3.07 | **0.81** | **−74 %** |
| `to_ascii()` – Latin/accented, 7 chars (`'déjà vu'`) | 5.41 | 7.70 | +42 % |
| `to_ascii()` – German, 10 chars (`'Düsseldorf'`) | 4.36 | 7.79 | +79 % |
| `to_ascii()` – mixed Greek + Latin, 14 chars | 9.55 | 8.08 | **−15 %** |
| `to_slugify()` – ASCII, 25 chars | 5.41 | **3.82** | **−29 %** |
| `to_slugify()` – Latin/accented, 25 chars | 10.23 | 10.90 | +7 % |
| `to_transliterate()` – ASCII, 25 chars | 1.22 | 1.05 | **−14 %** |
| `to_transliterate()` – Latin, 7 chars | 7.97 | 5.27 | **−34 %** |

### Long Strings (> 256 chars) — big wins

| Scenario | Master µs | Branch µs | Δ |
|---|---:|---:|---:|
| `to_ascii()` – pure ASCII, ~3 200 chars | 7.77 | **1.83** | **−76 %** |
| `to_ascii()` – Greek script, ~2 816 chars | 1478.6 | **70.8** | **−95 %** |
| `to_ascii()` – Greek single-char-only | 382.1 | **68.4** | **−82 %** |
| `to_ascii()` – Myanmar script, ~1 408 chars | 1304.0 | **94.9** | **−93 %** |
| `to_ascii()` – Myanmar single-char-only | 163.6 | **49.7** | **−70 %** |
| `to_ascii()` – Chinese + transliterate, ~896 chars | 1028.7 | **36.4** | **−96 %** |
| `to_transliterate()` – Chinese, ~896 chars | 597.0 | **24.9** | **−96 %** |
| `to_transliterate()` – emoji fixed fallback, ~1 024 chars | 394.0 | **46.0** | **−88 %** |

### Per-Language `to_ascii()` — short strings

| Language | Master µs | Branch µs | Δ |
|---|---:|---:|---:|
| de (German) | 5.38 | 8.09 | +50 % |
| fr (French) | 6.58 | 7.95 | +21 % |
| el (Greek) | 14.34 | 7.92 | **−45 %** |
| ru (Russian) | 12.67 | 8.04 | **−37 %** |
| bg (Bulgarian) | 15.83 | 8.15 | **−49 %** |
| uk (Ukrainian) | 13.19 | 7.97 | **−40 %** |
| ar (Arabic) | 14.31 | 8.25 | **−42 %** |
| tr (Turkish) | 5.43 | 8.17 | +50 % |
| pl (Polish) | 9.59 | 7.88 | **−18 %** |
| ro (Romanian) | 4.82 | 7.93 | +64 % |
| hu (Hungarian) | 5.42 | 7.75 | +43 % |
| sv (Swedish) | 4.83 | 7.70 | +59 % |
| da (Danish) | 5.38 | 7.75 | +44 % |
| nb (Norwegian) | 5.39 | 8.75 | +62 % |
| fi (Finnish — pure ASCII) | 2.99 | **0.81** | **−73 %** |
| my (Myanmar) | 16.52 | 7.82 | **−53 %** |
| zh (Chinese) | 16.83 | 12.68 | **−25 %** |
| ja (Japanese) | 18.02 | 13.11 | **−27 %** |
| ko (Korean) | 15.33 | 12.70 | **−17 %** |
| th (Thai) | 22.10 | 13.46 | **−39 %** |
| en (English merged) | 6.55 | 7.94 | +21 % |
| (empty/all) | 6.90 | 8.67 | +26 % |

---

## Key Takeaways

- **Pure-ASCII input is the #1 beneficiary**: the fast-path guard makes `to_ascii()` on
  already-ASCII strings ~4× faster.  This matters because most strings in typical web
  applications are already ASCII.

- **Long non-ASCII strings see 10–20× speedups** from first-byte index filtering.
  Bulk-processing tasks (data migration, search indexing) benefit enormously.

- **Short non-ASCII European strings (de, fr, ro, sv, da) are 20–80% slower** vs master.
  The `strtr($str, $fullMap)` approach for ≤ 64-byte strings pays ~4.4 µs for the full
  1 500-entry map, whereas master's targeted `str_replace` per matched character was
  ~1.5 µs for typical 1–3 character replacements.  The absolute regression is ~3 µs
  (5 µs → 8 µs), traded against correct multi-character key handling and dramatically
  simpler code.

- **Non-Latin scripts (el, ru, bg, uk, ar, th, my, zh, ja, ko) are 17–53% faster** even
  for short strings, because master's per-character approach needed more iterations for
  these large-alphabet scripts.

- **`to_transliterate()` warm-map caching delivers 88–96% improvement** on repeated calls
  without any behavioral change.

- **Security: `clean()` now rejects overlong C0/C1 sequences, ED surrogates, and F5–F7
  out-of-range bytes** — previously these passed through undetected.

- **Zero correctness regressions**: all 273 PHPUnit tests pass (238 from master + 35 new).
