<?php

namespace Brace\Session\Storages;

interface SessionStorageInterface
{

    /**
     * loads the $data written under the given $sessionID or returns null if no data exists
     *
     * @param string $sessionId
     * @return array|null
     */
    public function load(string $sessionId): ?array;

    /**
     * persists the given $data array under the $sessionId key into the chosen Storage
     *
     * @param string $sessionId
     * @param array $data
     */
    public function write(string $sessionId, array $data): void;

    /**
     * Destroy a session
     *
     * @param string $sessionId The session ID being destroyed.
     * @return void
     */
    public function destroy(string $sessionId): void;
}