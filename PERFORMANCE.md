# portable-ascii Performance Benchmark

**Branch:** `copilot/blind-spot-analysis-performance`  
**Baseline:** `master` (tag `2.0.3`)  
**PHP Version:** 8.3.6  
**Method:** Median µs/op across 3–7 rounds per loop count, static caches pre-warmed, each version run in an isolated process to prevent cross-contamination.  
**Machine:** GitHub Copilot cloud agent (Linux, x86-64)  

---

## What Changed

| Optimization | Description |
|---|---|
| **ASCII fast-path** | `preg_match('/[^\x20-\x7E]/', $str)` guard at top of `to_ascii()` — returns immediately for pure-ASCII input |
| **MAP_BY_FIRST_BYTE** | Pre-indexes the replacement map by the leading byte of each multi-byte sequence; only builds a tiny `strtr()` map from bytes actually present in the string via `count_chars()` |
| **Latin short-string filtered-map cache** | For short Latin-1-supplement strings (`ä`, `ö`, `ü`, `é`, `ê`, `à`, …), build and cache a tiny filtered `strtr()` map from the exact UTF-8 sequences present in the input, so repeat calls avoid rebuilding the broad `0xC2` / `0xC3` Latin buckets |
| **WARM_MAPS cache** | Static per-`$unknown` cache in `to_transliterate()` so the replacement map is only assembled once across repeated calls |
| **Tightened UTF-8 validation** | `clean()` now rejects C0/C1 overlongs, E0 overlongs, ED surrogates, F0 overlongs, F5–F7 out-of-range sequences |

---

## Summary Table (100 000 iterations · steady-state)

> **Legend:** `Master µs` / `Branch µs` = median microseconds per call. `Δ` = relative change; negative = **faster**.

### 🚀 Group 1: Already-ASCII Input (New Fast-Path)

| Scenario | Chars | Master µs | Branch µs | Δ |
|---|---:|---:|---:|---:|
| ASCII headline — *"The Quick Brown Fox Jumps 2025"* | 30 | 2.88 | 0.46 | **−84 %** |
| ASCII article sentence | 140 | 3.29 | 0.49 | **−85 %** |
| Long ASCII article (≈540 chars) | 540 | 4.04 | 0.67 | **−83 %** |

> Most web applications call `to_ascii()` on slugs and titles that are already ASCII (e.g. after a first pass, or for English content).  
> The new `preg_match` guard skips all map-loading and returns in ~0.5 µs — **6× faster than master**.

---

### 🚀 Group 2: Non-Latin Scripts — Short & Medium

| Scenario | Script | Chars | Master µs | Branch µs | Δ |
|---|---|---:|---:|---:|---:|
| *"Αθήνα: Νέα εποχή…"* | Greek | 30 | 26.8 | 9.3 | **−65 %** |
| Greek sentence | Greek | 67 | 50.1 | 18.0 | **−64 %** |
| *"Москва: новый рекорд…"* | Russian | 32 | 27.1 | 9.5 | **−65 %** |
| Russian science sentence | Russian | 74 | 55.6 | 19.4 | **−65 %** |
| *"北京欢迎您来中国旅游"* | Chinese | 9 | 19.9 | 12.0 | **−40 %** |
| Chinese sentence | Chinese | 30 | 45.2 | 17.6 | **−61 %** |
| *"東京オリンピック…"* | Japanese | 14 | 24.7 | 12.4 | **−50 %** |
| *"القاهرة: مستقبل…"* | Arabic | 29 | 33.1 | 13.3 | **−60 %** |

> Non-Latin scripts use byte prefixes outside the C3 range (Greek/Russian ≥ 0xCE, CJK 0xE2–0xEF).  
> `MAP_BY_FIRST_BYTE` limits the `strtr()` replacement table to only the bytes present in the input — typically 2–4 unique first-bytes instead of thousands of entries.

---

### 🚀 Group 3: Long Strings (Any Script)

| Scenario | Script | Chars | Master µs | Branch µs | Δ |
|---|---|---:|---:|---:|---:|
| Long ASCII article | ASCII | ~540 | 4.04 | 0.67 | **−83 %** |
| Long German text | German | ~490 | 62.0 | 26.9 | **−57 %** |
| Long Russian text | Russian | ~525 | 234 | 29.2 | **−88 %** |
| Long Greek text | Greek | ~518 | 259 | 29.1 | **−89 %** |
| Long Chinese text | Chinese/CJK | ~504 | 689 | 32.1 | **−95 %** |

> For long texts (blog posts, product descriptions, import batches), `MAP_BY_FIRST_BYTE` plus `count_chars()` filtering becomes dramatically more efficient because:  
> 1. The first-byte index is built once and cached per language/options combination.  
> 2. `count_chars($str, 1)` returns only byte classes that are actually present, making the `strtr()` map tiny regardless of how large the global map is.  
> 3. Long Chinese text previously decoded the full 700+ page CJK transliteration bank on every call; now it's pre-indexed and filtered in O(1) per byte class.

---

### ⚠️ Group 4: Latin + Diacritics — Short Strings (Remaining Regression)

| Scenario | Script | Chars | Master µs | Branch µs | Δ |
|---|---|---:|---:|---:|---:|
| *"Düsseldorf"* | German | 10 | 4.14 | 7.96 | **+92 %** |
| *"Fußball-WM: Österreich…"* | German | 45 | 6.80 | 9.07 | **+33 %** |
| German news paragraph | German | 180 | 10.7 | 21.8 | **+104 %** |
| French headline with accents | French | 51 | 13.8 | 17.5 | **+27 %** |
| French tourism paragraph | French | 160 | 10.0 | 20.2 | **+102 %** |
| Mixed Latin + Greek headline | Mixed | 55 | 14.8 | 33.0 | **+122 %** |
| Mixed-script slug (*"Ünlü Röportaj – Ça Va"*) | Mixed | 21 | 8.33 | 8.37 | **≈ 0 %** |

> **Root cause:** Western-European Latin Extended characters share the `0xC3` first byte (ä, ö, ü, ß, é, ê, ç, à, ñ, etc. are all `C3 xx`). The branch therefore builds a filtered `strtr()` map covering the entire Latin Extended block (60+ entries) rather than master's pre-flattened single-pass map.  
>   
> This is the **main remaining regression** and the primary focus of the next optimization round. The fix is to also cache the flattened `strtr()` map (keyed by language, not by input string), so the per-call overhead collapses to a single `strtr()` with a pre-built small array.  
>   
> The mixed-script slug is neutral (≈ 0%) because 21 chars with only ü, Ç, à exercises just 2–3 unique first-bytes; the overhead of map-building nearly cancels the savings from the smaller `strtr()` table.

---

## Full Data: µs/op by Loop Count

> Numbers converge quickly as PHP's JIT and opcode caches warm up. Loop count has negligible effect beyond 10 iterations for steady-state workloads.

### ASCII headline — *"The Quick Brown Fox Jumps 2025"*

| Loops | Master µs | Branch µs | Δ |
|---:|---:|---:|---:|
| 1 | 3.10 | 0.72 | −77% |
| 10 | 2.83 | 0.48 | −83% |
| 1 000 | 2.95 | 0.50 | −83% |
| 10 000 | 2.87 | 0.46 | −84% |
| 100 000 | 2.88 | 0.46 | **−84%** |

### German slug — *"Düsseldorf"*

| Loops | Master µs | Branch µs | Δ |
|---:|---:|---:|---:|
| 1 | 4.39 | 8.19 | +87% |
| 10 | 4.04 | 8.04 | +99% |
| 1 000 | 4.05 | 8.00 | +98% |
| 10 000 | 4.06 | 7.93 | +95% |
| 100 000 | 4.14 | 7.96 | **+92%** |

### German sports headline — *"Fußball-WM: Österreich schlägt Tschechien 3:2"*

| Loops | Master µs | Branch µs | Δ |
|---:|---:|---:|---:|
| 1 | 6.52 | 9.40 | +44% |
| 10 | 6.31 | 8.98 | +42% |
| 1 000 | 6.32 | 9.16 | +45% |
| 10 000 | 6.27 | 9.07 | +45% |
| 100 000 | 6.80 | 9.07 | **+33%** |

### Russian headline — *"Москва: новый рекорд температуры"*

| Loops | Master µs | Branch µs | Δ |
|---:|---:|---:|---:|
| 1 | 27.4 | 9.88 | −64% |
| 10 | 27.3 | 9.54 | −65% |
| 1 000 | 27.3 | 9.53 | −65% |
| 10 000 | 27.2 | 9.46 | −65% |
| 100 000 | 27.1 | 9.53 | **−65%** |

### Chinese sentence — *"中华人民共和国成立于一九四九年…"*

| Loops | Master µs | Branch µs | Δ |
|---:|---:|---:|---:|
| 1 | 44.2 | 18.0 | −59% |
| 10 | 45.3 | 17.7 | −61% |
| 1 000 | 43.8 | 18.0 | −59% |
| 10 000 | 44.0 | 18.3 | −58% |
| 100 000 | 45.2 | 17.6 | **−61%** |

### Long Chinese text (~504 chars)

| Loops | Master µs | Branch µs | Δ |
|---:|---:|---:|---:|
| 1 | 712 | 32.4 | −95% |
| 10 | 706 | 32.1 | −95% |
| 1 000 | 702 | 32.3 | −95% |
| 10 000 | 696 | 32.1 | −95% |
| 100 000 | 689 | 32.1 | **−95%** |

### Long Russian text (~525 chars)

| Loops | Master µs | Branch µs | Δ |
|---:|---:|---:|---:|
| 1 | 256 | 29.5 | −88% |
| 10 | 244 | 30.0 | −88% |
| 1 000 | 272 | 29.7 | −89% |
| 10 000 | 237 | 29.9 | −87% |
| 100 000 | 234 | 29.2 | **−88%** |

---

## Visual Summary (at 100 000 loops)

```
ASCII headline       [██████████████████████████████] 2.88 µs  →  [████] 0.46 µs   −84%
Long Chinese         [████████████████████████████████████████████████████████] 689 µs  →  [██] 32 µs  −95%
Long Russian         [████████████████████] 234 µs  →  [██] 29 µs  −88%
Long Greek           [███████████████████] 259 µs  →  [██] 29 µs  −89%
Russian headline     [██████████████████] 27 µs  →  [██████] 9.5 µs  −65%
Greek headline       [██████████████████] 27 µs  →  [██████] 9.3 µs  −65%
Arabic headline      [█████████████████████] 33 µs  →  [████████] 13 µs  −60%
Long German          [████████████████████████████████████████] 62 µs  →  [█████████████████] 27 µs  −57%
Fr. headline         [█████████] 14 µs  →  [███████████] 17 µs  +27% ⚠
German headline      [████] 6.8 µs  →  [██████] 9.1 µs  +33% ⚠
German slug          [███] 4.1 µs  →  [█████] 8.0 µs  +92% ⚠
```

---

## Interpretation & Roadmap

### What the numbers tell us

| Category | Impact | Status |
|---|---|---|
| Pure-ASCII input | **−83 to −85 %** | ✅ Delivered |
| Long Russian / Greek / CJK | **−87 to −95 %** | ✅ Delivered |
| Short Russian / Greek / Arabic / Japanese | **−40 to −65 %** | ✅ Delivered |
| Long German (Latin diacritics) | **−57 %** | ✅ Delivered |
| Short/medium German & French | **+27 to +104 %** | ⚠ Regression — next priority |

### Why German/French is still regressed

Latin Extended characters (ä, ö, ü, ß, é, ê, à, etc.) almost all share the `0xC3` first byte. When `MAP_BY_FIRST_BYTE` groups by first byte, the 0xC3 bucket contains **all** Latin diacritics — 60+ entries. Building this map on every call adds overhead that outweighs the saving from the smaller `strtr()` table.

**Planned fix:** Cache the flattened per-language `strtr()` map in a static array (keyed by language string + options bitmask). After the first call for language `de`, every subsequent call is a single pre-built `strtr()` — exactly as fast or faster than master's approach, while still benefiting from the MAP_BY_FIRST_BYTE cache for the initial build.

### Follow-up spot-check after the cached filtered-map retry

> Same machine, same PHP version, same isolated-process benchmark style as above, but re-run only for the affected scenarios plus two representative non-Latin controls.

| Scenario | Master µs | Branch µs | Δ |
|---|---:|---:|---:|
| German slug — *"Düsseldorf"* | 4.25 | 4.83 | +14% |
| German sports headline | 6.61 | 5.22 | **−21%** |
| French headline with accents | 12.55 | 5.85 | **−53%** |
| French tourism paragraph | 6.27 | 5.35 | **−15%** |
| Russian headline | 29.63 | 9.86 | **−67%** |
| Chinese sentence | 21.16 | 16.41 | **−22%** |
| Long German text | 65.17 | 29.52 | **−55%** |

The follow-up caches the short Latin filtered replacement map after the first call, which resolves the remaining French headline regression in the spot-check and keeps the non-Latin and long-string wins intact. The only remaining spot-check loss is the short German slug (`+14%`), so the next benchmark pass should verify whether that case needs its own threshold tweak or can be accepted as noise.

### Security hardening (not reflected in timing)

The branch also adds UTF-8 validation hardening in `clean()` that rejects:
- C0/C1 overlong 2-byte sequences (`\xC0\x80` … `\xC1\xBF`)
- E0 overlong 3-byte sequences (`\xE0\x80\x80` … `\xE0\x9F\xBF`)
- ED UTF-16 surrogate pairs (`\xED\xA0\x80` … `\xED\xBF\xBF`)
- F0 overlong 4-byte sequences (`\xF0\x80\x80\x80` … `\xF0\x8F\xBF\xBF`)
- F5–F7 out-of-Unicode-range (`\xF5` … `\xF7`)

These are zero-overhead on valid input (the `clean()` call is already part of the pipeline) but prevent a class of Unicode smuggling attacks.

---

*Benchmark script: [`/tmp/bench_worker.php`] + [`/tmp/bench_full.php`]*  
*Raw JSON data: [`/tmp/bench_results.json`]*
