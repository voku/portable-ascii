# portable-ascii 2.2.x released

I just shipped the `2.2.x` release line of [`voku/portable-ascii`](https://github.com/voku/portable-ascii).

This update is mainly about making `ASCII::to_ascii()` more predictable in real-world usage, especially when the same process calls the method multiple times with different inputs and options. It also includes a small maintenance update for the repository automation.

## Highlights

- fixed a non-deterministic `ASCII::to_ascii()` code path so repeated calls stay consistent across warm and cold internal caches
- added focused regression coverage for mixed ASCII / non-ASCII map keys at length boundaries
- locked in the expected handling for long strings that contain accented characters and the degree sign across cleanup, transliteration, and retention modes
- migrated the Renovate configuration to the current recommended setup

## Why this release matters

The main bug fix came from a reported inconsistency where `to_ascii()` could behave differently depending on which internal maps had already been loaded earlier in the request lifecycle. The `2.2.x` release removes that surprise and adds tests around the edge cases that triggered it.

Thanks to [@aguingand](https://github.com/aguingand) for the report and failing test case that helped track this down.

## Update

```bash
composer require voku/portable-ascii:^2.2
```

or:

```bash
composer update voku/portable-ascii
```

## Links

- GitHub: https://github.com/voku/portable-ascii
- Changelog: https://github.com/voku/portable-ascii/blob/master/CHANGELOG.md
- Issue report: https://github.com/voku/portable-ascii/issues/135
- Sponsor / support: https://www.patreon.com/voku/posts

If you are using `portable-ascii` in production and spot another edge case around transliteration or cleanup, please open an issue on GitHub.
