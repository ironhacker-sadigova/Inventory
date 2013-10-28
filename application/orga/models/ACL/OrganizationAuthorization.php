<?php

namespace Orga\Model\ACL;

use Core_Model_Query;
use Orga_Model_Organization;
use User\Domain\ACL\Action;
use User\Domain\ACL\Authorization;
use User\Domain\User;

/**
 * Autorisation d'accès à une organisation.
 *
 * @author matthieu.napoli
 */
class OrganizationAuthorization extends Authorization
{
    /**
     * @var Orga_Model_Organization|null
     */
    protected $resource;

    /**
     * @param User                         $user
     * @param Action                       $action
     * @param Orga_Model_Organization|null $resource Can be null, for example with the "CREATE" action
     */
    public function __construct(User $user, Action $action, Orga_Model_Organization $resource = null)
    {
        $this->user = $user;
        $this->action = $action;
        $this->resource = $resource;
    }

    /**
     * @return Orga_Model_Organization|null
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param Orga_Model_Organization $resource
     * @return self[]
     */
    public static function loadByResource(Orga_Model_Organization $resource)
    {
        $query = new Core_Model_Query();
        $query->filter->addCondition('resource', $resource);
        return self::loadList($query);
    }
}
