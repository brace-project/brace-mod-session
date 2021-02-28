<?php

namespace Brace\Session\Storages;

use Phore\Core\Exception\NotFoundException;
use Phore\ObjectStore\Driver\FileSystemObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;

class FileSessionStorage implements SessionStorageInterface
{
    private ObjectStore $objectStore;

    /** {@inheritdoc} */
    public function __construct(string $connection)
    {
        $this->objectStore = new ObjectStore(new FileSystemObjectStoreDriver($connection));
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
}