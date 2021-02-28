<?php

namespace Brace\Session;

use Brace\Core\Base\BraceAbstractMiddleware;
use Brace\Session\Storages\SessionStorageInterface;
use Phore\Di\Container\Producer\DiService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class SessionMiddleware extends BraceAbstractMiddleware
{

    public const COOKIE_NAME = 'SESSID';
    public const COOKIE_PATH = 'session.cookie_path';
    public const SESSION_ATTRIBUTE = 'session';

    public function __construct(
        private SessionStorageInterface $sessionStorage,
        private int $ttl = 86400,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $responseCookies = [];
        $requestCookies = $request->getCookieParams();
        $sessionId = $requestCookies[self::COOKIE_NAME] ?? null;
        if (!$this->isValidSession($sessionId)) {
            $sessionId = $this->generateSession();
        }
        $responseCookies[self::COOKIE_NAME] = $sessionId;
        /*
         * Todo: else part to update ttl
         * Todo: max ttl reached destroy Session create new ?
         */
        $sessionData = $this->sessionStorage->load($sessionId);

        $this->app->define(
            self::SESSION_ATTRIBUTE,
            new DiService(
                function () use ($sessionId, &$sessionData) {
                    return new Session($sessionData, $sessionData, $sessionId);
                }
            )
        );

        $response = $handler->handle($request);
        $this->sessionStorage->write($sessionId, $sessionData);


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

    /**
     * generates a new SessionId and also writes it into the $sessionStorage
     *
     * @return string
     */
    private function generateSession(): string
    {
        $sessionId = phore_random_str(32);
        $this->sessionStorage->write($sessionId, ['__expires' => time() + $this->ttl]);
        return $sessionId;
    }

    /**
     * checks whether a given $sessionId is valid or not
     *
     * @param string|null $sessionId
     * @return bool
     */
    private function isValidSession(string $sessionId = null): bool
    {
        if ($sessionId === null) {
            return false;
        }
        $data = $this->sessionStorage->load($sessionId);
        if ($data === null || $data === []) {
            return false;
        }
        if ($data['__expires'] < time()) {
            return false;
        }
        return true;
    }
}