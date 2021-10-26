<?php

namespace Brace\Session\Storages;

use Exception;
use InvalidArgumentException;
use Phore\Core\Exception\NotFoundException;
use Phore\ObjectStore\ObjectStore;

class FileSessionStorage implements SessionStorageInterface
{
    private ObjectStore $objectStore;

    public function __construct(ObjectStore $objectStore)
    {
        if(!class_exists(ObjectStore::class)){
            throw new InvalidArgumentException('ObjectStore package missing please install Phore\ObjectStore');
        }
        $this->objectStore = $objectStore;
    }

    /** {@inheritdoc} */
    public function load(string $sessionId): ?array
    {
        try {
            return $this->objectStore->object($sessionId . ".json")->getJson();
        } catch (NotFoundException) {
            return null;
        }
    }

    /** {@inheritdoc} */
    public function write(string $sessionId, array $data): void
    {
        $this->objectStore->object($sessionId . ".json")->putJson($data);
    }

    /** {@inheritdoc} */
    public function destroy(string $sessionId): void
    {
        try {
            $this->objectStore->object($sessionId . ".json")->remove();
        } catch (Exception) {
        }
    }
}