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

    public const COOKIE_NAME = 'SESSID';
    public const COOKIE_PATH = 'session.cookie_path';
    public const SESSION_ATTRIBUTE = 'session';
    private array $responseCookies = [];
    private ?Session $session = null;
    private ?string $sessionId = null;
    private ?array $sessionData = null;

    public function __construct(
        private SessionStorageInterface $sessionStorage,
        private int $ttl = 3600,
        private int $expires = 86400
    ) {
    }



    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->app->define(
            self::SESSION_ATTRIBUTE,
            new DiService(
                function () use ($request) {
                    return $this->createSession($this, $request)();
                }
            )
        );

        $response = $handler->handle($request);
        $session = $this->app->get(self::SESSION_ATTRIBUTE);

        return $this->handleSessionResponse($response);
    }

    /**
     * Creates a Session if the SESSION_ATTRIBUTE is loaded in the DI Container
     *
     * @param SessionMiddleware $that
     * @param ServerRequestInterface $request
     * @return callable
     */
    public function createSession(SessionMiddleware $that, ServerRequestInterface $request): callable
    {
        return static function () use ($that, $request) {
            $requestCookies = $request->getCookieParams();
            $that->sessionId = $requestCookies[self::COOKIE_NAME] ?? null;
            $that->sessionData = $that->sessionStorage->load(substr($that->sessionId, 0, 32));

            if (!$that->isValidSession()) {
                $that->sessionId = phore_random_str(64);
                $that->sessionData = $that->setUpDefaultSessionData();
            } else {
                $that->renewSessionData();
            }
            $that->responseCookies[self::COOKIE_NAME] = $that->sessionId;
            $that->session = new Session($that->sessionData, $that->sessionData, $that->sessionId);
            return $that->session;
        };
    }

    /**
     * Defines the Default Session Data
     *
     * @return array
     */
    #[ArrayShape([
        'sessionId' => "string",
        '__ttl' => "int",
        '__expires' => "int"
    ])] private function setUpDefaultSessionData(): array
    {
        return [
            'sessionId' => md5($this->sessionId),
            '__ttl' => time() + $this->ttl,
            '__expires' => time() + $this->expires
        ];
    }

    /**
     * checks whether a given $sessionId is valid or not
     *
     * @return bool
     */
    private function isValidSession(): bool
    {
        if ($this->sessionId === null) {
            return false;
        }
        if ($this->sessionData === null || $this->sessionData === []) {
            return false;
        }
        if (!array_key_exists('sessionId', $this->sessionData) ||
            empty(trim($this->sessionData['sessionId'])) ||
            md5($this->sessionId) !== $this->sessionData['sessionId']) {
            return false;
        }
        if (!array_key_exists('__expires', $this->sessionData) ||
            $this->sessionData['__expires'] < time()) {
            return false;
        }
        if (!array_key_exists('__ttl', $this->sessionData) ||
            $this->sessionData['__ttl'] < time()) {
            return false;
        }
        return true;
    }

    /**
     * Renews the session expiration time
     *
     */
    private function renewSessionData(): void
    {
        $this->sessionData['__ttl'] = time() + $this->ttl;
    }

    /**
     * handles which Response is send and if Cookies are set or not
     *
     * @param $response
     * @return ResponseInterface
     */
    private function handleSessionResponse($response): ResponseInterface
    {
        if ($this->session !== null && $this->session->hasChanged()) {
            $this->sessionStorage->write(substr($this->sessionId, 0, 32), $this->sessionData);
            return Cookie::setCookie(
                $response,
                self::COOKIE_NAME,
                $this->responseCookies[self::COOKIE_NAME],
                $this->expires,
                self::COOKIE_PATH
            );
        }
        return $response;
    }
}
