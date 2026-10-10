# TransliteratorPolyfill extraction: Slice 4 experiment assessment

> Date: 2026-10-10. Scope: experimental PR [#167](https://github.com/voku/portable-ascii/pull/167), issue [#162](https://github.com/voku/portable-ascii/issues/162).
> Evidence head: `fbe2986ff043bbff1a8f2492739a2dc9010c348f` (before adding this report).
> This is **not** a release approval, merge authorization, or assertion of full ICU compatibility.

## Question and decision

**Question:** Can the limited namespaced transliterator implementation and its canonical tests live in an independent Composer child package without breaking `voku/portable-ascii` and without putting the historical local Composer path override in the publishable manifest?

**Result: EXPERIMENT GO (feasibility), RELEASE NO-GO (not yet evaluated for publication).**

The child package is technically viable as a separate Composer consumer. Do **not** invest in cosmetics solely to green an experiment's quality badges. Do not waive real security issues either. Treat the current external quality findings as observed, not dismissed.

## Reproducible executed evidence

- [Child workflow, 15/15 jobs passed](https://github.com/voku/portable-ascii/actions/runs/38048418347):
  - PHPUnit on PHP 7.2–8.5, nine successful jobs. Example: PHP 7.2 **72 tests/112 assertions**, PHP 8.5 **72 tests/109 assertions**.
  - PHP 7.1 **production Composer installation and one runtime smoke**; **not full PHPUnit coverage**.
  - With no ext-intl, the full child suite passes; with no mbstring or without both extensions, **runtime smoke only** (PHPUnit requires mbstring).
  - Published `voku/portable-ascii 2.1.1` resolves and passes tests.
  - An **isolated CI-only** local `../../` path repository and synthetic `2.1.99` bind the child to the actual PR root checkout and the tests pass. The **committed child composer.json has no such override**.
  - PHPStan level 8: no errors.
- [Root workflow completed successfully](https://github.com/voku/portable-ascii/actions/runs/38048418352), including root PHPUnit, PHPStan and Infection. No root production autoload/dependency changes.
- Frozen #132 behavior was the reference, not merged as-is. The old duplicate root suites and historical workflow were not ported.

## Architecture and packaging

- **Dependency DAG:** `voku/transliterator-polyfill` -> `voku/portable-ascii:^2.1`. Root `composer.json` does **not** require the child. No production dependency cycle observed.
- The child has its own PSR-4 namespace `Voku\\Transliterator\\`; the root still exposes `voku\\`. The experiment does **not** register a global `transliterator_transliterate()` override.
- The publishable child manifest has a plausible package name, requirements and autoload, and no committed local-path or synthetic version. **A successful Composer resolution of an unpublished local package is not evidence that Packagist can install the child from a published distribution.**
- **Publication unresolved:** an actual separate owner repository, release tagging/repository URL correctness, Composer dist/Packagist discoverability, package archives, and a consumer install of the independently published child have **not** been verified. The manifest's `homepage` refers to `voku/transliterator-polyfill`; do not assume that repository exists.
- The implementation delegates mapping/transliteration to `voku\\helper\\ASCII`. This is a separate **distribution/API layer**, **not** a standalone mappings engine and **not** a reduction of the `portable-ascii` package's mapping/data footprint.
- There is still a contract mismatch worth deciding **before release**: `Transliterator::create()` accepts a syntactically valid unsupported ID and defers its rejection until `transliterate()`.

## Bounded compatibility contract

- Supported ICU IDs are intentionally limited. Unsupported IDs, raw ICU rules and reverse/inverse transliteration are not generally implemented; no global ICU function is replaced.
- Offsets are Unicode **codepoint** indices, not ICU UTF-16 code units. PHP 7 invalid offsets warn/return false, PHP 8 throws `ValueError`. This is an intentionally different interface, not full native compatibility.
- Unicode normalization may become a no-op when `Normalizer` is unavailable; Unicode case conversion differs when mbstring is absent. The tests demonstrate these behavioral boundaries, not universal ICU parity.
- No complete PHP 7.1 test-suite proof, no full no-mbstring PHPUnit run, and no package-dist consumer proof.

## Quality findings: triage, not fabricated closure

1. **SonarCloud:** latest available report at evidence head fails quality gate: **5.6% duplicated new code** (required <=3%), **new-code security rating C** (required A). The known open security annotations on the new CI workflow say *PHP dependencies should be locked to verified versions*. This is a real CI supply-chain reproducibility concern: the job performs `composer update --no-install` then `composer install`, which locks only **within that run**; subsequent runs may resolve different dependency versions. In a multi-version compatibility experiment, floating resolution is useful as a compatibility probe but is **not a reproducible release gate**. Future productization should use a fixed dependency baseline alongside a separately identified update/matrix job. Do not claim this finding has been fixed or falsely mark it a security exploit.
2. **Duplication:** the original #132 scaffold reportedly had 61.5% new-code duplication; #167 reports 5.6% and has only canonical child suites, no mirrored full root suite. The exact remaining duplicate line pairs were not available in the retrieved Sonar comments; **cause and safe remediation remain unverified**. No tests were dropped and no gates weakened for the metric.
3. **DeepSource: PHP:** external check is failing, with **no accessible confirmed rule-level diagnosis** from the GitHub statuses. [Analysis link](https://app.deepsource.com/gh/voku/portable-ascii/run/7ca7771a-e9dc-421e-ad54-2a50d536014a/php/). Do not guess a code defect or claim the finding cleared.
4. Actions checkout and PHP setup are pinned to full verified SHAs; credentials are not persisted. Historical workflow removed.
5. The PR remains **Draft** and must **not** be merged as release-ready on the above evidence alone.

## Suggested next decision after experiment

- **Recommended:** Keep the experiment as a draft and decide whether the child should become a separately maintained repository/package at all. This decision is independent from clean-up of `portable-ascii` mapping files; those are still owned by the root.
- **If yes (a separately authorized productization task):** settle the API/contract and PHP floor; verify owner repository and release/distribution mechanics; perform real consumer install from a published artifact; build a pinned supply-chain CI baseline plus an explicit floating-dependency compatibility matrix; inspect exact Sonar duplication pairs and DeepSource diagnostics; then decide on merge/tag/publication separately.
- **If no:** retain these executed results as a proof of concept; do not leave the PR marked release-ready.
- **No merge, no tag, no release, no security-rule relaxation** is authorized by the experiment verdict.
