<?php

namespace Brace\Session\Storages;

class RedisSessionStorage implements SessionStorageInterface
{
    /** {@inheritdoc} */
    public function __construct(string $connection)
    {
    }

    /** {@inheritdoc} */
    public function load(string $sessionId): ?array
    {
        // TODO: Implement load() method.
    }

    /** {@inheritdoc} */
    public function write(string $sessionId, array $data): void
    {
        // TODO: Implement write() method.
    }
}