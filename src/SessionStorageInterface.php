<?php

namespace Brace\Session;

interface SessionStorageInterface
{
    public function load(string $sessionId): array;

    public function write(string $sessionId, array $data): void;

}