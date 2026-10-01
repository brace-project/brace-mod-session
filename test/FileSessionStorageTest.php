<?php

namespace Test;

use Brace\Session\Storages\FileSessionStorage;
use Brace\Session\Storages\SessionStorageInterface;
use Phore\ObjectStore\Driver\FileSystemObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;
use PHPUnit\Framework\TestCase;

class FileSessionStorageTest extends TestCase
{
    private static string $directory;

    public static function setUpBeforeClass(): void
    {
        self::$directory = sys_get_temp_dir() . '/brace-mod-session-' . bin2hex(random_bytes(8));
        mkdir(self::$directory);
    }

    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$directory . '/*.json') as $file) {
            unlink($file);
        }
        rmdir(self::$directory);
    }

    public function testImplementsSessionStorageInterface(): FileSessionStorage
    {
        $FileSessionStorage = new FileSessionStorage(new ObjectStore(new FileSystemObjectStoreDriver(self::$directory)));
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
        self::assertFileExists(self::$directory . '/foo.json');
        self::assertFileExists(self::$directory . '/bar.json');
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
        self::assertFileDoesNotExist(self::$directory . '/foo.json');
    }

}
