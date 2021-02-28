<?php

namespace Brace\Session\Storages;

interface SessionStorageInterface
{
    /**
     * SessionStorageInterface constructor.
     * @param string $connection
     */
    public function __construct(string $connection);

    /**
     * loads the $data written under the given $sessionID
     *
     * @param string $sessionId
     * @return array
     */
    public function load(string $sessionId): array;

    /**
     * persists the given $data array under the $sessionId key into the chosen Storage
     *
     * @param string $sessionId
     * @param array $data
     */
    public function write(string $sessionId, array $data): void;

}