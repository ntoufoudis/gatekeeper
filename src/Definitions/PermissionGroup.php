<?php

namespace Ntoufoudis\Gatekeeper\Definitions;

abstract class PermissionGroup extends Definition
{
    public string $group = '';

    /** @return array<string, string> name => label */
    abstract public function permissions(): array;
}
