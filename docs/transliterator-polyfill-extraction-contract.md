# Transliterator polyfill extraction contract (proposal)

> Status: contract-first review for [#158](https://github.com/voku/portable-ascii/issues/158).
> Source of observed behavior: draft [#132](https://github.com/voku/portable-ascii/pull/132), head `f2bebb5677fe2dc978f799838c33968beeace338`.
> Target: current `master` at `b805306507d44d5bf9d63532f58a36aa0b4d9cec` (2026-10-09).
> **This document does not approve the implementation for release or claim native ICU compatibility.**

## Decision: shrink, do not rebase #132 wholesale

Keep #132 as an implementation/test reference and in Draft. Reimplement its agreed
limited API in small, independently testable slices against current `master`, starting
with a standalone child-package test owner. Do not carry both copies of the full test
suites or the local-only Composer repository into a released artifact. Do not change
the existing `voku\helper\ASCII` API as a side effect of extraction.

Rationale: #132 is 185 commits behind `master`, combines a new public API, packaging,
tooling and duplicated tests, and has no discoverable GitHub Actions run for its head.
The historic SonarQube report indicates 61.5% duplication on new code and two
security hotspots; those observations require verification, not dismissal.

## Public surface and ownership (observed, not yet approved)

| Surface | Actual implementation in #132 | Boundary |
| --- | --- | --- |
| `Voku\Transliterator\TransliteratorPolyfill::transliterate($idOrObject, string $input, int $start = 0, int $end = -1): string\|false` | Static entry; accepts an ID string or the package's own `Transliterator` object; explicitly rejects other types with `E_USER_WARNING` and `false`. | Namespaced class, **not** the global `transliterator_transliterate()` function. |
| `Voku\Transliterator\Transliterator::create(string $id, int $direction = FORWARD): ?self` | Creates a forward wrapper after UTF-8, empty-ID and rule-syntax checks; warns and returns `null` for reverse direction. | **Gap:** does not validate whether each normalized ID step is supported until later `transliterate()`. Do not promise eagerly validated IDs. |
| `Transliterator::transliterate(string $input, int $start = 0, int $end = -1): string\|false` / `getId(): string` | Delegates to static implementation; ID normalization strips empty pipeline segments. | Wrapper is not native `\Transliterator` and has no native object guarantees. |
| `Transliterator::createFromRules(...)` / `createInverse()` | Always emit `E_USER_WARNING` and return `null`. | No ICU custom-rule parser, inverse mapping, or reverse transliteration. |

Evidence: [polyfill entry and pipeline](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/src/TransliteratorPolyfill.php#L84-L180),
[wrapper](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/src/Transliterator.php#L13-L117),
[ID normalization](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/src/TransliteratorId.php#L13-L36).

## Supported ID grammar (observed)

- Semicolon-separated steps; surrounding whitespace removed, empty segments ignored, case-insensitive built-in names.
- Built-ins: `NFC`, `NFD`, `NFKC`, `NFKD`, `[:Nonspacing Mark:] Remove` (including compact spelling), `Any-Latin`, `Latin-ASCII`, `Any-ASCII`, `Any-Upper`, `Any-Lower`.
- The `Any-Latin`, `Latin-ASCII`, and `Any-ASCII` branches all call `ASCII::to_transliterate($input, null, false)`; this does **not** establish equivalence with each distinct ICU transliterator.
- Known language aliases accept `<alias>-ASCII` and `<alias>-Latin` optionally followed by `/BGN`:
  `amharic`, `arabic`, `azerbaijani`, `belarusian`, `bulgarian`, `bengali`, `greek`, `persian`, `armenian`, `georgian`, `kazakh`, `kirghiz`, `korean`, `macedonian`, `mongolian`, `oriya`, `pashto`, `russian`, `serbian`, `thai`, `turkmen`, `ukrainian`, `uzbek`, `han`, `de`, `de_at`, `de_ch`.
- `de` expands umlauts (including titlecase handling); `de_at`/`de_ch` use nonexpanding conversions. Other aliases go through `ASCII::to_ascii` with a mapped language code.
- Unsupported IDs/steps (for example `Latin; Title`, `Katakana-Hiragana`) are rejected with `E_USER_WARNING` and `false` at the static transliteration entry. Custom rule syntax containing `<` or `>` is also rejected. Empty/semicolon-only IDs and invalid UTF-8 in the ID/input are rejected with the same warning/false policy.
- The wrapper `create()` rejects malformed IDs and custom-rule syntax but **not** unsupported step names; its behavior needs an explicit API decision before implementation promotion.

Evidence: [supported constants and alias table](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/src/TransliteratorPolyfill.php#L28-L77),
[step recognition](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/src/TransliteratorPolyfill.php#L395-L445),
[unsupported cases](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/tests/TransliteratorPolyfillUpstreamCompatibilityTest.php#L106-L203).

## Offset and error semantics (observed; intentionally different from ICU)

| Condition | #132 behavior | Evidence / gate |
| --- | --- | --- |
| Default `(0, -1)` | Entire input string is transliterated. | `TransliteratorPolyfill.php` lines 175-180. |
| Positive `start`/`end` | Slice by **Unicode codepoints** using `/./us`; `end` is exclusive. ICU uses UTF-16 code units. Emoji makes this difference visible. | `TransliteratorPolyfill.php` lines 186-203; `testOffsetSemanticsAreCodepointBasedInsteadOfUtf16Units`. |
| Out-of-range positive offsets / empty range | Clamp to string length; if start >= end after clamping, return input unchanged without warning. Native ICU may report an error. | `testErrorPhptStartPastStringLengthReturnsInputUnchanged`. |
| `start < 0`, `end < -1`, or `start > end` with explicit end | PHP 8+: throw `ValueError`; PHP 7.x: emit `E_USER_WARNING`, return `false`. | `TransliteratorPolyfill.php` lines 303-331 and invalid-offset tests. |
| Invalid UTF-8 / unsupported rule or ID at static entry | Emit `E_USER_WARNING`, return `false`. | `TransliteratorPolyfill.php` lines 97-173. |
| `createFromRules()`, `createInverse()`, reverse direction | Emit `E_USER_WARNING`, return `null`. | `Transliterator.php` lines 33-103. |

Do not describe this as a transparent polyfill for global ICU APIs. Byte-for-byte
ICU result parity, UTF-16 offset semantics, error class/message parity and handling
of all valid ICU rules are **unverified or explicitly unsupported**. The upstream
compatibility test file is an adapted subset, not a native-ICU conformance suite.

## Runtime/extension policy

- The child `composer.json` claims PHP `>=7.1.0`, matching the root package's PHP floor. The child CI scaffold lists PHP 7.2–8.5 only: **7.1 is not evidenced by the scaffold**; either prove it with a compatible test runner or approve a higher floor before release.
- `Normalizer` is not required by Composer. For `NFC`/`NFD`/`NFKC`/`NFKD`, the implementation uses `class_exists('Normalizer', true)`; without ext-intl or a compatible autoloaded polyfill, normalization silently becomes a **no-op**. Even `Normalizer::normalize()` returning `false` silently preserves the input. Output therefore depends on extensions; this is not ICU parity.
- `Any-Upper`/`Any-Lower` use `mb_strtoupper`/`mb_strtolower` if available, otherwise PHP's byte-oriented `strtoupper`/`strtolower`. Verify no-mbstring Unicode behavior before documenting guarantees.
- The package's namespaced implementation does not replace the global ext-intl functions and can coexist with ext-intl. Presence of ext-intl can still change results through `Normalizer`.

Evidence: [package composer.json](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/composer.json),
[normalization/case branches](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/src/TransliteratorPolyfill.php#L209-L295),
[conditional-normalizer tests](https://github.com/voku/portable-ascii/blob/f2bebb5677fe2dc978f799838c33968beeace338/packages/transliterator-polyfill/tests/TransliteratorPolyfillTest.php#L470-L508).

## Composer and release boundary

- Proposed Composer package: `voku/transliterator-polyfill`, PSR-4 `Voku\Transliterator\` under `src/`, with an explicit runtime dependency on `voku/portable-ascii:^2.1`. It calls `voku\helper\ASCII::to_ascii()` and `ASCII::to_transliterate()`. A separate package name is **not** an independent transliteration engine or extracted ASCII data owner.
- The draft child `composer.json` adds a `repositories: [{type: path, url: ../../, options: {symlink: true, versions: {voku/portable-ascii: 2.1.99}}}]` entry. This is **test-harness only**. A published `composer.json` must not depend on a local path or synthetic release; test a separate release manifest against actual published `voku/portable-ascii` versions.
- Safe approach: keep the publishable child manifest free of `repositories`; inject a path-repository override only in isolated CI/test Composer configuration, and verify the copied/dist package manifest has no `../../` or `2.1.99`. Record the exact path-vs-published dependency-resolution evidence.
- Confirm dependency direction remains child -> portable-ascii. Do not introduce a portable-ascii runtime requirement on the child (cycle), and do not move `voku\helper\ASCII` accidentally.

## Tests: one owner, executable verification

- Canonical full suites belong in `packages/transliterator-polyfill/tests/` (functional and adapted upstream compatibility). #132 duplicates the two large classes under root `tests/` with primarily a namespace substitution. That duplicate content explains much of the reported Sonar duplication; **do not delete until the child suite actually runs**.
- Root should retain only **small integration smoke tests** for explicitly supported installation/wiring scenarios, not a mirrored 539+206-line suite. They must run with child classes installed/autoloadable; current root Composer autoload exposes only `voku\` under `src/voku/`.
- The child `.github/workflows/ci.yml` resides below `packages/transliterator-polyfill/` and is not activated as a root GitHub Actions workflow by this repository. Add a **root-level workflow** with child-package `working-directory` or a separate-package CI run that shows executed jobs and logs.
- Required positive/negative tests: built-in + language aliases; order of pipelines; nonspacing-mark normalization with and without Normalizer; malformed UTF-8; rule rejection; unknown ID passed to `create()` then `transliterate()`; reverse direction; PHP7 warnings vs PHP8 `ValueError`; supplementary Unicode codepoint offsets vs native ICU UTF-16; null-byte and other known divergences; mbstring present/absent.
- Required matrix: PHP 7.2–8.5 as scaffolded; resolve the claimed PHP 7.1 floor; ext-intl on/off (and Normalizer-polyfill on/off where relevant); root integration + isolated child suites + PHPStan; package creation from the actual publishable manifest and dependency graph.
- Security/quality gate: investigate both reported Sonar security hotspots on the actual files and remeasure duplication after the test-owner split. Require source-specific findings and passing current CI before considering release. A historical failing external status is not a substitute for evidence.

## Sequenced follow-up gates

1. **Contract review** (this document, #158): accept or explicitly revise public names, supported IDs, error/offset policy, runtime/extension floor and dependency owner. No runtime changes in this slice.
2. **One test owner + executable matrix** on current `master`: add isolated package CI and only minimal root smoke; prove execution and identify whether draft tests pass before removing duplicates.
3. **Minimal implementation port**: introduce only the API and behavior supported by gate 1, with tests from gate 2. Address `create()` deferred validation explicitly; avoid unreviewed ICU parity claims.
4. **Release hygiene**: publishable Composer manifest, actual dependency-resolution proof, security/duplication gates, documentation and separate release decision. Do not merge/rebase the old #132 wholesale.

**Approval status:** proposal, not a compatibility or release approval. No test in this document is claimed to have executed on #132's historical head.
