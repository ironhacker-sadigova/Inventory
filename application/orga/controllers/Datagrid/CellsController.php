<?php
/**
 * @author valentin.claras
 * @package Orga
 */

use Core\Annotation\Secure;


/**
 * Controller de cells
 * @package Orga
 */
class Orga_Datagrid_CellsController extends UI_Controller_Datagrid
{
    /**
     * Methode appelee pour remplir le tableau.
     * @Secure("viewProject")
     */
    public function getelementsAction()
    {
        $project = Orga_Model_Project::load(array('id' => $this->getParam('idProject')));
        /* @var User_Model_User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $listCellResource = array();
        foreach ($connectedUser->getLinkedResources() as $cellResource) {
            if (($cellResource instanceof User_Model_Resource_Entity)
                && ($cellResource->getEntity() instanceof Orga_Model_Cell)
                && ($cellResource->getEntity()->getProject() === $project)
                && (!in_array($cellResource, $listCellResource))
            ) {
                $listCellResource[] = $cellResource;
            }
        }
        foreach ($connectedUser->getRoles() as $userRole) {
            foreach ($userRole->getLinkedResources() as $cellResource) {
                if (($cellResource instanceof User_Model_Resource_Entity)
                    && ($cellResource->getEntity() instanceof Orga_Model_Cell)
                    && ($cellResource->getEntity()->getProject() === $project)
                    && (!in_array($cellResource, $listCellResource))
                ) {
                    $listCellResource[] = $cellResource;
                }
            }
        }

        foreach ($listCellResource as $cellResource) {
            $orgaCell = $cellResource->getEntity()->getOrgaCell();
            $data = array();
            $data['index'] = $orgaCell->getKey()['id'];
            $data['label'] = $orgaCell->getLabel();
            $data['granularity'] = $orgaCell->getGranularity()->getRef();

            $access = array();
            foreach ($cellResource->getLinkedSecurityIdentities() as $securityIdentity) {
                if (($securityIdentity instanceof User_Model_Role) && ($connectedUser->hasRole($securityIdentity))) {
                    $access[] = explode('_', $securityIdentity->getRef())[0];
                }
            }
            $data['access'] = $this->cellList($access);

            $data['details'] = $this->cellLink('orga/cell/details/idCell/'.$orgaCell->getKey()['id']);
            $this->addLine($data);
        }

        $this->send();
    }


}
