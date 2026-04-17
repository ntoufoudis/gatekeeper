<?php

namespace Ntoufoudis\Gatekeeper\Tests\Fixtures\Roles;

use Ntoufoudis\Gatekeeper\Definitions\RoleDefinition;

class BadRole extends RoleDefinition
{
    public string $name = 'bad';

    public string $label = 'Bad Role';

    public string $guard = 'web';

    public function permissions(): array
    {
        return ['nonexistent.permission'];
    }
}
