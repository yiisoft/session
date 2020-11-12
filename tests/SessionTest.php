<?php

declare(strict_types=1);

namespace Yiisoft\Session\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Session\Session;
use Yiisoft\Session\SessionException;

final class SessionTest extends TestCase
{
    private ?Session $session = null;

    public function getSession(array $options = [], \SessionHandlerInterface $handler = null): Session
    {
        if ($this->session === null) {
            $this->session = new Session($options, $handler);
        }

        return $this->session;
    }

    protected function tearDown(): void
    {
        if ($this->session !== null) {
            $this->session->destroy();
        }
    }

    public function testGetAndSet(): void
    {
        $session = $this->getSession();
        $session->set('key_get', 'value');
        self::assertEquals('value', $session->get('key_get'));
    }

    public function testHas(): void
    {
        $session = $this->getSession();
        $session->set('key_has', 'value');
        self::assertTrue($session->has('key_has'));
    }

    public function testClose(): void
    {
        $session = $this->getSession();
        $session->set('key_close', 'value');
        $session->close();
        self::assertEquals(PHP_SESSION_NONE, session_status());

        $session->open();
    }

    public function testRegenerateID(): void
    {
        $session = $this->getSession();
        $session->open();
        $id = $session->getId();
        $session->regenerateId();
        self::assertNotEquals($id, $session->getId());
    }

    public function testDiscard(): void
    {
        $session = $this->getSession();
        $session->set('key_discard', 'value');
        $session->discard();
        self::assertEmpty($session->get('key_discard'));
    }

    public function testGetName(): void
    {
        $session = $this->getSession();
        self::assertEquals($session->getName(), session_name());
    }

    public function testPull(): void
    {
        $session = $this->getSession();
        $session->set('key_pull', 'value');
        self::assertEquals('value', $session->pull('key_pull'));
        self::assertEmpty($session->get('key_pull'));
    }

    public function testAll(): void
    {
        $session = $this->getSession();
        $session->set('key_1', 1);
        $session->set('key_2', 2);
        self::assertEquals(['key_1' => 1, 'key_2' => 2], $session->all());
    }

    public function testClear(): void
    {
        $session = $this->getSession();
        $session->set('key', 'value');
        $session->clear();
        self::assertEmpty($session->all());
    }

    public function testSetId(): void
    {
        $session = $this->getSession();
        $session->setId('sessionId');
        $session->open();
        self::assertEquals(session_id(), $session->getId());
    }

    public function testGetCookieParameters(): void
    {
        $session = $this->getSession();
        self::assertEquals(session_get_cookie_params(), $session->getCookieParameters());
    }

    public function testCustomHandler(): void
    {
        $handler = new SpySessionHandler();
        $session = $this->getSession([], $handler);
        $session->open();
        $session->close();

        $this->assertSame(['open' => 1, 'read' => 1, 'write' => 1, 'close' => 1], $handler->getCalls());
    }

    public function testFailOpen(): void
    {
        $handler = new BadSessionHandler(['open']);
        $session = $this->getSession([], $handler);

        $this->expectException(SessionException::class);
        $session->open();
    }

    public function testFailWrite(): void
    {
        $handler = new BadSessionHandler(['write']);
        $session = $this->getSession([], $handler);

        $this->expectException(SessionException::class);
        $session->open();
        $session->close();
    }

    public function testFailRegenerateId(): void
    {
        $handler = new BadSessionHandler(['destroy']);
        $session = $this->getSession([], $handler);

        $this->expectException(SessionException::class);
        $session->open();
        $session->regenerateId();
    }
}
