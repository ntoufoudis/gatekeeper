<?php

namespace Ntoufoudis\Gatekeeper\Concerns;

trait HasGatekeeperAccess
{
    use HasPermissions;
    use HasRoles;
}
