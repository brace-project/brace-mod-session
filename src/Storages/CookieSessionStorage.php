<?php

namespace Brace\Session\Storages;

class CookieSessionStorage implements SessionStorageInterface
{

    public function __construct(
        private $secretKey,
        private $cookieName = "X-SESS-D",
        /**
         * @var string SameSite attribute of the cookie (None, Lax, Strict). Defaults to 'Lax'. Use None for CORS
         */
        private $sameSite = "Lax"
    ){
        if (strlen($this->secretKey) < 16)
            throw new \UnexpectedValueException("Encryption key needs at least 24 bytes");
    }

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

        [$nonce, $message] = explode(".", $_COOKIE[$this->cookieName], 2);

        $nonce = base64_decode($nonce); $message = base64_decode($message);

        try {
            $data = sodium_crypto_secretbox_open($message, $nonce, substr(sodium_crypto_generichash($this->secretKey), 0, 32));
        } catch (\Exception $e) {
            return null;
        }

        if ($data === false || $data === null)
            return null;

        $data = json_decode($data, true);
        if ($data["sess_id"] !== $sessionId) {
            return null;
        }
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

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $encrypted = sodium_crypto_secretbox(json_encode($data), $nonce, substr(sodium_crypto_generichash($this->secretKey), 0, 32));

        $cookieOptions = [
            'expires' => time() + 3600 * 24, // 1 day
            'path' => '/',            
            'httponly' => true,
            'samesite' => $this->sameSite // Could be 'None', 'Lax', or 'Strict', depending on your needs
        ];

        // Ensure HTTPS is used if SameSite is 'None'
        if ($cookieOptions['samesite'] === 'None') {
            $cookieOptions['secure'] = true;
        }

        setcookie($this->cookieName, base64_encode($nonce) . "." . base64_encode($encrypted), $cookieOptions);
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
