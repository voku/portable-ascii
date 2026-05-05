<?php

declare(strict_types=1);

namespace Voku\Transliterator;

/**
 * Thin package-surface delegate around the root limited object polyfill.
 *
 * This scaffold intentionally reuses the root behavior so the in-repo package
 * boundary can be validated without copying the implementation a second time.
 */
final class Transliterator
{
    public const FORWARD = \voku\helper\Transliterator::FORWARD;
    public const REVERSE = \voku\helper\Transliterator::REVERSE;

    /**
     * @var \voku\helper\Transliterator
     */
    private $inner;

    private function __construct(\voku\helper\Transliterator $inner)
    {
        $this->inner = $inner;
    }

    /**
     * @return self|null
     */
    public static function create(string $id, int $direction = self::FORWARD)
    {
        $inner = \voku\helper\Transliterator::create($id, $direction);
        if ($inner === null) {
            return null;
        }

        return new self($inner);
    }

    /**
     * @return null
     */
    public static function createFromRules(string $rules, int $direction = self::FORWARD)
    {
        \voku\helper\Transliterator::createFromRules($rules, $direction);

        return null;
    }

    /**
     * @return null
     */
    public function createInverse()
    {
        $this->inner->createInverse();

        return null;
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
}
