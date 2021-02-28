<?php

namespace Test;

use Brace\Core\Base\BraceAbstractMiddleware;
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

    public function testSessionMiddleware(): void
    {
        $app = new BraceApp();
        $this->middleware->_setApp($app);
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

}
