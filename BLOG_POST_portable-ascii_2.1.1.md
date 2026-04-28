# portable-ascii 2.1.1 released

I just shipped `portable-ascii` [`2.1.1`](https://github.com/voku/portable-ascii), which wraps up the broader `2.1.x` refresh with one more regression-coverage fix for long-string `ASCII::to_ascii()` behavior.

## Highlights

- optimized `ASCII::to_ascii()` and `ASCII::to_transliterate()` hot paths for ASCII, Latin, long-text, and multilingual inputs
- hardened invalid UTF-8 handling across `clean()`, `to_ascii()`, and `to_transliterate()`
- expanded regression coverage for malformed UTF-8, transliteration boundaries, slug loops, and long-string edge cases
- modernized the CI setup, refreshed PHPUnit support, and kept the package compatible down to PHP `7.1`
- fixed the final long-string degree-sign regression coverage issue in `2.1.1`

## Performance check: 2.0.3 -> 2.1.1

I reran the new performance harness against both tags with the same benchmark file.

- `to_ascii()` on short ASCII input improved from `3.032` to `1.023 µs/op` (`-66%`)
- `to_ascii()` on long ASCII input improved from `8.408` to `2.200 µs/op` (`-74%`)
- Greek long-input `to_ascii()` improved from `1350.599` to `69.263 µs/op` (`-95%`)
- Myanmar long-input `to_ascii()` improved from `1233.893` to `79.348 µs/op` (`-94%`)
- Chinese long-input transliteration via `to_ascii()` improved from `985.336` to `24.088 µs/op` (`-98%`)
- localized `to_slugify()` runs improved across the board, with Russian around `-44%` and Turkish around `-38%`

The only small regression in this sample was short Latin `to_transliterate()` (`7.733` -> `7.899 µs/op`, about `+2%`), which is well within the context of the much larger wins elsewhere in the release.

## Why this release matters

The `2.1.x` line is not just cleanup. It makes the common transliteration and slug-generation paths meaningfully cheaper while also tightening correctness around malformed UTF-8 and tricky replacement boundaries. `2.1.1` then locks in the long-string behavior with targeted regression coverage so the performance work stays safe.

## Update

```bash
composer require voku/portable-ascii:^2.1
```

or:

```bash
composer update voku/portable-ascii
```

## Links

- GitHub: https://github.com/voku/portable-ascii
- Changelog: https://github.com/voku/portable-ascii/blob/master/CHANGELOG.md
- Performance notes: https://github.com/voku/portable-ascii/blob/master/PERFORMANCE.md
- Sponsor/support: https://www.patreon.com/voku/posts

If you are using `portable-ascii` on multilingual input, this is the release line to pick up.
