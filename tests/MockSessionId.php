<?php

namespace Yiisoft\Session\Tests;

use SessionIdInterface;

final class MockSessionId implements SessionIdInterface
{
    public function __construct(private string $sessionId)
    {
    }

    public function create_sid(): string
    {
        return $this->sessionId;
    }
}
