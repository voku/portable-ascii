# Transliterator Polyfill

This directory is prepared as a copy-ready basis for a future standalone repository around `TransliteratorPolyfill`.

## Scope

- Public package surface: `Voku\Transliterator\TransliteratorPolyfill` and `Voku\Transliterator\Transliterator`
- Package-owned implementation files: `src/TransliteratorPolyfill.php`, `src/Transliterator.php`, `src/TransliteratorId.php`
- Root `src/voku/helper/*Transliterator*` files in `voku/portable-ascii` are compatibility shims; the package files are the implementation source of truth
- Runtime dependency retained on `voku/portable-ascii` for the underlying `voku\helper\ASCII` transliteration data and helpers
- Package test coverage includes the full transliterator-specific suite plus the php-src / ICU compatibility matrix

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
- Offsets keep the documented codepoint subset behavior, not ICU UTF-16 code-unit semantics
- The package tests include the extracted php-src / ICU compatibility cases plus the broader polyfill behavior suite so they can move with the code

## Migration from `voku/portable-ascii`

- Replace imports of `voku\helper\TransliteratorPolyfill` with `Voku\Transliterator\TransliteratorPolyfill`
- Keep the same `transliterate()` arguments and behavior
- Keep `voku/portable-ascii` as a dependency until the ASCII data and helper layer are extracted too

## Notes for manual extraction

- `composer.json` still includes a local path repository so this package can be validated inside the current repository before it is copied into its own repository
- The copy set is this directory’s `src/`, `tests/`, `composer.json`, `phpunit.xml`, `phpstan.neon`, `README.md`, and `LICENSE.txt`
