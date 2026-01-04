<?php

declare(strict_types=1);

namespace CardGenerator\DTO\Model;

use ArrayAccess;
use CardGenerator\DTO\Source\AbstractSourceDTO as SourceDTO;

/**
 * Base decorator for SourceDTO that exposes array-like access compatible with Twig.
 *
 * @implements ArrayAccess<string, mixed>
 */
abstract class AbstractModelDTO implements ArrayAccess
{
    protected SourceDTO $source;
    /** @var array<string, mixed> */
    protected array $extra = [];

    public function __construct(SourceDTO $source)
    {
        $this->source = $source;
    }

    public function getSource(): SourceDTO
    {
        return $this->source;
    }

    protected function set(string $key, mixed $value): void
    {
        $this->extra[$key] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->extra) || $this->source->has((string)$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (array_key_exists($offset, $this->extra)) {
            return $this->extra[$offset];
        }
        return $this->source->get((string)$offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->extra[(string)$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->extra[(string)$offset]);
    }

    public function __get(string $name): mixed
    {
        return $this->offsetGet($name);
    }

    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }
}
