<?php

use Core\Annotation\Secure;
use DI\Annotation\Inject;
use MyCLabs\Work\Dispatcher\WorkDispatcher;
use Core\Work\ServiceCall\ServiceCallTask;
use Orga\Model\ACL\Role\CellAdminRole;
use User\Domain\ACL\ACLService;
use User\Domain\ACL\Action;
use User\Domain\User;

/**
 * @author valentin.claras
 */
class Orga_Datagrid_MemberController extends UI_Controller_Datagrid
{
    /**
     * @Inject
     * @var ACLService
     */
    private $aclService;

    /**
     * @Inject
     * @var Orga_Service_ACLManager
     */
    private $aclManager;

    /**
     * @Inject
     * @var WorkDispatcher
     */
    private $workDispatcher;

    /**
     * @Inject("work.waitDelay")
     * @var int
     */
    private $waitDelay;

    /**
     * Fonction renvoyant la liste des éléments peuplant la Datagrid.
     *
     * Récupération des paramètres de tris et filtres de la manière suivante :
     *  $this->request.
     *
     * Récupération des arguments de la manière suivante :
     *  $this->getParam('nomArgument').
     *
     * Renvoie la liste d'éléments, le nombre total et un message optionnel.
     *
     * @Secure("editOrganizationAndCells")
     */
    public function getelementsAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);
        $axis = $organization->getAxisByRef($this->getParam('refAxis'));

        $isUserAllowedToEditOrganization = $this->aclService->isAllowed(
            $connectedUser,
            Action::EDIT(),
            $organization
        );
        $isUserAllowToEditAllMembers = $isUserAllowedToEditOrganization || $this->aclService->isAllowed(
            $connectedUser,
            Action::EDIT(),
            $organization->getGranularityByRef('global')->getCellByMembers([])
        );

        if (!$isUserAllowToEditAllMembers) {
            $members = [];
            /** @var Orga_Model_Cell[] $topCellsWithEditAccess */
            $topCellsWithEditAccess = $this->aclManager->getTopCellsWithAccessForOrganization(
                $connectedUser,
                $organization,
                [CellAdminRole::class]
            )['cells'];
            foreach ($topCellsWithEditAccess as $cell) {
                if (!$axis->isTransverse($cell->getGranularity()->getAxes())) {
                    foreach ($cell->getMembers() as $cellMember) {
                        if ($axis->isBroaderThan($cellMember->getAxis())) {
                            continue 2;
                        }
                    }
                    $members = array_merge($members, $cell->getChildMembersForAxes([$axis])[$axis->getRef()]);
                }
            }
            $members = array_unique($members);
            usort($members, [Orga_Model_Member::class, 'orderMembers']);
        } else {
            $this->request->filter->addCondition(Orga_Model_Member::QUERY_AXIS, $axis);
            $this->request->order->addOrder(Orga_Model_Member::QUERY_REF);
            /** @var Orga_Model_Member[] $members */
            $members = Orga_Model_Member::loadList($this->request);
        }

        foreach ($members as $member) {
            $data = [];
            $data['index'] = $member->getId();
            $data['label'] = $this->cellText($member->getLabel());
            $data['ref'] = $this->cellText($member->getRef());
            foreach ($member->getDirectParents() as $directParentMember) {
                $data['broader'.$directParentMember->getAxis()->getRef()] = $this->cellList(
                    $directParentMember->getCompleteRef(),
                    $directParentMember->getLabel()
                );
            }
            $this->addLine($data);
        }

        if ($isUserAllowToEditAllMembers) {
            $this->totalElements = Orga_Model_Member::countTotal($this->request);
        }

        $this->send();
    }


    /**
     * Ajoute un nouvel element.
     * @Secure("editOrganizationAndCells")
     */
    public function addelementAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);
        $axis = $organization->getAxisByRef($this->getParam('refAxis'));

        $label = $this->getAddElementValue('label');
        $ref = $this->getAddElementValue('ref');

        try {
            Core_Tools::checkRef($ref);
        } catch (Core_Exception_User $e) {
            $this->setAddElementErrorMessage('ref', $e->getMessage());
        }

        $broaderMembers = array();
        $contextualizingMembers = array();
        foreach ($axis->getDirectBroaders() as $directBroaderAxis) {
            $formFieldRef = 'broader'.$directBroaderAxis->getRef();
            $refBroaderMember = $this->getAddElementValue($formFieldRef);
            if (empty($refBroaderMember)) {
                $this->setAddElementErrorMessage($formFieldRef, __('Core', 'exception', 'emptyRequiredField'));
            } else {
                try {
                    $broaderMember = $directBroaderAxis->getMemberByCompleteRef($refBroaderMember);
                    $broaderMembers[] = $broaderMember;
                } catch (Core_Exception_NotFound $e) {
                    $this->setAddElementErrorMessage($formFieldRef, __('Core', 'exception', 'applicationError'));
                    continue;
                }
                if ($broaderMember->getAxis()->isContextualizing()) {
                    $contextualizingMembers[] = $broaderMember;
                }
                $contextualizingMembers = array_merge(
                    $contextualizingMembers,
                    $broaderMember->getContextualizingParents()
                );
            }
        }

        if (empty($this->_addErrorMessages)) {
            try {
                $axis->getMemberByCompleteRef($ref . '#' . Orga_Model_Member::buildParentMembersHashKey($contextualizingMembers));
                $this->setAddElementErrorMessage('ref', __('UI', 'formValidation', 'alreadyUsedIdentifier'));
            } catch (Core_Exception_NotFound $e) {
                $success = function () {
                    $this->message = __('UI', 'message', 'added');
                };
                $timeout = function () {
                    $this->message = __('UI', 'message', 'addedLater');
                };
                $error = function (Exception $e) {
                    throw $e;
                };

                // Lance la tache en arrière plan
                $task = new ServiceCallTask(
                    'Orga_Service_OrganizationService',
                    'addMember',
                    [$axis, $ref, $label, $broaderMembers],
                    __('Orga', 'backgroundTasks', 'addMember', ['MEMBER' => $label, 'AXIS' => $axis->getLabel()])
                );
                $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);
            }
        }

        $this->send();
    }

    /**
     * Supprime un element.
     * @Secure("editOrganizationAndCells")
     */
    public function deleteelementAction()
    {
        $member = Orga_Model_Member::load($this->delete);

        if ($member->hasDirectChildren()) {
            throw new Core_Exception_User('Orga', 'member', 'memberHasChild');
        }

        foreach ($member->getCells() as $memberCell) {
            if (count($memberCell->getAllRoles()) > 0) {
                throw new Core_Exception_User('Orga', 'member', 'deleteMemberWithUsersToCells');
            }
        }

        $success = function () {
            $this->message = __('UI', 'message', 'deleted');
        };
        $timeout = function () {
            $this->message = __('UI', 'message', 'deletedLater');
        };
        $error = function (Exception $e) {
            throw $e;
        };

        // Lance la tache en arrière plan
        $task = new ServiceCallTask(
            'Orga_Service_OrganizationService',
            'deleteMember',
            [$member],
            __('Orga', 'backgroundTasks', 'deleteMember', ['MEMBER' => $member->getLabel(), 'AXIS' => $member->getAxis()->getLabel()])
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);

        $this->send();
    }

    /**
     * Modifie les valeurs d'un element.
     * @Secure("editOrganizationAndCells")
     */
    public function updateelementAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);
        $axis = $organization->getAxisByRef($this->getParam('refAxis'));

        $member = Orga_Model_Member::load($this->update['index']);

        switch ($this->update['column']) {
            case 'label':
                $member->setLabel($this->update['value']);
                $this->message = __('UI', 'message', 'updated', array('LABEL' => $member->getLabel()));
                break;
            case 'ref':
                Core_Tools::checkRef($this->update['value']);
                try {
                    $completeRef = Orga_Model_Member::buildParentMembersHashKey($member->getContextualizingParents());
                    $completeRef = $this->update['value'] . '#' . $completeRef;
                    if ($axis->getMemberByCompleteRef($completeRef) !== $member) {
                        throw new Core_Exception_User('UI', 'formValidation', 'alreadyUsedIdentifier');
                    }
                } catch (Core_Exception_NotFound $e) {
                    $member->setRef($this->update['value']);
                    $this->message = __('UI', 'message', 'updated', array('LABEL' => $member->getLabel()));
                }
                break;
            default:
                $refBroaderAxis = substr($this->update['column'], 7);
                try {
                    $broaderAxis = $organization->getAxisByRef($refBroaderAxis);
                } catch (Core_Exception_NotFound $e) {
                    parent::updateelementAction();
                }
                if (!empty($this->update['value'])) {
                    $parentMember = $broaderAxis->getMemberByCompleteRef($this->update['value']);
                    $member->setDirectParentForAxis($parentMember);
                    $this->message = __('UI', 'message', 'updated', array('LABEL' => $member->getLabel()));
                } else {
                    throw new Core_Exception_User('UI', 'formValidation', 'emptyRequiredField');
                }
                break;
        }
        $this->data = $this->update['value'];

        $this->send();
    }

    /**
     * Renvoie la liste des parents éligibles pour un membre.
     * @Secure("editOrganizationAndCells")
     */
    public function getParentsAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);
        $broaderAxis = $organization->getAxisByRef($this->getParam('refParentAxis'));

        $isUserAllowedToEditOrganization = $this->aclService->isAllowed(
            $connectedUser,
            Action::EDIT(),
            $organization
        );
        $isUserAllowedToEditGlobalCell = $isUserAllowedToEditOrganization
            || $this->aclService->isAllowed(
                $connectedUser,
                Action::EDIT(),
                $organization->getGranularityByRef('global')->getCellByMembers([])
            );
        $isUserAllowToEditAllMembers = $isUserAllowedToEditOrganization || $isUserAllowedToEditGlobalCell;

        if (!$isUserAllowToEditAllMembers) {
            /** @var Orga_Model_Member[] $members */
            $members = [];
            /** @var Orga_Model_Cell[] $topCellsWithEditAccess */
            $topCellsWithEditAccess = $this->aclManager->getTopCellsWithAccessForOrganization(
                $connectedUser,
                $organization,
                [CellAdminRole::class]
            )['cells'];
            $isTransverseToAll = true;
            foreach ($topCellsWithEditAccess as $cell) {
                if (!$broaderAxis->isTransverse($cell->getGranularity()->getAxes())) {
                    $isTransverseToAll = false;
                    foreach ($cell->getMembers() as $cellMember) {
                        if ($broaderAxis->isBroaderThan($cellMember->getAxis())) {
                            continue 2;
                        }
                    }
                    $members = array_merge(
                        $members,
                        $cell->getChildMembersForAxes([$broaderAxis])[$broaderAxis->getRef()]
                    );
                }
            }
            if (!$isTransverseToAll) {
                $members = array_unique($members);
                usort($members, [Orga_Model_Member::class, 'orderMembers']);
            } else {
                $members = $broaderAxis->getOrderedMembers()->toArray();
            }
        } else {
            $members = $broaderAxis->getOrderedMembers()->toArray();
        }

        $query = $this->getParam('q');
        if (!empty($query)) {
            foreach ($members as $indexMember => $member) {
                if (strpos($member->getLabel(), $query) === false) {
                    unset($members[$indexMember]);
                }
            }
        }

        foreach ($members as $eligibleParentMember) {
            $this->addElementAutocompleteList(
                $eligibleParentMember->getCompleteRef(),
                $eligibleParentMember->getLabel()
            );
        }

        $this->send();
    }

}