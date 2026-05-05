# Transliterator Polyfill

This directory is a scaffold for a future standalone repository around `TransliteratorPolyfill`.

The current extraction step is intentionally thin: this package exposes a dedicated package surface while delegating behavior to `voku/portable-ascii`.

## Scope

- Public package surface: `Voku\Transliterator\TransliteratorPolyfill` and `Voku\Transliterator\Transliterator`
- Backing implementation: `voku\helper\TransliteratorPolyfill`
- Backing object wrapper: `voku\helper\Transliterator`
- Runtime dependency: `voku/portable-ascii`

## What this scaffold proves

- It proves the package boundary without copying the transliteration data tables yet
- It keeps object-style transliteration support clearly limited instead of pretending to be native ext-intl
- It leaves a clear path to a later standalone extraction if the `ASCII` data needs to move too

## Usage

```php
use Voku\Transliterator\Transliterator;
use Voku\Transliterator\TransliteratorPolyfill;

echo TransliteratorPolyfill::transliterate('Any-Latin; Latin-ASCII', 'déjà vu');
// "deja vu"

echo TransliteratorPolyfill::transliterate('de-ASCII', 'Ä Ö Ü ä ö ü ß');
// "AE OE UE ae oe ue ss"

$transliterator = Transliterator::create('Latin-ASCII');
echo $transliterator->transliterate('café');
// "cafe"
```

## Known limitations

- This package is a limited compatibility layer, not a full ICU implementation
- The object wrapper is intentionally limited and is not a drop-in replacement for native ext-intl `Transliterator`
- `Transliterator::createFromRules()` returns `null` with a warning because there is no full ICU parser/executor here
- `Transliterator::createInverse()` returns `null` with a warning because inverse transliterators are still out of scope
- Offsets use the documented codepoint subset behavior from the root helper, not ICU UTF-16 code-unit semantics
- For the full supported-step list and compatibility matrix, see the root `README.md` in this repository

## Migration from `voku/portable-ascii`

- Replace imports of `voku\helper\TransliteratorPolyfill` with `Voku\Transliterator\TransliteratorPolyfill`
- Keep the same `transliterate()` arguments and behavior
- This thin package still depends on `voku/portable-ascii` until a later full extraction

## Notes for this in-repo scaffold

`composer.json` includes a local path repository so this scaffold can be validated inside the current repository before being split into its own repository.
