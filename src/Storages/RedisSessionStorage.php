<?php


namespace Brace\Session;


class RedisSessionStorage implements SessionStorageInterface
{
    public function __construct(string $connection)
    {
    }

    public function load(string $sessionId): array
    {
        // TODO: Implement load() method.
    }

    public function write(string $sessionId, array $data): void
    {
        // TODO: Implement write() method.
    }


}