<?php

namespace Ntoufoudis\Gatekeeper\Tests\Fixtures\Permissions;

use Ntoufoudis\Gatekeeper\Definitions\PermissionGroup;

class PostPermissions extends PermissionGroup
{
    public string $guard = 'web';

    public string $group = 'Content';

    public function permissions(): array
    {
        return [
            'posts.view' => 'View published posts',
            'posts.create' => 'Create new posts',
        ];
    }
}
