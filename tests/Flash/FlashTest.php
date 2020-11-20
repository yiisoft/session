<?php

declare(strict_types=1);

namespace Yiisoft\Session\Tests\Flash;

use PHPUnit\Framework\TestCase;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;

final class FlashTest extends TestCase
{
    /**
     * @var MockArraySessionStorage|SessionInterface
     */
    private SessionInterface $session;

    private function getSession(array $contents = []): SessionInterface
    {
        return new MockArraySessionStorage($contents);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new MockArraySessionStorage([
            '__flash' => [
                '__counters' => [
                    'info' => 0,
                    'error' => 0,
                ],
                'info' => 'Some message to show',
                'error' => 'Some error message to show',
            ],
        ]);
    }

    public function testCleanupFromPreviousRequest(): void
    {
        $session = $this->getSession([
            '__flash' => [
                '__counters' => [
                    'info' => 1,
                ],
                'info' => 'Some message to show',
            ],
        ]);
        $flash = new Flash($session);

        $flash->getAll();

        $rawFlashes = $session->get('__flash');
        $this->assertArrayNotHasKey('info', $rawFlashes);
        $this->assertArrayNotHasKey('info', $rawFlashes['__counters']);
    }

    public function testSpoiledFlashesSessionValue(): void
    {
        $session = $this->getSession([
            '__flash' => 42,
        ]);
        $flash = new Flash($session);

        $flash->getAll();

        $rawFlashes = $session->get('__flash');
        $this->assertSame(['__counters' => []], $rawFlashes);
    }

    public function testSpoiledCountersSessionValue(): void
    {
        $session = $this->getSession([
            '__flash' => [
                '__counters' => 42,
            ],
        ]);
        $flash = new Flash($session);

        $flash->getAll();

        $rawFlashes = $session->get('__flash');
        $this->assertSame(['__counters' => []], $rawFlashes);
    }

    public function testRemove(): void
    {
        $flash = new Flash($this->session);

        $flash->remove('error');
        $rawFlashes = $this->session->get('__flash');
        $this->assertSame(['__counters' => ['info' => 1], 'info' => 'Some message to show'], $rawFlashes);
    }

    public function testRemoveAll(): void
    {
        $session = $this->getSession();
        $flash = new Flash($session);

        $flash->removeAll();

        $rawFlashes = $session->get('__flash');
        $this->assertSame(['__counters' => []], $rawFlashes);
    }

    public function testHas(): void
    {
        $flash = new Flash($this->session);

        $this->assertTrue($flash->has('error'));
        $this->assertFalse($flash->has('nope'));

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 1,
                'error' => 1,
            ],
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
        ], $rawFlashes);
    }

    public function testGet(): void
    {
        $flash = new Flash($this->session);

        $value = $flash->get('error');
        $this->assertSame('Some error message to show', $value);

        $value = $flash->get('nope');
        $this->assertNull($value);

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 1,
                'error' => 1,
            ],
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
        ], $rawFlashes);
    }

    public function testAdd(): void
    {
        $flash = new Flash($this->session);

        $flash->add('info', 'test');
        $flash->add('new', '1');
        $flash->add('new', '2');

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => -1,
                'error' => 1,
                'new' => -1,
            ],
            'info' => ['Some message to show', 'test'],
            'error' => 'Some error message to show',
            'new' => ['1', '2'],
        ], $rawFlashes);
    }

    public function testSet(): void
    {
        $flash = new Flash($this->session);

        $flash->set('warn', 'Warning message');

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 1,
                'error' => 1,
                'warn' => -1,
            ],
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
            'warn' => 'Warning message',
        ], $rawFlashes);
    }

    public function testRemoveAfterAccessWithGet(): void
    {
        $flash = new Flash($this->session);

        $flash->set('warn', 'Warning message');

        // will mark "warn" as to be deleted in the next request
        $flash->get('warn');

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 1,
                'error' => 1,
                'warn' => 1,
            ],
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
            'warn' => 'Warning message',
        ], $rawFlashes);
    }

    public function testRemoveAfterAccessWithAll(): void
    {
        $flash = new Flash($this->session);

        $flash->set('warn', 'Warning message');

        // will mark all flashes as to be deleted in the next request
        $flash->getAll();

        $rawFlashes = $this->session->get('__flash');
        $this->assertSame([
            '__counters' => [
                'info' => 1,
                'error' => 1,
                'warn' => 1,
            ],
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
            'warn' => 'Warning message',
        ], $rawFlashes);
    }

    public function testGetAll(): void
    {
        $flash = new Flash($this->session);

        $flashes = $flash->getAll();
        $this->assertSame([
            'info' => 'Some message to show',
            'error' => 'Some error message to show',
        ], $flashes);
    }
}
