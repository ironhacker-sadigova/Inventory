<?php

namespace Orga\ViewModel;

use Core_Exception_UndefinedAttribute;
use Doctrine\Common\Collections\Criteria;
use MyCLabs\ACL\ACLManager;
use MyCLabs\ACL\Model\Actions;
use Orga_Model_Cell;
use User\Domain\User;
use Orga\Model\ACL\Action\CellAction;
use AF\Domain\InputSet\PrimaryInputSet;

/**
 * Factory de CellViewModel.
 */
class CellViewModelFactory
{
    /**
     * @var ACLManager
     */
    private $aclManager;

    /**
     * @var array
     */
    public $inventoryStatusList;

    /**
     * @var array
     */
    public $inputStatusList;


    /**
     * @param ACLManager $aclManager
     */
    public function __construct(ACLManager $aclManager)
    {
        $this->aclManager = $aclManager;

        $this->inventoryStatusList = [
            Orga_Model_Cell::STATUS_NOTLAUNCHED => __('Orga', 'view', 'inventoryNotLaunched'),
            Orga_Model_Cell::STATUS_ACTIVE => __('Orga', 'view', 'inventoryOpen'),
            Orga_Model_Cell::STATUS_CLOSED => __('Orga', 'view', 'inventoryClosed')
        ];
        $this->inputStatusList = [
            PrimaryInputSet::STATUS_FINISHED => __('AF', 'inputInput', 'statusFinished'),
            PrimaryInputSet::STATUS_COMPLETE => __('AF', 'inputInput', 'statusComplete'),
            PrimaryInputSet::STATUS_CALCULATION_INCOMPLETE => __('AF', 'inputInput', 'statusCalculationIncomplete'),
            PrimaryInputSet::STATUS_INPUT_INCOMPLETE => __('AF', 'inputInput', 'statusInputIncomplete'),
            CellViewModel::AF_STATUS_INVENTORY_NOT_STARTED => __('Orga', 'view', 'inventoryNotLaunched'),
            CellViewModel::AF_STATUS_AF_NOT_CONFIGURED => __('Orga', 'view', 'statusAFNotConfigured'),
            CellViewModel::AF_STATUS_NOT_STARTED => __('Orga', 'view', 'statusNotStarted'),
        ];
    }

    /**
     * @param Orga_Model_Cell $cell
     * @param User $user
     * @param bool $withAdministrators
     * @param bool $withACL
     * @param bool $withReports
     * @param bool $withExports
     * @param bool $withInventory
     * @param bool $editInventory
     * @param bool $withInput
     * @param bool $withInputLink
     * @return CellViewModel
     */
    public function createCellViewModel(
        Orga_Model_Cell $cell,
        User $user,
        $withAdministrators = null,
        $withACL = null,
        $withReports = null,
        $withExports = null,
        $withInventory = null,
        $editInventory = null,
        $withInput = null,
        $withInputLink = null
    ) {
        $cellViewModel = new CellViewModel();
        $cellViewModel->id = $cell->getId();
        $cellViewModel->shortLabel = $cell->getLabel();
        $cellViewModel->extendedLabel = $cell->getExtendedLabel();
        $cellViewModel->relevant = $cell->isRelevant();
        $cellViewModel->tag = $cell->getTag();

        // Administrateurs.
        if ($withAdministrators === true) {
            foreach ($cell->getAdminRoles() as $administrator) {
                array_unshift($cellViewModel->administrators, $administrator->getSecurityIdentity()->getEmail());
            }
            foreach (array_reverse($cell->getParentCells()) as $parentCell) {
                foreach ($parentCell->getAdminRoles() as $parentAdministrator) {
                    array_unshift($cellViewModel->administrators, $parentAdministrator->getSecurityIdentity()->getEmail());
                }
            }
            foreach ($cell->getOrganization()->getAdminRoles() as $organizationAdministrator) {
                array_unshift($cellViewModel->administrators, $organizationAdministrator->getSecurityIdentity()->getEmail());
            }
        }

        // Utilisateurs.
        if (($withACL === true)
            || (($withACL !== false)
                && ($cell->getGranularity()->getCellsWithACL())
                && ($this->aclManager->isAllowed($user, Actions::ALLOW, $cell)))
        ) {
            $cellViewModel->showUsers = true;
            $cellViewModel->numberUsers = $cell->getAdminRoles()->count() + $cell->getManagerRoles()->count()
                + $cell->getContributorRoles()->count() + $cell->getObserverRoles()->count();
        }

        // Reports.
        if (($withReports === true)
            || (($withReports !== false)
                && ($cell->getGranularity()->getCellsGenerateDWCubes())
                && ($this->aclManager->isAllowed($user, CellAction::VIEW_REPORTS(), $cell)))
        ) {
            $cellViewModel->showReports = true;
        }

        // Exports.
        if (($withExports === true)
            || (($withExports !== false)
                && ($this->aclManager->isAllowed($user, CellAction::VIEW_REPORTS(), $cell)))
        ) {
            $cellViewModel->showExports = true;
        }

        // Inventory
        $cellViewModel->inventoryStatus = $cell->getInventoryStatus();
        if (($withInventory === true)
            || (($withInventory !== false)
                && (($this->aclManager->isAllowed($user, CellAction::VIEW_REPORTS(), $cell))))
        ) {
            try {
                $granularityForInventoryStatus = $cell->getGranularity()->getOrganization()->getGranularityForInventoryStatus();

                if (($editInventory)
                    || (($cell->getGranularity() === $granularityForInventoryStatus)
                        && ($this->aclManager->isAllowed($user, CellAction::INPUT(), $cell)))) {
                    $cellViewModel->canEditInventory = true;
                }

                if (($cell->getGranularity() === $granularityForInventoryStatus)
                    || ($cell->getGranularity()->isNarrowerThan($granularityForInventoryStatus))
                ) {
                    $cellViewModel->showInventory = true;

                    $cellViewModel->inventoryStatusTitle = $this->inventoryStatusList[$cellViewModel->inventoryStatus];

                    $cellViewModel->inventoryCompletion = 0;
                    $cellViewModel->inventoryNotStartedInputsNumber = 0;
                    $cellViewModel->inventoryStartedInputsNumber = 0;
                    $cellViewModel->inventoryCompletedInputsNumber = 0;
                    if (($cell->getGranularity()->isNarrowerThan($granularityForInventoryStatus)
                        || ($cell->getGranularity() === $granularityForInventoryStatus))
                        && ($cell->getGranularity()->getInputConfigGranularity() !== null)) {
                        if ($cell->getAFInputSetPrimary() !== null) {
                            $cellViewModel->inventoryCompletion += $cell->getAFInputSetPrimary()->getCompletion();
                            if ($cell->getAFInputSetPrimary()->getCompletion() == 0) {
                                $cellViewModel->inventoryNotStartedInputsNumber ++;
                            } else if ($cell->getAFInputSetPrimary()->getCompletion() < 100) {
                                $cellViewModel->inventoryStartedInputsNumber ++;
                            } else {
                                $cellViewModel->inventoryCompletedInputsNumber ++;
                            }
                        } else {
                            $cellViewModel->inventoryNotStartedInputsNumber ++;
                        }
                    }
                    foreach ($cell->getGranularity()->getNarrowerGranularities() as $narrowerInputGranularity) {
                        if (($narrowerInputGranularity->getInputConfigGranularity() !== null)
                            && ($narrowerInputGranularity->isNarrowerThan($granularityForInventoryStatus))) {
                            $relevantCriteria = new Criteria();
                            $relevantCriteria->where($relevantCriteria->expr()->eq(Orga_Model_Cell::QUERY_ALLPARENTSRELEVANT, true));
                            $relevantCriteria->andWhere($relevantCriteria->expr()->eq(Orga_Model_Cell::QUERY_RELEVANT, true));
                            $relevantChildInputCells = $cell->getChildCellsForGranularity($narrowerInputGranularity)->matching($relevantCriteria);
                            /** @var Orga_Model_Cell $childInputCell */
                            foreach ($relevantChildInputCells as $childInputCell) {
                                $childAFInputSetPrimary = $childInputCell->getAFInputSetPrimary();
                                if ($childAFInputSetPrimary !== null) {
                                    $cellViewModel->inventoryCompletion += $childInputCell->getAFInputSetPrimary()->getCompletion();
                                    if ($childInputCell->getAFInputSetPrimary()->getCompletion() == 0) {
                                        $cellViewModel->inventoryNotStartedInputsNumber ++;
                                    } else if ($childInputCell->getAFInputSetPrimary()->getCompletion() < 100) {
                                        $cellViewModel->inventoryStartedInputsNumber ++;
                                    } else {
                                        $cellViewModel->inventoryCompletedInputsNumber ++;
                                    }
                                } else {
                                    $cellViewModel->inventoryNotStartedInputsNumber ++;
                                }
                            }
                        }
                    }
                    $totalInventoryInputs = $cellViewModel->inventoryNotStartedInputsNumber + $cellViewModel->inventoryStartedInputsNumber + $cellViewModel->inventoryCompletedInputsNumber;
                    if ($totalInventoryInputs > 0) {
                        $cellViewModel->inventoryCompletion /= $totalInventoryInputs;
                    }
                    $cellViewModel->inventoryCompletion = round($cellViewModel->inventoryCompletion);
                }
            } catch (Core_Exception_UndefinedAttribute $e) {
            } catch (\Core_Exception_NotFound $e) {
            }
        }

        // Saisie.
        if (($withInput === true)
            || (($withInput !== false)
                && ($cell->getGranularity()->getInputConfigGranularity() !== null)
                && (($this->aclManager->isAllowed($user, CellAction::INPUT(), $cell))))
        ) {
            $cellViewModel->showInput = true;
            $cellViewModel->showInputLink = (($withInputLink !== true) && ($withInputLink !== false)) ? true : $withInputLink;
            $inputStatus = ($cell->getInputAFUsed() !== null) ? CellViewModel::AF_STATUS_NOT_STARTED : CellViewModel::AF_STATUS_AF_NOT_CONFIGURED;
            try {
                $granularityForInventoryStatus = $cell->getGranularity()->getOrganization()->getGranularityForInventoryStatus();
                if (($cell->getInventoryStatus() === Orga_Model_Cell::STATUS_NOTLAUNCHED)
                    && (($cell->getGranularity() === $granularityForInventoryStatus)
                        || ($cell->getGranularity()->isNarrowerThan($granularityForInventoryStatus)))) {
                    if ($withInputLink !== false) {
                        $cellViewModel->showInputLink = false;
                    }
                    $inputStatus = CellViewModel::AF_STATUS_INVENTORY_NOT_STARTED;
                }
            } catch (Core_Exception_UndefinedAttribute $e) {
            } catch (\Core_Exception_NotFound $e) {
            }

            $aFInputSetPrimary = $cell->getAFInputSetPrimary();
            if ($aFInputSetPrimary !== null) {
                $cellViewModel->inputStatus = $aFInputSetPrimary->getStatus();
                $cellViewModel->inputCompletion = $aFInputSetPrimary->getCompletion();
            } else {
                $cellViewModel->inputStatus = $inputStatus;
                $cellViewModel->inputCompletion = 0;
            }
            $cellViewModel->inputStatusTitle = $this->inputStatusList[$cellViewModel->inputStatus];
        }

        return $cellViewModel;
    }
}
