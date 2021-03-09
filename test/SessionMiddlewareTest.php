<?php

namespace Test;

use Brace\Core\BraceApp;
use Brace\Session\Session;
use Brace\Session\SessionMiddleware;
use Brace\Session\Storages\FileSessionStorage;
use Laminas\Diactoros\Response;
use Phore\ObjectStore\Driver\FileSystemObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;
use PHPUnit\Framework\TestCase;
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
        $isValidSession = self::getMethod('isValidSession', $this->middleware);
        self::assertTrue($isValidSession->isPrivate());
        return $isValidSession;
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionSessionIdIsNull(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', null);
        self::getChangeValue($this->middleware, 'sessionData', []);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionArrayKeyDoesntExists(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', null);
        self::getChangeValue($this->middleware, 'sessionData', ["foo" => "bar"]);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionArrayKeyExistsButIsEmpty(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', null);
        self::getChangeValue($this->middleware, 'sessionData', ["sessionId" => '']);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionArrayKeyExistsButIsNull(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', null);
        self::getChangeValue($this->middleware, 'sessionData', ["sessionId" => null]);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionArrayKeyExistsButHashIsNotEqual(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', "foo");
        self::getChangeValue($this->middleware, 'sessionData', ["sessionId" => "test"]);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionIdExistsButDataIsNull(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', "foo");
        self::getChangeValue($this->middleware, 'sessionData', null);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionIdExistsButDataIsEmpty(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', "foo");
        self::getChangeValue($this->middleware, 'sessionData', []);
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionDataIsExpiredTtlNot(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', "foo");
        self::getChangeValue(
            $this->middleware,
            'sessionData',
            [
                'sessionId' => md5('foo'),
                '__expires' => time() - 1,
                '__ttl' => time() + 10
            ]
        );
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionTtlIsExpiredExpiresNot(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', "foo");
        self::getChangeValue(
            $this->middleware,
            'sessionData',
            [
                'sessionId' => md5('foo'),
                '__expires' => time() + 10,
                '__ttl' => time() - 1
            ]
        );
        self::assertFalse($isValidSession->invokeArgs($this->middleware, []));
    }

    /**
     * @depends testIsValidSessionAndIsPrivateMethod
     * @param ReflectionMethod $isValidSession
     * @throws ReflectionException
     */
    public function testIsValidSessionDataIsValid(ReflectionMethod $isValidSession): void
    {
        self::getChangeValue($this->middleware, 'sessionId', "foo");
        self::getChangeValue(
            $this->middleware,
            'sessionData',
            [
                'sessionId' => md5('foo'),
                '__expires' => time() + 10,
                '__ttl' => time() +10
            ]
        );
        self::assertTrue($isValidSession->invokeArgs($this->middleware, []));
    }

    public function testSessionMiddleware(): void
    {
        $app = new BraceApp();
        $this->middleware->_setApp($app);
        //send Request with empty Cookies
        $response = $this->middleware->process(
            $this->createMock(ServerRequestInterface::class),
            $this->writingMiddleware($app)
        );
        $cookies = $response->getHeader('Set-Cookie');
        $explode = explode(';', $cookies[0]);
        $leftside = explode('=', $explode[0]);
        $rightside = explode('=', trim($explode[2]));
        $sessId = $leftside[1];
        self::assertEquals('SESSID', $leftside[0]);
        self::assertEquals('path', $rightside[0]);
        self::assertEquals('session.cookie_path', $rightside[1]);
        $data = self::$fileSessionStorage->load(substr($sessId, 0, 32));
        self::assertArrayHasKey('foo', $data);
        self::assertArrayHasKey('__expires', $data);
        self::assertEquals('bar', $data['foo']);
        self::assertTrue($data['__expires'] > time());

        $this->middleware = new SessionMiddleware(self::$fileSessionStorage);
        $this->middleware->_setApp($app);

        //send Request with different SessID thats not saved
        $response = $this->middleware->process(
            $this->writingServerRequest(),
            $this->writingMiddleware($app)
        );
        $cookies = $response->getHeader('Set-Cookie');
        $explode = explode(';', $cookies[0]);
        $leftside = explode('=', $explode[0]);
        $sessIdSecondRequest = $leftside[1];
        self::assertNotEquals($sessId, $sessIdSecondRequest);
        $sessId = $leftside[1];
        self::assertEquals('SESSID', $leftside[0]);
        self::assertEquals('path', $rightside[0]);
        self::assertEquals('session.cookie_path', $rightside[1]);
        $data = self::$fileSessionStorage->load(substr($sessId, 0, 32));
        self::assertArrayHasKey('foo', $data);
        self::assertArrayHasKey('__expires', $data);
        self::assertEquals('bar', $data['foo']);
        self::assertTrue($data['__expires'] > time());

        //send Request with same Cookie
        //change value of 'foo'
        $this->middleware = new SessionMiddleware(self::$fileSessionStorage);
        $this->middleware->_setApp($app);

        $response = $this->middleware->process(
            $this->writingServerRequest($sessId),
            $this->writingMiddleware($app, 'foobar')
        );
        $cookies = $response->getHeader('Set-Cookie');
        $explode = explode(';', $cookies[0]);
        $leftside = explode('=', $explode[0]);
        $sessIdSecondRequest = $leftside[1];
        self::assertEquals($sessId, $sessIdSecondRequest);
        $data = self::$fileSessionStorage->load(substr($sessId, 0, 32));
        self::assertArrayHasKey('foo', $data);
        self::assertArrayHasKey('__expires', $data);
        self::assertEquals('foobar', $data['foo']);
        self::assertTrue($data['__expires'] > time());
    }

    private function writingMiddleware(BraceApp $app, string $value = 'bar'): RequestHandlerInterface
    {
        return $this->fakeDelegate(
            static function () use ($app, $value) {
                $session = $app->get(SessionMiddleware::SESSION_ATTRIBUTE);
                assert($session instanceof Session);
                $session->set('foo', $value);

                return new Response();
            }
        );
    }

    private function fakeDelegate(callable $callback): RequestHandlerInterface
    {
        $middleware = $this->createMock(RequestHandlerInterface::class);

        $middleware
            ->expects(self::once())
            ->method('handle')
            ->willReturnCallback($callback);

        return $middleware;
    }


    private function writingServerRequest(string $sessId = 'bar'): ServerRequestInterface
    {
        $middleware = $this->middleware;
        return $this->fakeCookieParams(
            static function () use ($middleware, $sessId) {
                return [$middleware::COOKIE_NAME => $sessId];
            }
        );
    }

    private function fakeCookieParams(callable $callback): ServerRequestInterface
    {
        $request = $this->createMock(ServerRequestInterface::class);

        $request
            ->expects(self::once())
            ->method('getCookieParams')
            ->willReturnCallback($callback);

        return $request;
    }
}
