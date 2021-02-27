<?php

namespace Test;

use Brace\Core\BraceApp;
use Brace\Session\Session;
use Brace\Session\SessionMiddleware;
use Brace\Session\Storages\FileSessionStorage;
use Laminas\Diactoros\Response;
use PHPUnit\Framework\TestCase;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
        self::$fileSessionStorage = new FileSessionStorage("/tmp");
    }

    protected function setUp(): void
    {
        $this->middleware = new SessionMiddleware(self::$fileSessionStorage);
    }

    public static function tearDownAfterClass(): void
    {
        system('sudo rm -R /tmp/*');
    }

    protected static function getMethod($name, $obj): ReflectionMethod
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }


    public function testGenerateSession(): void
    {
        $generateSession = self::getMethod('generateSession', $this->middleware);
        self::assertTrue($generateSession->isPrivate());
        $responseCookies = [];
        $sessionId = $generateSession->invokeArgs($this->middleware, [&$responseCookies]);
        self::assertArrayHasKey($this->middleware::COOKIE_NAME, $responseCookies);
        $data = self::$fileSessionStorage->load($sessionId);
        self::assertArrayHasKey('__expires', $data);
        self::assertTrue($data['__expires'] >= time());
        self::assertEquals(32, strlen($sessionId));
    }

    public function testIsValidSessionAndIsPrivateMethod(): ReflectionMethod
    {
        $isValidSession = self::getMethod('isValidSession', $this->middleware);
        self::assertTrue($isValidSession->isPrivate());
        return $isValidSession;
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionDefaultArgs(ReflectionMethod $isValidSession): void
    {
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionArgumentIsNull(ReflectionMethod $isValidSession): void
    {
        self::assertFalse($isValidSession->invokeArgs($this->middleware, [null]));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionDataIsEmpty(ReflectionMethod $isValidSession): void
    {
        self::$fileSessionStorage->write('foo', []);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, ['foo']));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionDataIsExpired(ReflectionMethod $isValidSession): void
    {
        self::$fileSessionStorage->write('bar', ['__expires' => time() - 1]);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, ['bar']));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionDataIsValid(ReflectionMethod $isValidSession): void
    {
        self::$fileSessionStorage->write('foobar', ['__expires' => time() + 10]);
        self::assertTrue($isValidSession->invokeArgs($this->middleware, ['foobar']));
    }

}
