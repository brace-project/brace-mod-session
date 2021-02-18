<?php

namespace Test;

use Brace\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{

    public function testImplementsSessionInterface(): void
    {
        $sessionData = [];
        $session = new Session($sessionData, $sessionData, "test");
        self::assertInstanceOf(Session::class, $session);
    }

    public function testIsNotChangedAtInstantiation(): void
    {
        $sessionData = [];
        $session = new Session($sessionData, $sessionData, "test");
        self::assertFalse($session->hasChanged());
    }

    public function testSettingDataInSessionMakesItAccessible(): Session
    {
        $sessionData = [];
        $session = new Session($sessionData, $sessionData, "test");
        self::assertFalse($session->has('foo'));
        $session->set('foo', 'bar');
        self::assertTrue($session->has('foo'));
        self::assertSame('bar', $session->get('foo'));
        return $session;
    }

    /**
     * @depends testSettingDataInSessionMakesItAccessible
     * @param Session $session
     */
    public function testSettingDataInSessionChangesSession(Session $session): void
    {
        self::assertTrue($session->hasChanged());
    }

    /**
     * @depends testSettingDataInSessionMakesItAccessible
     * @param Session $session
     */
    public function testToArrayReturnsAllDataPreviouslySet(Session $session): void
    {
        self::assertSame(['foo' => 'bar'], $session->toArray());
    }

    /**
     * @depends testSettingDataInSessionMakesItAccessible
     * @param Session $session
     */
    public function testCanUnsetDataInSession(Session $session): void
    {
        $session->remove('foo');
        self::assertFalse($session->has('foo'));
    }

    public function testClearingSessionRemovesAllData(): void
    {
        $sessionData = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];
        $session = new Session($sessionData, $sessionData, "test");
        self::assertSame($sessionData, $session->toArray());

        $session->clear();
        self::assertNotSame($sessionData, $session->toArray());
        self::assertSame([], $session->toArray());
    }

    public function serializedDataProvider(): iterable
    {
        $data = (object)['test_case' => $this];
        $expected = json_decode(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION), true, 512, JSON_THROW_ON_ERROR);
        yield 'nested-objects' => [$data, $expected];
    }

    /**
     * @dataProvider serializedDataProvider
     * @param $data
     * @param $expected
     */
    public function testSetEnsuresDataIsJsonSerializable($data, $expected): void
    {
        $sessionData = [];
        $session = new Session($sessionData, $sessionData, "test");
        $session->set('foo', $data);
        self::assertNotSame($data, $session->get('foo'));
        self::assertSame($expected, $session->get('foo'));
    }
}

