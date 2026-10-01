<?php

namespace spec\Brace\Session;

use Brace\Session\Session;
use Brace\Session\SessionMiddleware;
use Brace\Session\Storages\SessionStorageInterface;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;

class SessionMiddlewareSpec extends ObjectBehavior
{
    function it_creates_a_session(SessionStorageInterface $storage)
    {
        $this->beConstructedWith($storage);
        $data = null;
        $this->_createSession('test', $data)->shouldHaveType(Session::class);
    }

    function it_rejects_an_invalid_session(ServerRequestInterface $request, SessionStorageInterface $storage)
    {
        $this->beConstructedWith($storage);
        $request->getCookieParams()->willReturn(['X-SESS' => 'test']);
        $storage->load('test')->willReturn([
            '__sid_hash' => sha1('other'),
            '__ttl' => time() + 3600,
            '__expires' => time() + 86400,
            'data' => [],
        ]);

        $data = null;
        $id = null;
        $this->_loadSession($request, $data, $id)->shouldReturn(null);
    }
}
