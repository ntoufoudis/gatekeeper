<?php

namespace Ntoufoudis\Gatekeeper\Definitions;

abstract class Definition
{
    public string $guard = 'web';

    /** @return array<int|string, string> */
    abstract public function permissions(): array;
}
