<?php

namespace Brace\Session\Storages;

interface SessionStorageInterface
{

    public function __construct(string $connection);

    public function load(string $sessionId): array;

    public function write(string $sessionId, array $data): void;

}