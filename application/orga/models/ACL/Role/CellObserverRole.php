<?php

namespace Orga\Model\ACL\Role;

use Orga\Model\ACL\CellAuthorization;
use Orga\Model\ACL\OrganizationAuthorization;
use Orga\Model\ACL\Action\CellAction;
use Orga_Model_Cell;
use User\Domain\ACL\Action;
use User\Domain\ACL\Role\Role;
use User\Domain\User;

/**
 * Cell observer.
 */
class CellObserverRole extends AbstractCellRole
{
    public function __construct(User $user, Orga_Model_Cell $cell)
    {
        $cell->addObserverRole($this);

        parent::__construct($user, $cell);
    }

    public function buildAuthorizations()
    {
        $this->authorizations->clear();

        // Voir l'organisation
        OrganizationAuthorization::create($this, Action::VIEW(), $this->cell->getOrganization());

        $authorizations = CellAuthorization::createMany($this, $this->cell, [
            Action::VIEW(),
            CellAction::COMMENT(),
            CellAction::VIEW_REPORTS(),
        ]);

        // Cellules filles
        foreach ($this->cell->getChildCells() as $childCell) {
            foreach ($authorizations as $authorization) {
                CellAuthorization::createChildAuthorization($authorization, $childCell);
            }
        }
    }

    public static function getLabel()
    {
        return __('Orga', 'role', 'cellObserver');
    }
}
