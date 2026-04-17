<?php

namespace Ntoufoudis\Gatekeeper\Tests\Fixtures\Roles;

use Ntoufoudis\Gatekeeper\Definitions\RoleDefinition;

class EditorRole extends RoleDefinition
{
    public string $name = 'editor';

    public string $label = 'Content Editor';

    public string $guard = 'web';

    public function permissions(): array
    {
        return ['posts.view', 'posts.create'];
    }
}
