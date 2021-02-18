<?php

namespace Brace\Session;

class Session
{

    private array $data;

    public function __construct(
        private array &$sessionData,
        private array $originalSessionData,
        private string $sessionId
    ){}

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
        unset($this->data[$key]);
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
        return $this->sessionData !== $this->originalSessionData;
    }

    public function isEmpty(): bool
    {
        return ! count($this->data);
    }

    public function jsonSerialize(): object
    {
        return (object) $this->data;
    }
}