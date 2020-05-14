[![Build Status](https://travis-ci.org/voku/portable-ascii.svg?branch=master)](https://travis-ci.org/voku/portable-ascii)
[![Build status](https://ci.appveyor.com/api/projects/status/gnejjnk7qplr7f5t/branch/master?svg=true)](https://ci.appveyor.com/project/voku/portable-ascii/branch/master)
[![Coverage Status](https://coveralls.io/repos/voku/portable-ascii/badge.svg?branch=master&service=github)](https://coveralls.io/github/voku/portable-ascii?branch=master)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/997c9bb10d1c4791967bdf2e42013e8e)](https://www.codacy.com/app/voku/portable-ascii)
[![Latest Stable Version](https://poser.pugx.org/voku/portable-ascii/v/stable)](https://packagist.org/packages/voku/portable-ascii) 
[![Total Downloads](https://poser.pugx.org/voku/portable-ascii/downloads)](https://packagist.org/packages/voku/portable-ascii)
[![License](https://poser.pugx.org/voku/portable-ascii/license)](https://packagist.org/packages/voku/portable-ascii)
[![Donate to this project using Paypal](https://img.shields.io/badge/paypal-donate-yellow.svg)](https://www.paypal.me/moelleken)
[![Donate to this project using Patreon](https://img.shields.io/badge/patreon-donate-yellow.svg)](https://www.patreon.com/voku)

# 🔡 Portable ASCII

## Description

It is written in PHP (PHP 7+) and can work without "mbstring", "iconv" or any other extra encoding php-extension on your server. 

The benefit of Portable ASCII is that it is easy to use, easy to bundle.

The project based on ...
+ Portable UTF-8 work (https://github.com/voku/portable-utf8) 
+ Daniel St. Jules's work (https://github.com/danielstjules/Stringy) 
+ Johnny Broadway's work (https://github.com/jbroadway/urlify)
+ and many cherry-picks from "github"-gists and "Stack Overflow"-snippets ...

## Index

* [Alternative](#alternative)
* [Install](#install-portable-ascii-via-composer-require)
* [Why Portable ASCII?](#why-portable-ascii)
* [Requirements and Recommendations](#requirements-and-recommendations)
* [Usage](#usage)
* [Class methods](#class-methods)
* [Unit Test](#unit-test)
* [License and Copyright](#license-and-copyright)

## Alternative

If you like a more Object Oriented Way to edit strings, then you can take a look at [voku/Stringy](https://github.com/voku/Stringy), it's a fork of "danielstjules/Stringy" but it used the "Portable ASCII"-Class and some extra methods. 

```php
// Portable ASCII
use voku\helper\ASCII;
ASCII::to_transliterate('déjà σσς iıii'); // 'deja sss iiii'

// voku/Stringy
use Stringy\Stringy as S;
$stringy = S::create('déjà σσς iıii');
$stringy->toTransliterate();              // 'deja sss iiii'
```

## Install "Portable ASCII" via "composer require"
```shell
composer require voku/portable-ascii
```

##  Why Portable ASCII?[]()
I need ASCII char handling in different classes and before I added this functions into "Portable UTF-8",
but this repo is more modular and portable, because it has no dependencies.

## Requirements and Recommendations

*   No extensions are required to run this library. Portable ASCII only needs PCRE library that is available by default since PHP 4.2.0 and cannot be disabled since PHP 5.3.0. "\u" modifier support in PCRE for ASCII handling is not a must.
*   PHP 7.0 is the minimum requirement

## Usage

Example: ASCII::to_ascii()
```php
  echo ASCII::to_ascii('�Düsseldorf�', 'de');
  
  // will output
  // Duesseldorf

  echo ASCII::to_ascii('�Düsseldorf�', 'en');
  
  // will output
  // Dusseldorf
```

# Portable ASCII | API

The API from the "ASCII"-Class is written as small static methods.


## Class methods

<table><tr><td><a href="#charsarraybool-replace_extra_symbols">charsArray</a>
</td><td><a href="#charsarraywithmultilanguagevaluesbool-replace_extra_symbols-arraystringarrayintstring">charsArrayWithMultiLanguageValues</a>
</td><td><a href="#charsarraywithonelanguagestring-language-bool-replace_extra_symbols-bool-asorigreplacearray">charsArrayWithOneLanguage</a>
</td><td><a href="#charsarraywithsinglelanguagevaluesbool-replace_extra_symbols-bool-asorigreplacearray">charsArrayWithSingleLanguageValues</a>
</td></tr><tr><td><a href="#cleanstring-str-bool-normalize_whitespace-bool-keep_non_breaking_space-bool-normalize_msword-bool-remove_invisible_characters-string">clean</a>
</td><td><a href="#getalllanguages-arraystringstring">getAllLanguages</a>
</td><td><a href="#is_asciistring-str-bool">is_ascii</a>
</td><td><a href="#normalize_mswordstring-str-string">normalize_msword</a>
</td></tr><tr><td><a href="#normalize_whitespacestring-str-bool-keepnonbreakingspace-bool-keepbidiunicodecontrols-string">normalize_whitespace</a>
</td><td><a href="#remove_invisible_charactersstring-str-bool-url_encoded-string-replacement-string">remove_invisible_characters</a>
</td><td><a href="#to_asciistring-str-string-language-bool-remove_unsupported_chars-bool-replace_extra_symbols-bool-use_transliterate-boolnull-replace_single_chars_only-string">to_ascii</a>
</td><td><a href="#to_filenamestring-str-bool-use_transliterate-string-fallback_char-string">to_filename</a>
</td></tr><tr><td><a href="#to_slugifystring-str-string-separator-string-language-array-replacements-bool-replace_extra_symbols-bool-use_str_to_lower-bool-use_transliterate-string">to_slugify</a>
</td><td><a href="#to_transliteratebool-str-stringnull-unknown-bool-strict-string">to_transliterate</a>
</td></tr></table>

## charsArray(bool $replace_extra_symbols): 
<a href="#class-methods">↑</a>
Returns an replacement array for ASCII methods.

EXAMPLE: <code>
$array = ASCII::charsArray();

var_dump($array['ru']['б']); // 'b'
</code>

**Parameters:**
- `bool $replace_extra_symbols [optional] <p>Add some more replacements e.g. "£" with " pound ".</p>`

**Return:**
- `array`

--------

## charsArrayWithMultiLanguageValues(bool $replace_extra_symbols): array<string,array<int,string>>
<a href="#class-methods">↑</a>


**Parameters:**
- `bool $replace_extra_symbols`

**Return:**
- `array<string,array<int,string>>`

--------

## charsArrayWithOneLanguage(string $language, bool $replace_extra_symbols, bool $asOrigReplaceArray): 
<a href="#class-methods">↑</a>


**Parameters:**
- `string $language`
- `bool $replace_extra_symbols`
- `bool $asOrigReplaceArray`

**Return:**
- `array`

--------

## charsArrayWithSingleLanguageValues(bool $replace_extra_symbols, bool $asOrigReplaceArray): 
<a href="#class-methods">↑</a>


**Parameters:**
- `bool $replace_extra_symbols`
- `bool $asOrigReplaceArray`

**Return:**
- `array`

--------

## clean(string $str, bool $normalize_whitespace, bool $keep_non_breaking_space, bool $normalize_msword, bool $remove_invisible_characters): string
<a href="#class-methods">↑</a>


**Parameters:**
- `string $str`
- `bool $normalize_whitespace`
- `bool $keep_non_breaking_space`
- `bool $normalize_msword`
- `bool $remove_invisible_characters`

**Return:**
- `string`

--------

## getAllLanguages(): array<string,string>
<a href="#class-methods">↑</a>
Get all languages from the constants "ASCII::.*LANGUAGE_CODE".

**Parameters:**
__nothing__

**Return:**
- `array<string,string>`

--------

## is_ascii(string $str): bool
<a href="#class-methods">↑</a>


**Parameters:**
- `string $str`

**Return:**
- `bool`

--------

## normalize_msword(string $str): string
<a href="#class-methods">↑</a>


**Parameters:**
- `string $str`

**Return:**
- `string`

--------

## normalize_whitespace(string $str, bool $keepNonBreakingSpace, bool $keepBidiUnicodeControls): string
<a href="#class-methods">↑</a>


**Parameters:**
- `string $str`
- `bool $keepNonBreakingSpace`
- `bool $keepBidiUnicodeControls`

**Return:**
- `string`

--------

## remove_invisible_characters(string $str, bool $url_encoded, string $replacement): string
<a href="#class-methods">↑</a>


**Parameters:**
- `string $str`
- `bool $url_encoded`
- `string $replacement`

**Return:**
- `string`

--------

## to_ascii(string $str, string $language, bool $remove_unsupported_chars, bool $replace_extra_symbols, bool $use_transliterate, bool|null $replace_single_chars_only): string
<a href="#class-methods">↑</a>


**Parameters:**
- `string $str`
- `string $language`
- `bool $remove_unsupported_chars`
- `bool $replace_extra_symbols`
- `bool $use_transliterate`
- `bool|null $replace_single_chars_only`

**Return:**
- `string`

--------

## to_filename(string $str, bool $use_transliterate, string $fallback_char): string
<a href="#class-methods">↑</a>


**Parameters:**
- `string $str`
- `bool $use_transliterate`
- `string $fallback_char`

**Return:**
- `string`

--------

## to_slugify(string $str, string $separator, string $language, array $replacements, bool $replace_extra_symbols, bool $use_str_to_lower, bool $use_transliterate): string
<a href="#class-methods">↑</a>


**Parameters:**
- `string $str`
- `string $separator`
- `string $language`
- `array $replacements`
- `bool $replace_extra_symbols`
- `bool $use_str_to_lower`
- `bool $use_transliterate`

**Return:**
- `string`

--------

## to_transliterate(bool $str, string|null $unknown, bool $strict): string
<a href="#class-methods">↑</a>


**Parameters:**
- `bool $str`
- `null|string $unknown`
- `bool $strict`

**Return:**
- `string`

--------



## Unit Test

1) [Composer](https://getcomposer.org) is a prerequisite for running the tests.

```
composer install
```

2) The tests can be executed by running this command from the root directory:

```bash
./vendor/bin/phpunit
```

### Support

For support and donations please visit [Github](https://github.com/voku/portable-ascii/) | [Issues](https://github.com/voku/portable-ascii/issues) | [PayPal](https://paypal.me/moelleken) | [Patreon](https://www.patreon.com/voku).

For status updates and release announcements please visit [Releases](https://github.com/voku/portable-ascii/releases) | [Twitter](https://twitter.com/suckup_de) | [Patreon](https://www.patreon.com/voku/posts).

For professional support please contact [me](https://about.me/voku).

### Thanks

- Thanks to [GitHub](https://github.com) (Microsoft) for hosting the code and a good infrastructure including Issues-Managment, etc.
- Thanks to [IntelliJ](https://www.jetbrains.com) as they make the best IDEs for PHP and they gave me an open source license for PhpStorm!
- Thanks to [Travis CI](https://travis-ci.com/) for being the most awesome, easiest continous integration tool out there!
- Thanks to [StyleCI](https://styleci.io/) for the simple but powerful code style check.
- Thanks to [PHPStan](https://github.com/phpstan/phpstan) && [Psalm](https://github.com/vimeo/psalm) for really great Static analysis tools and for discover bugs in the code!

### License and Copyright

Released under the MIT License - see `LICENSE.txt` for details.
