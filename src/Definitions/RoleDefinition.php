<?php

namespace Ntoufoudis\Gatekeeper\Definitions;

abstract class RoleDefinition extends Definition
{
    public string $name = '';

    public string $label = '';

    /** @return string[] */
    abstract public function permissions(): array;
}
