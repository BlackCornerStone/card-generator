<?php

namespace CardGenerator\DTO\Source;

class AbstractSourceDTO
{
    /** @var array<string, mixed> */
    protected array $data;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function get(string $key)
    {
        return $this->data[$key] ?? null;
    }
}
