# Extracted data integration candidate

This branch requires `voku/portable-ascii-mapping-data:^1.0`, retaining full functionality by default.
The data repository is https://github.com/voku/portable-ascii-mapping-data; its reviewed development commit
is `3a5ae61a58e31cbad24f7014070e724bf38682fe`. There are no stable mapping tags yet. This branch cannot be consumed
as a normal stable dependency until its separate version/migration gate is approved.

## Local development without publication

```sh
COMPOSER="$(php build/footprint/development-composer.php)" composer update --prefer-dist
php vendor/bin/phpunit -c phpunit.xml
```

The helper writes a temporary application manifest with absolute checkout paths, a VCS
repository and a pinned `dev-main#<sha> as 1.0.0` provider alias. It installs the checkout's
vendor directory without altering the production manifest or requiring a release tag.
Composer reads branch metadata; retain the generated lockfile and reviewed provider manifest.

## Explicit application opt-out

A root application can declare `replace: {"voku/portable-ascii-mapping-data": "1.0.0"}` only after auditing
its executed paths. This deliberately suppresses installation rather than implementing
equivalent functionality. Pin the reviewed data version; never use `*`. A reached missing
provider throws an actionable RuntimeException rather than silently changing outputs.

Non-strict Unicode bank paths, UTF8::to_ascii and transliterating filenames need this provider. Native intl does not replace non-strict mappings. Strict/native paths must be tested with their actual inputs and extensions.

Direct consumers of `src/voku/helper/data/x*.php` must migrate to
`Voku\PortableAsciiMappingData\BankMap::load()`; those internal files are removed in this candidate.
Public library signatures and retained byte/language/support data remain unchanged.
Do not merge or publish this as a compatibility-only patch: moved paths/new dependencies
require their own major-version/migration review. Roll back by reverting this extraction
commit; the earlier in-place branch remains available.
