# Transliterator Polyfill

This directory is a scaffold for a future standalone repository around `TransliteratorPolyfill`.

The first extraction step is intentionally thin: this package exposes a dedicated package surface while delegating behavior to `voku/portable-ascii`.

## Scope

- Public package surface: `Voku\Transliterator\TransliteratorPolyfill`
- Backing implementation: `voku\helper\TransliteratorPolyfill`
- Runtime dependency: `voku/portable-ascii`
- Optional normalization support remains optional through `Normalizer`

## Why this shape first

- It proves the package boundary without copying the transliteration data tables yet
- It preserves current behavior while keeping the new package small
- It leaves a clear path to a later standalone extraction if the `ASCII` data needs to move too

## Usage

```php
use Voku\Transliterator\TransliteratorPolyfill;

echo TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'déjà vu');
// "deja vu"

echo TransliteratorPolyfill::transliterate('de-ASCII', 'Ä Ö Ü ä ö ü ß');
// "AE OE UE ae oe ue ss"
```

## Supported pipeline steps

- `NFC`, `NFD`, `NFKC`, `NFKD`
- `[:Nonspacing Mark:] Remove`
- `Any-Latin`, `Latin-ASCII`, `Any-ASCII`
- `Any-Upper`, `Any-Lower`
- Limited language aliases such as `de-ASCII`, `de_AT-ASCII`, `de_CH-ASCII`, and `Turkmen-Latin/BGN`

## Upstream-derived compatibility contract

The tests in this scaffold are adapted from PHP's own `ext/intl` transliterator PHPT tests:

- `ext/intl/tests/transliterator_transliterate_basic.phpt`
- `ext/intl/tests/transliterator_transliterate_error.phpt`
- `ext/intl/tests/transliterator_transliterate_variant1.phpt`

This package is a limited compatibility helper, not full ICU.

| Upstream PHPT case | Expected package behavior |
| --- | --- |
| `Latin; Title` transliteration | `false` + warning |
| Supported offset slicing | transliterates only the selected slice |
| Invalid UTF-8 string or ID | `false` + warning |
| ICU rule strings | `false` + warning |
| Stringable object first argument | `false` + warning |

## Known limitations

- This package does not register a global `transliterator_transliterate()` function
- Custom ICU rules using `>` / `<` operators are not supported
- Some mappings still differ from ICU data for selected scripts
- Null bytes are stripped by the underlying ASCII transliteration logic
- Unsupported ICU rules intentionally return `false`

## Migration from `voku/portable-ascii`

- Replace imports of `voku\helper\TransliteratorPolyfill` with `Voku\Transliterator\TransliteratorPolyfill`
- Keep the same `transliterate()` arguments and behavior
- This thin package still depends on `voku/portable-ascii` until a later full extraction

## Notes for this in-repo scaffold

`composer.json` includes a local path repository so this scaffold can be validated inside the current repository before being split into its own repository.
