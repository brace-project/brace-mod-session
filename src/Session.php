<?php

namespace Brace\Session;

use JetBrains\PhpStorm\Pure;

class Session
{

    private array $originalSessionData;

    public function __construct(
        private array &$sessionData,
        private string $sessionId
    )
    {
        $this->originalSessionData = $this->sessionData;
    }

    /**
     * Retrieve all data for purposes of persistence.
     */
    public function toArray(): array
    {
        return $this->sessionData;
    }

    /**
     * Set a value within the session.
     *
     * Values MUST be serializable in any format; we recommend ensuring the
     * values are JSON serializable for greatest portability.
     *
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, mixed $value): void
    {
        $this->sessionData[$key] = $value;
    }

    /**
     * Retrieve a value from the session.
     *
     * @template T
     * @param string $key
     * @param class-string<T>|null $cast
     * @return T
     */
    public function get(string $key, string $cast = null, bool $silentFail=false): mixed
    {
        $val = $this->sessionData[$key] ?? null;


        if ($cast !== null) {
            try {

                if ($val === null)
                    throw new \InvalidArgumentException("Value of session key '$key' is null");
                return phore_hydrate($val, $cast);
            } catch (\Exception $e) {
                if ($silentFail)
                    return null;
                throw new \InvalidArgumentException("Cannot cast session value '$key' to $cast: " . $e->getMessage(), 0, $e);
            }
        }

        return $val;
    }

    /**
     * Remove a value from the session.
     * @param string $key
     */
    public function remove(string $key): void
    {
        unset($this->sessionData[$key]);
    }

    /**
     * Clear all values.
     */
    public function clear(): void
    {
        $this->sessionData = [];
    }

    /**
     * Whether or not the container has the given key.
     * @param string $key
     * @return bool
     */
    #[Pure] public function has(string $key): bool
    {
        return array_key_exists($key, $this->sessionData);
    }

    /**
     * Checks whether the session has changed its contents since its lifecycle start
     */
    #[Pure] public function hasChanged(): bool
    {
        //print_r ($this->sessionData); print_r($this->originalSessionData);
        //print_r(array_diff($this->sessionData, $this->originalSessionData));

        return $this->sessionData !== $this->originalSessionData;
    }

    /**
     * Checks whether the session contains any data
     */
    #[Pure] public function isEmpty(): bool
    {
        return !count($this->sessionData);
    }

    public function _getData(): array
    {
        return $this->sessionData;
    }

    private $isDestroyed = false;

    public function destroy()
    {
        $this->isDestroyed = true;
    }

    public function isDestroyed() : bool
    {
        return $this->isDestroyed;
    }

}

