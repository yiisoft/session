<?php

namespace Yiisoft\Session\Tests;

use SessionHandler;

final class MockSessionId extends SessionHandler
{
    public function __construct(private string $sessionId)
    {
    }

    public function create_sid(): string
    {
        return $this->sessionId;
    }
}
