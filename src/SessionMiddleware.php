<?php

namespace Brace\Session;

use Brace\Core\Base\BraceAbstractMiddleware;
use Brace\Core\Helper\Cookie;
use Brace\Session\Storages\SessionStorageInterface;
use JetBrains\PhpStorm\ArrayShape;
use Phore\Di\Container\Producer\DiService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class SessionMiddleware extends BraceAbstractMiddleware
{

    public const SESSION_DI_NAME = 'session';

    public function __construct(
        private SessionStorageInterface $sessionStorage,
        private int $ttl = 3600,
        private int $expires = 86400,
        private string $cookieName = "X-SESS",
        private string $cookiePath = "/"
    ) {
    }


    public function _loadSession(ServerRequestInterface $request, &$sessionDataRef, &$sessionId) : Session|null
    {
        $sessionId = $request->getCookieParams()[$this->cookieName] ?? null;
        if ($sessionId === null)
            return null;

        $sessionDataRef = $this->sessionStorage->load(substr($sessionId, 0, 32));
        if ($sessionDataRef === null)
            return null;

        if ( ! $this->isValidSessionData($sessionDataRef, $sessionId))
            return null;
        return new Session($sessionDataRef["data"], $sessionId);
    }

    /**
     * Creates a Session if the SESSION_ATTRIBUTE is loaded in the DI Container
     *
     * @param SessionMiddleware $that
     * @param ServerRequestInterface $request
     * @return callable
     */
    public function _createSession(string $sessionId, &$sessionDataRef): Session
    {
        $sessionDataRef = [
            "__sid_hash" => sha1($sessionId),
            "__ttl" => time() + $this->ttl,
            "__expires" => time() + $this->expires,
            "data" => []
        ];
        return new Session($sessionDataRef["data"], $sessionId);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $newSessionId = null;

        $loadedSessionId = null;
        $sessionDataRef = null;

        $this->app->define(
            self::SESSION_DI_NAME,
            new DiService(
                function () use ($request, &$newSessionId, &$sessionDataRef, &$loadedSessionId) {
                    $session = $this->_loadSession($request,$sessionDataRef, $loadedSessionId);

                    if ($session === null) {
                        $newSessionId = phore_random_str(64);
                        return $this->_createSession($newSessionId, $sessionDataRef);
                    } else {
                        return $session;
                    }

                }
            )
        );

        // Process next middleware
        $response = $handler->handle($request);

        // Attach new SessionId Cookie (if needed)
        if ($newSessionId !== null) {
            $response = Cookie::setCookie(
                $response,
                $this->cookieName,
                $newSessionId,
                0,
                $this->cookiePath
            );
            $this->sessionStorage->write(substr($newSessionId, 0, 32), $sessionDataRef);
        }

        // Check for updated session data
        if ($loadedSessionId !== null) {
            if ($this->app->get(self::SESSION_DI_NAME, Session::class)->hasChanged())
                $this->sessionStorage->write(substr($newSessionId, 0, 32), $sessionDataRef);
        }

        return $response;
    }




    /**
     * checks whether a given $sessionId is valid or not
     *
     * @return bool
     */
    private function isValidSessionData(array $sessionData, string $sessionId): bool
    {
        if ( ! isset($sessionData["__sid_hash"]) || $sessionData["__sid_hash"] !== sha1($sessionId)) {
            return false;
        }
        if ($sessionData['__expires'] < time()) {
            return false;
        }
        if ($sessionData['__ttl'] < time()) {
            return false;
        }
        return true;
    }

}
