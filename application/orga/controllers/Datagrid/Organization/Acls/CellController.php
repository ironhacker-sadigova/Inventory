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

/**
 * Controlleur du Datagrid listant les Roles d'une Cellule.
 * @author valentin.claras
 * @package Orga
 * @subpackage Controller
 */
class Orga_Datagrid_Organization_Acls_CellController extends UI_Controller_Datagrid
{
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
        $idCell = $this->getParam('idCell');
        $cell = Orga_Model_Cell::load($idCell);

        foreach ($cell->getAllRoles() as $role) {
            $data = array();
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

        $this->send();
    }

    /**
     * @Secure("allowCell")
     */
    public function addelementAction()
    {
        $cell = Orga_Model_Cell::load($this->getParam('idCell'));

        // Validation
        $userEmail = $this->getAddElementValue('userEmail');
        if (empty($userEmail)) {
            $this->setAddElementErrorMessage('userEmail', __('UI', 'formValidation', 'emptyRequiredField'));
            $this->send();
            return;
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
                $this->send();
                return;
        }

        // Vérifie que l'utilisateur n'a pas déjà le role
        try {
            $user = User::loadByEmail($userEmail);
            foreach ($user->getRoles() as $userRole) {
                if ($userRole instanceof $role && $userRole->getCell() === $cell) {
                    $this->setAddElementErrorMessage('userEmail', __('Orga', 'role', 'userAlreadyHasRole'));
                    $this->send();
                    return;
                }
            }
        } catch (Core_Exception_NotFound $e) {
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

        $task = new ServiceCallTask(
            Orga_Service_ACLManager::class,
            'addCellRole',
            [$cell, $role, $userEmail, false],
            __('Orga', 'backgroundTasks', 'addRoleToUser', ['ROLE' => $role::getLabel(), 'USER' => $userEmail])
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);

        $this->send();
    }

    /**
     * @Secure("allowCell")
     */
    public function deleteelementAction()
    {
        $role = Role::load($this->delete);
        $user = $role->getUser();

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
            Orga_Service_ACLManager::class,
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
