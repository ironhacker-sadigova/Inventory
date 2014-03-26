<?php

namespace Account\Domain;

use Core\Domain\EntityRepository;
use User\Domain\User;

/**
 * Account repository.
 *
 * @author matthieu.napoli
 */
interface AccountRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     * @return Account
     */
    public function get($id);

    /**
     * Returns all accounts that the user can traverse.
     *
     * @param User $user
     *
     * @return Account[]
     */
    public function getTraversableAccounts(User $user);
}
