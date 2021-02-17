<?php

namespace Brace\Session;

class Session
{

    private array $data;

    public function __construct(
        private array &$sessionData,
        private string $sessionId
    )
    {}

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function get(string $key)
    {
        return $this->sessionData[$key];
    }

    public function remove(string $key): void
    {
        // TODO: Implement remove() method.
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function hasChanged(): bool
    {
        // TODO: Implement hasChanged() method.
    }

    public function isEmpty(): bool
    {
        // TODO: Implement isEmpty() method.
    }

    public function jsonSerialize(): object
    {
        // TODO: Implement jsonSerialize() method.
    }
}