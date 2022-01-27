<?php

declare(strict_types=1);

namespace Yiisoft\Session\Tests;

use function array_key_exists;

final class SpySessionHandler implements \SessionHandlerInterface
{
    private array $calls = [];

    public function getCalls(): array
    {
        return $this->calls;
    }

    public function close(): bool
    {
        $this->record('close');
        return true;
    }

    public function destroy($session_id): bool
    {
        $this->record('destroy');
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        $this->record('gc');
        return true;
    }

    public function open($save_path, $name): bool
    {
        $this->record('open');
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($session_id)
    {
        $this->record('read');
        return '';
    }

    public function write($session_id, $session_data): bool
    {
        $this->record('write');
        return true;
    }

    private function record(string $method): void
    {
        if (!array_key_exists($method, $this->calls)) {
            $this->calls[$method] = 1;
        } else {
            $this->calls[$method]++;
        }
    }
}
