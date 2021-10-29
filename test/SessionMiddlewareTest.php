<?php

namespace Test;


use Brace\Session\SessionMiddleware;
use Brace\Session\Storages\FileSessionStorage;
use Phore\ObjectStore\Driver\FileSystemObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class SessionMiddlewareTest extends TestCase
{
    protected static FileSessionStorage $fileSessionStorage;
    private SessionMiddleware $middleware;

    public static function setUpBeforeClass(): void
    {
        system('sudo rm -R /tmp/*');
        self::$fileSessionStorage = new FileSessionStorage(new ObjectStore(new FileSystemObjectStoreDriver("/tmp")));
    }

    protected function setUp(): void
    {
        $this->middleware = new SessionMiddleware(self::$fileSessionStorage);
    }

    public static function tearDownAfterClass(): void
    {
        system('sudo rm -R /tmp/*');
    }

    /**
     * Gets the private Method of a class and sets its accessibility
     *
     * @param $name
     * @param $obj
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    protected static function getMethod($name, $obj): ReflectionMethod
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Gets the private property of a class and sets its value
     *
     * @param $obj
     * @param string $property
     * @param $value
     * @return void
     * @throws ReflectionException
     */
    protected static function getChangeValue($obj, string $property, $value): void
    {
        $reflectionClass = new ReflectionClass($obj);
        $reflectionMethod = $reflectionClass->getProperty($property);
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->setValue($obj, $value);
    }

    /**
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    public function testIsValidSessionAndIsPrivateMethod(): ReflectionMethod
    {
        $isValidSession = self::getMethod('isValidSessionData', $this->middleware);
        self::assertTrue($isValidSession->isPrivate());
        return $isValidSession;
    }


}
