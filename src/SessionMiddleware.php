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
        $sessionData = $sessionId === null ? null : $this->sessionStorage->load($sessionId);
        if (!$this->isValidSession($sessionData)) {
            $sessionId = phore_random_str(32);
            $sessionData = $this->setUpDefaultSessionData();
        }
        //Todo: Update expires if is valid
        $responseCookies[self::COOKIE_NAME] = $sessionId;

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

    //Todo: define some DefaultSessionData and Write test for this method
    /**
     * Defines the Default Session Data
     *
     * @return int[]
     */
    private function setUpDefaultSessionData(): array
    {
        return ['__expires' => time() + $this->ttl];
    }

    /**
     * checks whether a given $sessionId is valid or not
     *
     * @param array|null $sessionData
     * @return bool
     */
    private function isValidSession(?array $sessionData): bool
    {
        if ($sessionData === null || $sessionData === []) {
            return false;
        }
        if (array_key_exists('__expires', $sessionData)
            && $sessionData['__expires'] < time()) { // Todo: max ttl reached ?
            return false;
        }
        return true;
    }

}