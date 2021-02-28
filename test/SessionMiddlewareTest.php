<?php

namespace Test;

use Brace\Core\BraceApp;
use Brace\Session\Session;
use Brace\Session\SessionMiddleware;
use Brace\Session\Storages\FileSessionStorage;
use Laminas\Diactoros\Response;
use Phore\Core\Exception\NotFoundException;
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
     * @throws ReflectionException
     * @throws NotFoundException
     */
    public function testGenerateSession(): void
    {
        $generateSession = self::getMethod('generateSession', $this->middleware);
        self::assertTrue($generateSession->isPrivate());
        $sessionId = $generateSession->invokeArgs($this->middleware, []);
        $data = self::$fileSessionStorage->load($sessionId);
        self::assertArrayHasKey('__expires', $data);
        self::assertTrue($data['__expires'] >= time());
        self::assertEquals(32, strlen($sessionId));
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
        $rightside = explode('=', trim($explode[1]));
        $sessId = $leftside[1];
        self::assertEquals('SESSID', $leftside[0]);
        self::assertEquals('path', $rightside[0]);
        self::assertEquals('session.cookie_path', $rightside[1]);
        $data = self::$fileSessionStorage->load($sessId);
        self::assertArrayHasKey('foo', $data);
        self::assertArrayHasKey('__expires', $data);
        self::assertEquals('bar', $data['foo']);
        self::assertTrue($data['__expires'] > time());

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
        $data = self::$fileSessionStorage->load($sessId);
        self::assertArrayHasKey('foo', $data);
        self::assertArrayHasKey('__expires', $data);
        self::assertEquals('bar', $data['foo']);
        self::assertTrue($data['__expires'] > time());

        //send Request with same Cookie
        //change value of 'foo'
        $response = $this->middleware->process(
            $this->writingServerRequest($sessId),
            $this->writingMiddleware($app, 'foobar')
        );
        $cookies = $response->getHeader('Set-Cookie');
        $explode = explode(';', $cookies[0]);
        $leftside = explode('=', $explode[0]);
        $sessIdSecondRequest = $leftside[1];
        self::assertEquals($sessId, $sessIdSecondRequest);
        $data = self::$fileSessionStorage->load($sessId);
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
