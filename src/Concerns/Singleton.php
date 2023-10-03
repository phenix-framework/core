<?php

declare(strict_types=1);

namespace Phenix\Concerns;

use Phenix\Exceptions\RuntimeError;

trait Singleton
{
    private function __construct()
    {
        // Disabled instantiation.
    }

    final public function __clone(): void
    {
        throw new RuntimeError('Cloning was disabled.');
    }

    final public function __wakeup(): void
    {
        throw new RuntimeError('WakeUp was disabled.');
    }
}
