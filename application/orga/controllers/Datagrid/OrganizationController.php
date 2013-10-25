<?php
/**
 * @author valentin.claras
 * @package Orga
 */

use Core\Annotation\Secure;
use Core\Work\ServiceCall\ServiceCallTask;
use DI\Annotation\Inject;
use MyCLabs\Work\Dispatcher\WorkDispatcher;
use User\Domain\ACL\Action\DefaultAction;
use User\Domain\ACL\ACLService;

/**
 * Controller de projet
 * @package Orga
 */
class Orga_Datagrid_OrganizationController extends UI_Controller_Datagrid
{
    /**
     * @Inject
     * @var ACLService
     */
    private $aclService;

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
     * Methode appelee pour remplir le tableau.
     * @Secure("viewOrganizations")
     */
    public function getelementsAction()
    {
        $this->request->aclFilter->enabled = true;
        $this->request->aclFilter->user = $this->_helper->auth();
        $this->request->aclFilter->action = DefaultAction::VIEW();

        foreach (Orga_Model_Organization::loadList($this->request) as $organization) {
            /** @var Orga_Model_Organization $organization */
            $data = array();
            $data['index'] = $organization->getId();
            $data['label'] = $organization->getLabel();
            $rootAxesLabel = array();
            foreach ($organization->getRootAxes() as $rootAxis) {
                $rootAxesLabel[] = $rootAxis->getLabel();
            }
            $data['rootAxes'] = implode(', ', $rootAxesLabel);
            try {
                $data['granularityForInventoryStatus'] = $organization->getGranularityForInventoryStatus()->getLabel();
            } catch (Core_Exception_UndefinedAttribute $e) {
                $data['granularityForInventoryStatus'] = '';
            };

            $isConnectedUserAbleToSeeManyCells = false;
            foreach ($organization->getGranularities() as $granularity) {
                $aclCellQuery = new Core_Model_Query();
                $aclCellQuery->aclFilter->enabled = true;
                $aclCellQuery->aclFilter->user = $this->_helper->auth();
                $aclCellQuery->aclFilter->action = DefaultAction::VIEW();
                $aclCellQuery->filter->addCondition(
                    Orga_Model_Cell::QUERY_GRANULARITY,
                    $granularity,
                    Core_Model_Filter::OPERATOR_EQUAL
                );
                $numberCellUserCanSee = Orga_Model_Cell::countTotal($aclCellQuery);
                if ($numberCellUserCanSee > 1) {
                    $isConnectedUserAbleToSeeManyCells = true;
                    break;
                } elseif ($numberCellUserCanSee == 1) {
                    break;
                }
            }
            if ($isConnectedUserAbleToSeeManyCells) {
                $data['details'] = $this->cellLink('orga/organization/cells/idOrganization/'.$organization->getId());
            } elseif ($numberCellUserCanSee == 1) {
                $cellWithAccess = Orga_Model_Cell::loadList($aclCellQuery);
                $data['details'] = $this->cellLink('orga/cell/details/idCell/'.array_pop($cellWithAccess)->getId());
            }

            $isConnectedUserAbleToDeleteOrganization = $this->aclService->isAllowed(
                $this->_helper->auth(),
                DefaultAction::DELETE(),
                $organization
            );
            if (!$isConnectedUserAbleToDeleteOrganization) {
                $data['delete'] = false;
            }

            $this->addLine($data);
        }

        $this->send();
    }

    /**
     * Ajoute un nouvel element.
     * @Secure("createOrganization")
     */
    public function addelementAction()
    {
        $administrator = $this->_helper->auth();
        $label = $this->getAddElementValue('label');

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
            'createOrganization',
            [$administrator, $label],
            __('Orga', 'backgroundTasks', 'createOrganization', ['LABEL' => $label])
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);

        $this->send();
    }

    /**
     * Supprime un element.
     * @Secure("deleteOrganization")
     */
    public function deleteelementAction()
    {
        $organization = Orga_Model_Organization::load($this->delete);

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
            'deleteOrganization',
            [$organization]
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);

        $this->send();
    }
}
