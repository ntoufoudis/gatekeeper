<?php

namespace Ntoufoudis\Gatekeeper\Tests\Fixtures\Permissions\Sub;

use Ntoufoudis\Gatekeeper\Definitions\PermissionGroup;

class CommentPermissions extends PermissionGroup
{
    public string $guard = 'api';

    public string $group = 'Content';

    public function permissions(): array
    {
        return [
            'comments.view' => 'View comments',
            'comments.delete' => 'Delete comments',
        ];
    }
}
