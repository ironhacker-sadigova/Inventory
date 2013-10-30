<?php

namespace User\Domain\ACL\Authorization;

use User\Domain\ACL\Action;
use User\Domain\User;

interface AuthorizationRepositoryInterface
{
    /**
     * @param User   $user
     * @param Action $action
     * @param mixed  $resource
     * @return bool Does the authorization exist
     */
    public function exists(User $user, Action $action, $resource = null);
}
