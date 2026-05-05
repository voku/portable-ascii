<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * Limited object-style transliterator polyfill.
 *
 * This is intentionally not a full ICU Transliterator implementation.
 * It only wraps the subset of transliterator IDs supported by TransliteratorPolyfill.
 */
final class Transliterator
{
    public const FORWARD = 0;
    public const REVERSE = 1;

    /**
     * @var string
     */
    private $id;

    private function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Create a limited transliterator wrapper for supported forward IDs.
     *
     * @return self|null
     */
    public static function create(string $id, int $direction = self::FORWARD)
    {
        if ($direction !== self::FORWARD) {
            \trigger_error(
                'transliterator_create(): polyfill only supports forward transliteration direction',
                \E_USER_WARNING
            );

            return null;
        }

        if (\preg_match('//u', $id) !== 1) {
            \trigger_error(
                'transliterator_create(): invalid UTF-8 in transliterator ID. Transliterator IDs must be valid UTF-8 strings.',
                \E_USER_WARNING
            );

            return null;
        }

        $id = \trim($id);
        $id = \preg_replace('/\s*;\s*/', ';', $id) ?? $id;
        $id = \rtrim($id, ';');

        if ($id === '') {
            \trigger_error(
                'transliterator_create(): polyfill received empty transliterator ID',
                \E_USER_WARNING
            );

            return null;
        }

        if (\strpos($id, '>') !== false || \strpos($id, '<') !== false) {
            \trigger_error(
                'transliterator_create(): polyfill does not support custom ICU rules',
                \E_USER_WARNING
            );

            return null;
        }

        return new self($id);
    }

    /**
     * The limited polyfill does not implement the full ICU rule parser/executor.
     *
     * @return self|null
     */
    public static function createFromRules(string $rules, int $direction = self::FORWARD)
    {
        unset($rules, $direction);

        \trigger_error(
            'transliterator_create_from_rules(): polyfill does not support the full ICU rule parser/executor',
            \E_USER_WARNING
        );

        return null;
    }

    /**
     * @return self|null
     */
    public function createInverse()
    {
        \trigger_error(
            'Transliterator::createInverse(): polyfill does not support inverse transliterators',
            \E_USER_WARNING
        );

        return null;
    }

    /**
     * @return string|false
     */
    public function transliterate(string $string, int $start = 0, int $end = -1)
    {
        return TransliteratorPolyfill::transliterate($this->id, $string, $start, $end);
    }

    public function getId(): string
    {
        return $this->id;
    }
}
