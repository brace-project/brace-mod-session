<?php

namespace test;

use Brace\Session\Storages\FileSessionStorage;
use Brace\Session\Storages\SessionStorageInterface;
use Phore\Core\Exception\NotFoundException;
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
        $FileSessionStorage = new FileSessionStorage("/tmp");
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
        $FileSessionStorage->write("foo", ['foo'=> 'bar']);
        $FileSessionStorage->write("bar", ['bar'=> 'foo']);
        self::assertFileExists('../tmp/foo.json');
        self::assertFileExists('../tmp/bar.json');
        return $FileSessionStorage;
    }

    /**
     * @depends testWriteData
     * @param FileSessionStorage $FileSessionStorage
     * @throws NotFoundException
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
    public function testFileDoesntExistThrowsException(FileSessionStorage $FileSessionStorage): void
    {
        $this->expectException(NotFoundException::class);
        $FileSessionStorage->load("foobar");
    }

}
