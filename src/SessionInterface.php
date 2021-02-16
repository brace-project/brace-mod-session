<?php


interface SessionInterface extends JsonSerializable{

    /**
     * Stores a given value in the Session
     *
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value): void;

    /**
     * Retrieves a value from the session - if the value doesn't exist, then it uses the given $default
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Removes the contents of the session
     *
     * @param string $key
     */
    public function remove(string $key): void;

    /**
     * Clears the contents of the Session
     */
    public function clear(): void;

    /**
     * Checks whether a given key exists in the session
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Checks whether the session has changed its contents since its lifecycle start
     *
     * @return bool
     */
    public function hasChanged(): bool;

    /**
     * Checks whether the session contains any data
     *
     * @return bool
     */
    public function isEmpty(): bool;

    public function jsonSerialize(): object;

}