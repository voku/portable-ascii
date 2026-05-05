# Transliterator Polyfill

This directory is a scaffold for a future standalone repository around `TransliteratorPolyfill`.

The current extraction step is intentionally thin: it validates a package boundary while delegating behavior to `voku/portable-ascii`.

## Scope

- Public package surface: `Voku\Transliterator\TransliteratorPolyfill` and `Voku\Transliterator\Transliterator`
- Backing implementation: `voku\helper\TransliteratorPolyfill`
- Backing object wrapper: `voku\helper\Transliterator`
- Runtime dependency: `voku/portable-ascii`

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

## Boundaries

- This is still a limited compatibility layer, not a full ICU implementation
- `Transliterator::createFromRules()` and `createInverse()` deliberately return `null` with warnings
- Offsets keep the root helper's documented codepoint subset behavior, not ICU UTF-16 code-unit semantics
- The full supported-step list and compatibility matrix stay in the root `README.md`

## Migration from `voku/portable-ascii`

- Replace imports of `voku\helper\TransliteratorPolyfill` with `Voku\Transliterator\TransliteratorPolyfill`
- Keep the same `transliterate()` arguments and behavior
- This thin package still depends on `voku/portable-ascii` until a later full extraction

## Notes for this in-repo scaffold

`composer.json` includes a local path repository so this scaffold can be validated inside the current repository before being split into its own repository.
