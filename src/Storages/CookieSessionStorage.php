<?php

namespace Brace\Session\Storages;

class CookieSessionStorage implements SessionStorageInterface
{

    public function __construct(
        private $cookieName = "X-SESS-D"
    ){}

    /**
     * loads the $data written under the given $sessionID or returns null if no data exists
     *
     * @param string $sessionId
     * @return array|null
     */
    public function load(string $sessionId): ?array
    {
        if ( ! isset($_COOKIE[$this->cookieName]))
            return null;
        $data = json_decode($_COOKIE[$this->cookieName], true);
        if ($data["sess_id"] !== $sessionId)
            return null;
        return $data["data"];
    }

    /**
     * persists the given $data array under the $sessionId key into the chosen Storage
     *
     * @param string $sessionId
     * @param array $data
     */
    public function write(string $sessionId, array $data): void
    {
        $data = [
            "sess_id" => $sessionId,
            "data" => $data
        ];
        setcookie($this->cookieName, json_encode($data));
    }

    /**
     * Destroy a session
     *
     * @param string $sessionId The session ID being destroyed.
     * @return void
     */
    public function destroy(string $sessionId): void
    {
        setcookie($this->cookieName, null);
    }

}
