<?php

namespace spec\Brace\Session\Storages;

use Brace\Session\Storages\FileSessionStorage;
use Brace\Session\Storages\SessionStorageInterface;
use Phore\ObjectStore\Driver\FileSystemObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;
use PhpSpec\ObjectBehavior;

class FileSessionStorageSpec extends ObjectBehavior
{
    private string $directory;

    function let()
    {
        $this->directory = sys_get_temp_dir() . '/brace-session-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
        $this->beConstructedWith(new ObjectStore(new FileSystemObjectStoreDriver($this->directory)));
    }

    function letGo()
    {
        foreach (glob($this->directory . '/*.json') as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    function it_implements_the_storage_interface()
    {
        $this->shouldHaveType(FileSessionStorage::class);
        $this->shouldImplement(SessionStorageInterface::class);
    }

    function it_writes_and_loads_data()
    {
        $this->write('foo', ['foo' => 'bar']);
        $this->write('bar', ['bar' => 'foo']);
        if (!file_exists($this->directory . '/foo.json') || !file_exists($this->directory . '/bar.json')) {
            throw new \RuntimeException('Session files were not written');
        }
        $this->load('foo')->shouldReturn(['foo' => 'bar']);
        $this->load('bar')->shouldReturn(['bar' => 'foo']);
    }

    function it_returns_null_for_missing_data()
    {
        $this->load('missing')->shouldReturn(null);
    }

    function it_destroys_data()
    {
        $this->write('foo', ['foo' => 'bar']);
        $this->destroy('foo');
        $this->load('foo')->shouldReturn(null);
        if (file_exists($this->directory . '/foo.json')) {
            throw new \RuntimeException('Session file was not removed');
        }
    }
}
