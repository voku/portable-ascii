# Changelog

### 1.3.4 (2019-10-14)

- fix static cache for "ASCII::charsArrayWithOneLanguage"

### 1.3.3 (2019-10-14)

- fix "Turkish" mapping -> 'ä' -> 'a'

### 1.3.2 (2019-10-14)

- fix language parameter usag with e.g. "de_DE"
- re-add missing "extra"-mapping chars

### 1.3.1 (2019-10-13)

- fix "ASCII::to_slugify" -> remove unicode chars
- add more test for ascii chars in the mapping
- fix non ascii chars in the mapping

### 1.3.0 (2019-10-12)

- add transliteration "fr" (was supported before, but with chars from other languages)
- add transliteration "ru" - Passport (2013), ICAO
- add transliteration "ru" - GOST 7.79-2000(B)
- add transliteration "el" - greeklish
- add transliteration "zh"
- add transliteration "nl"
- add transliteration "it"
- add transliteration "mk"
- add transliteration "pt"
- add constants -> ASCII::*LANGUAGE_CODES
- add more special latin chars / (currency) symbols
- add simple tests for all supported languages
- optimize "Russian" to ASCII (via "translit.ru")
- optimize performance of string replacement
- optimize performance of array merging
- optimize phpdoc comments
- "ASCII::to_transliterate" -> use "transliterator_create" + static cache
- "ASCII::to_ascii" -> fix "remove unsupported chars"
- "ASCII::to_ascii" -> add some more special chars
- run/fix static analyse via "pslam" + "phpstan" 
- auto fix code style via "php-cs-fixer"
- fix transliteration for "german"
- fix transliteration for "persian" (thanks @mardep)
- fix transliteration for "polish" (thanks @dariusz.drobisz)
- fix transliteration for "bulgarian" (thanks @mkosturkov)
- fix transliteration for "croatian" (thanks @ludifonovac)
- fix transliteration for "serbian" (thanks @ludifonovac)
- fix transliteration for "swedish" (thanks @nicholasruunu)
- fix transliteration for "france" (thanks @sharptsa)
- fix transliteration for "serbian" (thanks @nikolaposa)
- fix transliteration for "czech" (thanks @slepic)

### 1.2.3 (2019-09-10)

- fix language depending ASCII chars (the order matters)

### 1.2.2 (2019-09-10)

- fix bulgarian ASCII chars | thanks @bgphp

### 1.2.1 (2019-09-07)

- "charsArray()" -> add access to "ASCII::$ASCII_MAPS*""

### 1.2.0 (2019-09-07)

- "to_slugify()" -> use the extra ascii array

### 1.1.0 (2019-09-07)

- add + split extra ascii replacements

### 1.0.0 (2019-09-05)

- initial commit