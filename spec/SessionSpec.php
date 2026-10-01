<?php

namespace spec\Brace\Session;

use Brace\Session\Session;
use PhpSpec\ObjectBehavior;

class SessionSpec extends ObjectBehavior
{
    function let()
    {
        $data = [];
        $this->beConstructedWith($data, 'test');
    }

    function it_is_a_session()
    {
        $this->shouldHaveType(Session::class);
    }

    function it_starts_unchanged()
    {
        $this->hasChanged()->shouldReturn(false);
        $this->isEmpty()->shouldReturn(true);
    }

    function it_stores_and_removes_data()
    {
        $this->has('foo')->shouldReturn(false);
        $this->set('foo', 'bar');
        $this->has('foo')->shouldReturn(true);
        $this->get('foo')->shouldReturn('bar');
        $this->toArray()->shouldReturn(['foo' => 'bar']);
        $this->hasChanged()->shouldReturn(true);

        $this->remove('foo');
        $this->has('foo')->shouldReturn(false);
    }

    function it_clears_data()
    {
        $this->set('foo', 'bar');
        $this->clear();
        $this->toArray()->shouldReturn([]);
        $this->isEmpty()->shouldReturn(true);
    }
}
