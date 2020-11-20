<?php

declare(strict_types=1);

namespace Yiisoft\Session\Tests;

final class BadSessionHandler implements \SessionHandlerInterface
{
    private array $failAt;

    public function __construct(array $failAt = [])
    {
        $this->failAt = $failAt;
    }

    public function close()
    {
        return $this->getReturnValue('close');
    }

    public function destroy($session_id)
    {
        return $this->getReturnValue('destroy');
    }

    public function gc($maxlifetime)
    {
        return $this->getReturnValue('gc');
    }

    public function open($save_path, $name)
    {
        return $this->getReturnValue('open');
    }

    public function read($session_id)
    {
        return '';
    }

    public function write($session_id, $session_data)
    {
        return $this->getReturnValue('write');
    }

    private function getReturnValue(string $method): bool
    {
        return !in_array($method, $this->failAt, true);
    }
}
