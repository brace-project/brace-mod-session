<?php

namespace Brace\Session;

use Brace\Core\Base\BraceAbstractMiddleware;
use Brace\Session\Storages\SessionStorageInterface;
use JetBrains\PhpStorm\ArrayShape;
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
        if ($sessionId !== null) {
            $sessionData = $this->sessionStorage->load($sessionId);
            $this->sessionStorage->destroy($sessionId);
        } else {
            $sessionData = null;
        }
        if (!$this->isValidSession($sessionData)) {
            $sessionId = phore_random_str(32);
            $sessionData = $this->setUpDefaultSessionData();
        } else {
            $this->renewSessionData($sessionData);
        }
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
        $session = $this->app->get(self::SESSION_ATTRIBUTE);
        if ($session->hasChanged() && !$session->isEmpty()) {
            $this->sessionStorage->write($sessionId, $sessionData);
        }

        $response = $response->withHeader(
            'Set-Cookie',
            sprintf(
                "%s=%s; path=%s",
                self::COOKIE_NAME,
                $responseCookies[self::COOKIE_NAME],
                self::COOKIE_PATH
            )
        );
        return $response;
    }


    /**
     * Defines the Default Session Data
     *
     * @return int[]
     */
    #[ArrayShape(['__expires' => "int"])] private function setUpDefaultSessionData(): array
    {
        //Todo: define some DefaultSessionData and Write test for this method
        return ['__expires' => time() + $this->ttl]; //Todo: useful params? start time
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
            && $sessionData['__expires'] < time()) {
            return false;
        }
        // Todo: max ttl reached ?
        return true;
    }

    /**
     * Renews the session expiration time
     *
     * @param array $sessionData
     */
    private function renewSessionData(array &$sessionData): void
    {
        $sessionData['__expires'] = time() + $this->ttl;
    }


}