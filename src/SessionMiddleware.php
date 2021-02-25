<?php

namespace Brace\Session;

use Brace\Core\Base\BraceAbstractMiddleware;
use Brace\Session\Storage\SessionStorageInterface;
use Phore\Di\Container\Producer\DiService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class SessionMiddleware extends BraceAbstractMiddleware
{

    public const COOKIE_NAME = 'SESSID';
    public const COOKIE_PATH = 'session.cookie_path';

    public function __construct(
        private SessionStorageInterface $sessionStorage,
        private int $ttl = 86400,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $responseCookies = [];
        $requestCookies = $request->getCookieParams();
        $sessionId = $requestCookies[self::COOKIE_NAME];
        if (!$this->isValidSession($sessionId)) { //Todo: update expires if Session is Valid
            $sessionId = $this->generateSession($responseCookies);
        }
        $sessionData = $this->sessionStorage->load($sessionId);

        $this->app->define('session', new DiService(function () use ($sessionId, &$sessionData) {
            return new Session($sessionData, $sessionData, $sessionId);
        }));

        $response = $handler->handle($request);
        $this->sessionStorage->write($sessionId, $sessionData);

        //Todo: destroy Session
        //Todo: max Expires

        foreach ($responseCookies as $key => $value) {
            $response = $response->withHeader(
                'Set-Cookie',
                sprintf(
                    "%s=%s; path=%s",
                    $key,
                    $value,
                    self::COOKIE_PATH
                )
            );
        }
        return $response;
    }

    private function generateSession(array &$responseCookies): string
    {
        $sessionId = phore_random_str(32);
        $responseCookies[self::COOKIE_NAME] = $sessionId;
        $this->sessionStorage->write($sessionId, ['__expires' => time() + $this->ttl]);
        return $sessionId;
    }

    private function isValidSession(string $sessionId = null): bool
    {
        if ($sessionId === null) {
            return false;
        }
        $data = $this->sessionStorage->load($sessionId);
        if ($data === null) {
            return false;
        }
        if ($data['__expires'] < time()) {
            return false;
        }
        return true;
    }
}