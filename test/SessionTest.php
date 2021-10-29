<?php

namespace Test;

use Brace\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{

    public function testImplementsSessionInterface(): void
    {
        $sessionData = [];
        $session = new Session($sessionData, "test");
        self::assertInstanceOf(Session::class, $session);
    }

    public function testIsNotChangedAtInstantiation(): void
    {
        $sessionData = [];
        $session = new Session($sessionData, "test");
        self::assertFalse($session->hasChanged());
    }

    public function testSettingDataInSessionMakesItAccessible(): Session
    {
        $sessionData = [];
        $session = new Session($sessionData, "test");
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

    public function testClearingSessionRemovesAllData(): Session
    {
        $sessionData = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];
        $testData = $sessionData;
        $session = new Session($sessionData, "test");
        self::assertSame($testData, $session->toArray());

        $session->clear();
        self::assertNotSame($testData, $session->toArray());
        self::assertSame([], $session->toArray());
        return $session;
    }

    /**
     * @depends testClearingSessionRemovesAllData
     * @param Session $session
     */
    public function testSessionIsEmpty(Session $session): void
    {
        self::assertTrue($session->isEmpty());
    }
}

