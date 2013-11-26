<?php
/**
 * @author valentin.claras
 * @package Orga
 * @subpackage Controller
 */

use Core\Annotation\Secure;
use Core\Work\ServiceCall\ServiceCallTask;
use MyCLabs\Work\Dispatcher\WorkDispatcher;
use Orga\Model\ACL\Role\CellAdminRole;
use Orga\Model\ACL\Role\CellManagerRole;
use Orga\Model\ACL\Role\CellContributorRole;
use Orga\Model\ACL\Role\CellObserverRole;
use User\Domain\ACL\Role\Role;
use User\Domain\User;
use User\Domain\UserService;

/**
 * Controlleur du Datagrid listant les utilisateurs d'un ensemble de cellules.
 * @author valentin.claras
 * @package Orga
 * @subpackage Controller
 */
class Orga_Datagrid_Cell_Acls_ChildusersController extends UI_Controller_Datagrid
{
    /**
     * @Inject
     * @var UserService
     */
    private $userService;

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
     * @Secure("allowCell")
     */
    public function getelementsAction()
    {
        $this->request->setCustomParameters($this->request->filter->getConditions());
        $this->request->filter->setConditions([]);

        $idCell = $this->getParam('idCell');
        $cell = Orga_Model_Cell::load($idCell);
        $granularity = Orga_Model_Granularity::load($this->getParam('idGranularity'));

        $this->request->order->addOrder(Orga_Model_Cell::QUERY_TAG);
        $childCells = $cell->loadChildCellsForGranularity($granularity, $this->request);
        foreach ($childCells as $childCell) {
            $data = [];
            foreach ($childCell->getMembers() as $member) {
                $data[$member->getAxis()->getRef()] = $member->getCompleteRef();
            }

            foreach ($childCell->getAllRoles() as $role) {
                $data['index'] = $role->getId();
                $data['userFirstName'] = $role->getUser()->getFirstName();
                $data['userLastName'] = $role->getUser()->getLastName();
                $data['userEmail'] = $role->getUser()->getEmail();
                switch (true) {
                    case $role instanceof CellAdminRole:
                        $data['userRole'] = 'CellAdminRole';
                        break;
                    case $role instanceof CellManagerRole:
                        $data['userRole'] = 'CellManagerRole';
                        break;
                    case $role instanceof CellContributorRole:
                        $data['userRole'] = 'CellContributorRole';
                        break;
                    case $role instanceof CellObserverRole:
                        $data['userRole'] = 'CellObserverRole';
                        break;
                }
                $this->addLine($data);
            }
        }

        $this->send();
    }

    /**
     * @Secure("allowCell")
     */
    public function addelementAction()
    {
        $cell = Orga_Model_Cell::load($this->getParam('idCell'));
        $granularity = Orga_Model_Granularity::load($this->getParam('idGranularity'));
        $members = [];
        foreach ($cell->getMembers() as $member) {
            if ($granularity->hasAxis($member->getAxis())) {
                $members[] = $member;
            }
        }
        foreach ($granularity->getAxes() as $axis) {
            if (isset($this->_add[$this->id.'_'.$axis->getRef().'_addForm'])) {
                $members[] = $axis->getMemberByCompleteRef($this->getAddElementValue($axis->getRef()));
            }
        }
        $granularityCell = $granularity->getCellByMembers($members);

        // Validation
        $userEmail = $this->getAddElementValue('userEmail');
        if (empty($userEmail)) {
            $this->setAddElementErrorMessage('userEmail', __('UI', 'formValidation', 'emptyRequiredField'));
        } elseif (! filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            $this->setAddElementErrorMessage('userEmail', __('UI', 'formValidation', 'invalidEmail'));
            $this->send();
            return;
        }

        $role = $this->getAddElementValue('userRole');
        switch ($role) {
            case 'CellAdminRole':
                $role = CellAdminRole::class;
                break;
            case 'CellManagerRole':
                $role = CellManagerRole::class;
                break;
            case 'CellContributorRole':
                $role = CellContributorRole::class;
                break;
            case 'CellObserverRole':
                $role = CellObserverRole::class;
                break;
            default:
                $this->setAddElementErrorMessage('userRole', __('UI', 'formValidation', 'emptyRequiredField'));
        }

        // Vérifie que l'utilisateur n'a pas déjà le role
        try {
            $user = User::loadByEmail($userEmail);
            if ($user->hasRole($role)) {
                $this->setAddElementErrorMessage('userEmail', __('Orga', 'role', 'userAlreadyHasRole'));
            }
        } catch (Core_Exception_NotFound $e) {
        }

        if (!empty($this->_addErrorMessages)) {
            $this->send();
            return;
        }

        $success = function () {
            $this->message = __('UI', 'message', 'added');
        };
        $timeout = function () {
            $this->message = __('UI', 'message', 'addedLater');
        };
        $error = function (Exception $e) {
            throw $e;
        };

        $serviceCallTask = new ServiceCallTask(
            'Orga_Service_ACLManager',
            'addCellRole',
            [$cell, $role, $userEmail, false],
            __('Orga', 'backgroundTasks', 'addRoleToUser', ['ROLE' => $role::getLabel(), 'USER' => $userEmail])
        );

        $this->workDispatcher->runBackground($serviceCallTask, $this->waitDelay, $success, $timeout, $error);

        $this->send();
    }

    /**
     * Fonction supprimant un élément.
     *
     * Récupération de la ligne à supprimer de la manière suivante :
     *  $this->delete.
     *
     * Récupération des arguments de la manière suivante :
     *  $this->getParam('nomArgument').
     *
     * Renvoie un message d'information.
     * @Secure("allowCell")
     */
    public function deleteelementAction()
    {
        $role = Role::load($this->delete);
        $user = $role->getUser();
        $cell = Orga_Model_Cell::load($this->getParam('idCell'));

        $success = function () {
            $this->message = __('UI', 'message', 'deleted');
        };
        $timeout = function () {
            $this->message = __('UI', 'message', 'deletedLater');
        };
        $error = function (Exception $e) {
            throw $e;
        };

        $task = new ServiceCallTask(
            'Orga_Service_ACLManager',
            'removeCellRole',
            [$user, $role, false],
            __(
                'Orga',
                'backgroundTasks',
                'removeRoleFromUser',
                ['ROLE' => $role->getLabel(), 'USER' => $user->getEmail()]
            )
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);

        $this->send();
    }
}
