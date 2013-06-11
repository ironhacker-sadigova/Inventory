<?php
/**
 * @author valentin.claras
 * @package Orga
 * @subpackage Controller
 */

use Core\Annotation\Secure;

/**
 * Controlleur du Datagrid listant les Roles du projet d'une cellule.
 * @author valentin.claras
 * @package Orga
 * @subpackage Controller
 */
class Orga_Datagrid_Cell_Acls_ProjectController extends UI_Controller_Datagrid
{
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
     * @Secure("editProject")
     */
    function getelementsAction()
    {
        $idProject = $this->getParam('idProject');
        $projectAdministratorRole = User_Model_Role::loadByRef('projectAdministrator_'.$idProject);

        foreach ($projectAdministratorRole->getUsers() as $user) {
            $data = array();
            $data['index'] = $user->getKey()['id'];
            $data['userFirstName'] = $user->getFirstName();
            $data['userLastName'] = $user->getLastName();
            $data['userEmail'] = $user->getEmail();
            $this->addLine($data);
        }

        $this->send();
    }

    /**
     * Fonction ajoutant un élément.
     *
     * Renvoie un message d'information.
     *
     * @see getAddElementValue
     * @see setAddElementErrorMessage
     * @Secure("editProject")
     */
    function addelementAction()
    {
        /** @var User_Service_User $userService */
        $userService = $this->get('User_Service_User');
        /** @var Orga_Service_ACLManager $aclManager */
        $aclManager = $this->get('Orga_Service_ACLManager');

        $idProject = $this->getParam('idProject');
        $projectAdministratorRole = User_Model_Role::loadByRef('projectAdministrator_'.$idProject);
        $project = Orga_Model_Project::load(array('id' => $idProject));

        $userEmail = $this->getAddElementValue('userEmail');
        if (empty($userEmail)) {
            $this->setAddElementErrorMessage('userEmail', __('UI', 'formValidation', 'emptyRequiredField'));
        }

        if (empty($this->_addErrorMessages)) {
            $entityManagers = Zend_Registry::get('EntityManagers');
            $entityManagers['default']->getConnection()->beginTransaction();

            if (User_Model_User::isEmailUsed($userEmail)) {
                $user = User_Model_User::loadByEmail($userEmail);
                if ($user->hasRole($projectAdministratorRole)) {
                    $this->setAddElementErrorMessage('userEmail', __('Orga', 'role', 'userAlreadyHasRole'));
                } else {
                    set_time_limit(0);
                    try {
                        $entityManagers['default']->flush();

                        $aclManager->addProjectAdministrator(
                            $project,
                            $user
                        );
                        $entityManagers['default']->flush();

                        $entityManagers['default']->getConnection()->commit();
                    } catch (Exception $e) {
                        $entityManagers['default']->getConnection()->rollback();
                        $entityManagers['default']->clear();

                        throw $e;
                    }
                    $userService->sendEmail(
                        $user,
                        __('User', 'email', 'subjectAccessRightsChange'),
                        __('Orga', 'email', 'userProjectAdministratorRoleAdded', array(
                            'PROJECT' => $project->getLabel(),
                        ))
                    );
                    $this->message = __('Orga', 'role', 'roleAddedToExistingUser');
                }
            } else {
                $user = $userService->inviteUser(
                    $userEmail,
                    __('Orga', 'email', 'userProjectAdministratorRoleGivenAtCreation', array(
                        'PROJECT' => $project->getLabel(),
                        'ROLE' => $projectAdministratorRole->getName()
                    ))
                );
                $this->message = __('Orga', 'role', 'userCreatedFromRessource');
                $user->addRole(User_Model_Role::loadByRef('user'));

                set_time_limit(0);
                try {
                    $entityManagers['default']->flush();

                    $aclManager->addProjectAdministrator(
                        $project,
                        $user
                    );
                    $entityManagers['default']->flush();

                    $entityManagers['default']->getConnection()->commit();
                } catch (Exception $e) {
                    $entityManagers['default']->getConnection()->rollback();
                    $entityManagers['default']->clear();

                    throw $e;
                }
            }
        }

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
    function deleteelementAction()
    {
        /** @var User_Service_User $userService */
        $userService = $this->get('User_Service_User');
        /** @var Orga_Service_ACLManager $aclManager */
        $aclManager = $this->get('Orga_Service_ACLManager');

        $idProject = $this->getParam('idProject');
        $project = Orga_Model_Project::load(array('id' => $idProject));
        $user = User_Model_User::load(array('id' => $this->delete));

        set_time_limit(0);
        $entityManagers = Zend_Registry::get('EntityManagers');
        $entityManagers['default']->getConnection()->beginTransaction();
        try {
            $entityManagers['default']->flush();

            $aclManager->removeProjectAdministrator(
                $project,
                $user
            );
            $entityManagers['default']->flush();

            $entityManagers['default']->getConnection()->commit();
        } catch (Exception $e) {
            $entityManagers['default']->getConnection()->rollback();
            $entityManagers['default']->clear();

            throw $e;
        }

        $userService->sendEmail(
            $user,
            __('User', 'email', 'subjectAccessRightsChange'),
            __('Orga', 'email', 'userProjectAdministratorRoleRemoved', array(
                'PROJECT' => $project->getLabel(),
            ))
        );

        $this->message = __('Orga', 'role', 'userRoleRemovedFromUser');
        $this->send();
    }

}