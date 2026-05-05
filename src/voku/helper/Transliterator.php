<?php

declare(strict_types=1);

namespace voku\helper;

/**
 * Backward-compatible wrapper around the extracted transliterator package.
 */
final class Transliterator
{
    public const FORWARD = 0;
    public const REVERSE = 1;

    /**
     * @var \Voku\Transliterator\Transliterator
     */
    private $inner;

    private function __construct(\Voku\Transliterator\Transliterator $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @return self|null
     */
    public static function create(string $id, int $direction = self::FORWARD)
    {
        $inner = \Voku\Transliterator\Transliterator::create($id, $direction);

        return $inner === null ? null : new self($inner);
    }

    /**
     * @return null
     */
    public static function createFromRules(string $rules, int $direction = self::FORWARD)
    {
        return \Voku\Transliterator\Transliterator::createFromRules($rules, $direction);
    }

    /**
     * @return null
     */
    public function createInverse()
    {
        return $this->inner->createInverse();
    }

    /**
     * @return string|false
     */
    public function transliterate(string $string, int $start = 0, int $end = -1)
    {
        return $this->inner->transliterate($string, $start, $end);
    }

    public function getId(): string
    {
        return $this->inner->getId();
    }

    public function toPackageTransliterator(): \Voku\Transliterator\Transliterator
    {
        return $this->inner;
    }
}
