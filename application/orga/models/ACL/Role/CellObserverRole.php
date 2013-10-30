<?php

namespace Orga\Model\ACL\Role;

use Orga\Model\ACL\CellAuthorization;
use Orga\Model\ACL\OrganizationAuthorization;
use Orga_Action_Cell;
use User\Domain\ACL\Action;
use User\Domain\ACL\Role;

/**
 * Cell observer.
 */
class CellObserverRole extends AbstractCellRole
{
    public function buildAuthorizations()
    {
        $this->authorizations->clear();

        // Voir l'organisation
        OrganizationAuthorization::create($this, $this->user, Action::VIEW(), $this->cell->getOrganization());

        CellAuthorization::create($this, $this->user, Action::VIEW(), $this->cell);
        CellAuthorization::create($this, $this->user, Orga_Action_Cell::COMMENT(), $this->cell);

        // Cellules filles
        foreach ($this->cell->getChildCells() as $childCell) {
            CellAuthorization::create($this, $this->user, Action::VIEW(), $childCell);
            CellAuthorization::create($this, $this->user, Orga_Action_Cell::COMMENT(), $childCell);
        }
    }
}
