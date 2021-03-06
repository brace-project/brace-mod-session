<?php

namespace Test;

use Brace\Session\Storages\FileSessionStorage;
use Brace\Session\Storages\SessionStorageInterface;
use Phore\ObjectStore\Driver\FileSystemObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;
use PHPUnit\Framework\TestCase;

class FileSessionStorageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        system('sudo rm -R /tmp/*');
    }

    public static function tearDownAfterClass(): void
    {
        system('sudo rm -R /tmp/*');
    }

    public function testImplementsSessionStorageInterface(): FileSessionStorage
    {
        $FileSessionStorage = new FileSessionStorage(new ObjectStore(new FileSystemObjectStoreDriver("/tmp")));
        self::assertInstanceOf(SessionStorageInterface::class, $FileSessionStorage);
        return $FileSessionStorage;
    }

    /**
     * @depends testImplementsSessionStorageInterface
     * @param FileSessionStorage $FileSessionStorage
     * @return FileSessionStorage
     */
    public function testWriteData(FileSessionStorage $FileSessionStorage): FileSessionStorage
    {
        $FileSessionStorage->write("foo", ['foo' => 'bar']);
        $FileSessionStorage->write("bar", ['bar' => 'foo']);
        self::assertFileExists('/tmp/foo.json');
        self::assertFileExists('/tmp/bar.json');
        return $FileSessionStorage;
    }

    /**
     * @depends testWriteData
     * @param FileSessionStorage $FileSessionStorage
     */
    public function testLoadData(FileSessionStorage $FileSessionStorage): void
    {
        $data = $FileSessionStorage->load("foo");
        $expected = ['foo' => 'bar'];
        self::assertEquals($expected, $data);
        $data = $FileSessionStorage->load("bar");
        $expected = ['bar' => 'foo'];
        self::assertEquals($expected, $data);
    }

    /**
     * @depends testWriteData
     * @param FileSessionStorage $FileSessionStorage
     */
    public function testFileDoesntExistReturnNull(FileSessionStorage $FileSessionStorage): void
    {
        self::assertEquals(null, $FileSessionStorage->load("foobar"));
    }

    /**
     * @depends testWriteData
     * @param FileSessionStorage $FileSessionStorage
     */
    public function testDestroy(FileSessionStorage $FileSessionStorage): void
    {
        $FileSessionStorage->destroy("foo");
        self::assertFileDoesNotExist("/tmp/foo.json");
    }

}
