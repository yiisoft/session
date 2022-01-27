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

    public function close(): bool
    {
        return $this->getReturnValue('close');
    }

    public function destroy($session_id): bool
    {
        return $this->getReturnValue('destroy');
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        return $this->getReturnValue('gc');
    }

    public function open($save_path, $name): bool
    {
        return $this->getReturnValue('open');
    }

    #[\ReturnTypeWillChange]
    public function read($session_id)
    {
        return '';
    }

    public function write($session_id, $session_data): bool
    {
        return $this->getReturnValue('write');
    }

    private function getReturnValue(string $method): bool
    {
        return !in_array($method, $this->failAt, true);
    }
}
