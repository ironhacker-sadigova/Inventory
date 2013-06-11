<?php
/**
 * @package Orga
 */

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

/**
 * Classe permettant de construire les ACL relatives aux éléments d'Orga.
 * @author valentin.claras
 * @package Orga
 *
 */
class Orga_Service_ACLManager extends Core_Singleton implements User_Service_ACL_ResourceTreeTraverser
{
    /**
     * Indique que l'Orga_ACLManager a détected des changements sur les ressources.
     *
     * @var bool
     */
    protected static $changesDetected = false;

    /**
     * Indique que l'Orga_ACLManager est en train de créer les ressources.
     *
     * @var bool
     */
    protected static $processing = false;

    /**
     * Ensemble des nouveaux Project.
     *
     * @var Orga_Model_Project[]
     */
    protected $newProjects = [];

    /**
     * Ensemble des nouveaux Cell.
     *
     * @var Orga_Model_Cell[]
     */
    protected $newCells = [];

    /**
     * Ensemble des nouveaux Report.
     *
     * @var DW_Model_Report[]
     */
    protected $newReports = [];

    /**
     * Ensemble des nouvelles Ressources.
     *
     * @var array
     */
    protected $newResources = [ 'project' => [], 'cell' => [], 'report' => [] ];

    /**
     * Ensemble des nouveaux Role.
     *
     * @var User_Model_Role[]
     */
    protected $newRoles = [];


    /**
     * Renvoie l'instance Singleton de la classe.
     *
     * @return Orga_Service_ACLManager
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }


    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
    {
        if (self::$processing === true) {
            return;
        }

        $entityManager = $eventArgs->getEntityManager();
        $unitOfWork = $entityManager->getUnitOfWork();

        // Créations
        foreach ($unitOfWork->getScheduledEntityInsertions() as $entity) {
            // Project
            if ($entity instanceof Orga_Model_Project) {
                $this->newProjects[] = $entity;
            }
            // Cell
            if ($entity instanceof Orga_Model_Cell) {
                $this->newCells[] = $entity;
            }
            // Report
            if ($entity instanceof DW_Model_Report) {
                $this->newReports[] = $entity;
            }
        }

        // Suppressions
        foreach ($unitOfWork->getScheduledEntityDeletions() as $entity) {
            // Project
            if ($entity instanceof Orga_Model_Project) {
                $this->processOldProject(User_Model_Resource_Entity::loadByEntity($entity));
            }
            // Cell
            if ($entity instanceof Orga_Model_Cell) {
                $this->processOldCell(User_Model_Resource_Entity::loadByEntity($entity));
            }
            // Report
            if ($entity instanceof DW_Model_Report) {
                $this->processOldReport(User_Model_Resource_Entity::loadByEntity($entity));
            }
        }

        if (!empty($this->newProjects) || !empty($this->newCells) || !empty($this->newReports)) {
            self::$changesDetected = true;
        }
    }

    /**
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        if ((self::$changesDetected === false) || (self::$processing === true)) {
            return;
        }
        self::$processing = true;


        foreach ($this->newProjects as $project) {
            $this->processNewProject($project);
        }
        $this->newProjects = array();

        foreach ($this->newCells as $cell) {
            $this->processNewCell($cell);
        }
        $this->newCells = array();

        foreach ($this->newReports as $report) {
            $this->processNewReport($report);
        }
        $this->newReports = array();


        $eventArgs->getEntityManager()->flush();

        $this->newResources = array('project' => array(), 'cell' => array(), 'report' => array());
        $this->newRoles = array();

        self::$changesDetected = false;
        self::$processing = false;
    }

    /**
     * Créer la ressource et roles d'un Project et génère les authorisations.
     *
     * @param Orga_Model_Project $project
     */
    protected function processNewProject(Orga_Model_Project $project)
    {
        // Création de la ressource projet donné.
        $projectResource = new User_Model_Resource_Entity();
        $projectResource->setEntity($project);
        $projectResource->save();
        $this->newResources['project'][$project->getId()] = $projectResource;

        // Création du rôle administrateur du projet donné.
        $projectAdministrator = new User_Model_Role();
        $projectAdministrator->setRef('projectAdministrator_'.$project->getId());
        $projectAdministrator->setName(__('Orga', 'role', 'projectAdministrator'));
        $projectAdministrator->save();
        $this->newRoles[$projectAdministrator->getRef()] = $projectAdministrator;

        // Ajout des autorisations du rôle administrateur sur la ressource.
        User_Service_ACL::getInstance()->allow(
            $projectAdministrator,
            User_Model_Action_Default::VIEW(),
            $projectResource
        );
        User_Service_ACL::getInstance()->allow(
            $projectAdministrator,
            User_Model_Action_Default::EDIT(),
            $projectResource
        );
        User_Service_ACL::getInstance()->allow(
            $projectAdministrator,
            User_Model_Action_Default::DELETE(),
            $projectResource
        );
    }

    /**
     * Créer la ressource et roles d'une Cell et génère les authorisations.
     *
     * @param Orga_Model_Cell $cell
     */
    protected function processNewCell(Orga_Model_Cell $cell)
    {
        $project = $cell->getGranularity()->getProject();

        if (isset($this->newResources['project'][$project->getId()])) {
            $projectResource = $this->newResources['project'][$project->getId()];
        } else {
            $projectResource = User_Model_Resource_Entity::loadByEntity($project);
        }

        // Création de la ressource cellule donnée.
        $cellResource = new User_Model_Resource_Entity();
        $cellResource->setEntity($cell);
        $cellResource->save();
        $this->newResources['cell'][$cell->getId()] = $cellResource;


        // Création du rôle administrateur de la cellule donnée.
        $cellAdministrator = new User_Model_Role();
        $cellAdministrator->setRef('cellAdministrator_'.$cell->getId());
        $cellAdministrator->setName(__('Orga', 'role', 'cellAdministrator'));
        $cellAdministrator->save();
        $this->newRoles[$cellAdministrator->getRef()] = $cellAdministrator;

        // Ajout des autorisations du rôle administrateur sur la ressource.
        User_Service_ACL::getInstance()->allow(
            $cellAdministrator,
            User_Model_Action_Default::VIEW(),
            $projectResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellAdministrator,
            User_Model_Action_Default::VIEW(),
            $cellResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellAdministrator,
            User_Model_Action_Default::EDIT(),
            $cellResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellAdministrator,
            User_Model_Action_Default::ALLOW(),
            $cellResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellAdministrator,
            Orga_Action_Cell::COMMENT(),
            $cellResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellAdministrator,
            Orga_Action_Cell::INPUT(),
            $cellResource
        );


        // Création du rôle contributeur de la cellule donnée.
        $cellContributor = new User_Model_Role();
        $cellContributor->setRef('cellContributor_'.$cell->getId());
        $cellContributor->setName(__('Orga', 'role', 'cellContributor'));
        $cellContributor->save();
        $this->newRoles[$cellContributor->getRef()] = $cellContributor;

        // Ajout des autorisations du rôle administrateur sur la ressource.
        User_Service_ACL::getInstance()->allow(
            $cellContributor,
            User_Model_Action_Default::VIEW(),
            $projectResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellContributor,
            User_Model_Action_Default::VIEW(),
            $cellResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellContributor,
            Orga_Action_Cell::COMMENT(),
            $cellResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellContributor,
            Orga_Action_Cell::INPUT(),
            $cellResource
        );


        // Création du rôle observateur de la cellule donnée.
        $cellObserver = new User_Model_Role();
        $cellObserver->setRef('cellObserver_'.$cell->getId());
        $cellObserver->setName(__('Orga', 'role', 'cellObserver'));
        $cellObserver->save();
        $this->newRoles[$cellObserver->getRef()] = $cellObserver;

        // Ajout des autorisations du rôle observateur sur la ressource.
        User_Service_ACL::getInstance()->allow(
            $cellObserver,
            User_Model_Action_Default::VIEW(),
            $projectResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellObserver,
            User_Model_Action_Default::VIEW(),
            $cellResource
        );
        User_Service_ACL::getInstance()->allow(
            $cellObserver,
            Orga_Action_Cell::COMMENT(),
            $cellResource
        );
    }

    /**
     * Créer la ressource d'un Report et génère les authorisations.
     *
     * @param DW_Model_Report $dWReport
     */
    protected function processNewReport(DW_Model_Report $dWReport)
    {
        // Création de la ressource Report donné.
        $reportResource = new User_Model_Resource_Entity();
        $reportResource->setEntity($dWReport);
        $reportResource->save();
        $this->newResources['report'][$dWReport->getId()] = $reportResource;


        // Cas spécifique d'un Report de Cell copié depuis le Cube d'une Granularity.
        if (Orga_Model_GranularityReport::isDWReportCopiedFromGranularityDWReport($dWReport)) {
            $cell = Orga_Model_Cell::loadByDWCube($dWReport->getCube());

            $cellAdministratorRoleRef = 'cellAdministrator_'.$cell->getId();
            if (isset($this->newRoles[$cellAdministratorRoleRef])) {
                $cellAdministrator = $this->newRoles[$cellAdministratorRoleRef];
            } else {
                $cellAdministrator = User_Model_Role::loadByRef($cellAdministratorRoleRef);
            }
            User_Service_ACL::getInstance()->allow(
                $cellAdministrator,
                User_Model_Action_Default::VIEW(),
                $reportResource
            );

            $cellContributorRoleRef = 'cellContributor_'.$cell->getId();
            if (isset($this->newRoles[$cellContributorRoleRef])) {
                $cellContributor = $this->newRoles[$cellContributorRoleRef];
            } else {
                $cellContributor = User_Model_Role::loadByRef($cellContributorRoleRef);
            }
            User_Service_ACL::getInstance()->allow(
                $cellContributor,
                User_Model_Action_Default::VIEW(),
                $reportResource
            );

            $cellObserverRoleRef = 'cellObserver_'.$cell->getId();
            if (isset($this->newRoles[$cellObserverRoleRef])) {
                $cellObserver = $this->newRoles[$cellObserverRoleRef];
            } else {
                $cellObserver = User_Model_Role::loadByRef($cellObserverRoleRef);
            }
            User_Service_ACL::getInstance()->allow(
                $cellObserver,
                User_Model_Action_Default::VIEW(),
                $reportResource
            );

            return;
        }


        // Vérification de l'origine du report pour déterminer l'identité de celui qui possède les droits.
        try {
            Orga_Model_GranularityReport::loadByGranularityDWReport($dWReport);
            $granularity = Orga_Model_Granularity::loadByDWCube($dWReport->getCube());
            $projectAdministratorRoleRef = 'projectAdministrator_'.$granularity->getProject()->getId();
            if (isset($this->newRoles[$projectAdministratorRoleRef])) {
                $identity = $this->newRoles[$projectAdministratorRoleRef];
            } else {
                $identity = User_Model_Role::loadByRef($projectAdministratorRoleRef);
            }
        } catch (Core_Exception_NotFound $e) {
            // Le Report n'est pas issue d'un Cube de DW de Granularity.
            $identity = User_Model_User::load(Zend_Auth::getInstance()->getIdentity());
        }

        User_Service_ACL::getInstance()->allow(
            $identity,
            User_Model_Action_Default::VIEW(),
            $reportResource
        );
        User_Service_ACL::getInstance()->allow(
            $identity,
            User_Model_Action_Default::EDIT(),
            $reportResource
        );
        User_Service_ACL::getInstance()->allow(
            $identity,
            User_Model_Action_Default::DELETE(),
            $reportResource
        );
    }

    /**
     * Supprime la ressource et roles d'un Project.
     *
     * @param User_Model_Resource_Entity $projectResource
     */
    protected function processOldProject(User_Model_Resource_Entity $projectResource)
    {
        $idProject = $projectResource->getEntityIdentifier();

        $projectResource->delete();

        $projectAdministrator = User_Model_Role::loadByRef('projectAdministrator_'.$idProject);
        $projectAdministrator->delete();
    }

    /**
     * Supprime la ressource et roles d'une Cell.
     *
     * @param User_Model_Resource_Entity $cellResource
     */
    protected function processOldCell(User_Model_Resource_Entity $cellResource)
    {
        $idCell = $cellResource->getEntityIdentifier();

        $cellResource->delete();

        $cellAdministrator = User_Model_Role::loadByRef('cellAdministrator_'.$idCell);
        $cellAdministrator->delete();

        $cellContributor = User_Model_Role::loadByRef('cellContributor_'.$idCell);
        $cellContributor->delete();

        $cellObserver = User_Model_Role::loadByRef('cellObserver_'.$idCell);
        $cellObserver->delete();
    }

    /**
     * Supprime la ressource d'un Report.
     *
     * @param User_Model_Resource_Entity $reportResource
     */
    protected function processOldReport(User_Model_Resource_Entity $reportResource)
    {
        $idReport = $reportResource->getEntityIdentifier();

        $reportResource->delete();
    }


    /**
     * Trouve les ressources parent d'une ressource
     *
     * @param User_Model_Resource_Entity $resource
     *
     * @return User_Model_Resource_Entity[] Tableau indexé par l'ID de chaque ressource pour éviter les doublons
     */
    public function getParentResources(User_Model_Resource_Entity $resource)
    {
        $parentResources = [];

        /** @var Orga_Model_Cell $cell */
        $cell = $resource->getEntity();

        try {
            // Si la cellule a été supprimée, il n'y a plus de parents
            $parentCells = $cell->getParentCells();
        } catch (Core_Exception_NotFound $e) {
            return $parentResources;
        }

        foreach ($parentCells as $parentCell) {
            if (isset($this->newResources['cell'][$parentCell->getId()])) {
                $parentResource = $this->newResources['cell'][$parentCell->getId()];
            } else {
                $parentResource = User_Model_Resource_Entity::loadByEntity($parentCell);
            }
            if ($parentResource !== null) {
                $parentResources[] = $parentResource;
            }
        }

        return $parentResources;
    }

    /**
     * Trouve les ressources filles d'une ressource
     *
     * @param User_Model_Resource_Entity $resource
     *
     * @return User_Model_Resource_Entity[] Tableau indexé par l'ID de chaque ressource pour éviter les doublons
     */
    public function getChildResources(User_Model_Resource_Entity $resource)
    {
        $childResources = [];

        /** @var Orga_Model_Cell $cell */
        $cell = $resource->getEntity();

        foreach ($cell->getChildCells() as $childCell) {
            if (isset($this->newResources['cell'][$childCell->getId()])) {
                $childResource = $this->newResources['cell'][$childCell->getId()];
            } else {
                $childResource = User_Model_Resource_Entity::loadByEntity($childCell);
            }
            if ($childResource !== null) {
                $childResources[] = $childResource;
            }
        }

        return $childResources;
    }

    /**
     * Ajoute au projet donné, l'utilisateur comme administrateur.
     *
     * @param Orga_Model_Project $project
     * @param User_Model_User $user
     */
    public function addProjectAdministrator($project, $user)
    {
        $user->addRole(User_Model_Role::loadByRef('projectAdministrator_'.$project->getId()));

        $globalCell = Orga_Model_Granularity::loadByRefAndProject('global', $project)->getCells()[0];
        $user->addRole(
            User_Model_Role::loadByRef('cellAdministrator_'.$globalCell->getId())
        );
    }

    /**
     * Retire au projet donné, l'utilisateur comme administrateur.
     *
     * @param Orga_Model_Project $project
     * @param User_Model_User $user
     */
    public function removeProjectAdministrator($project, $user)
    {
        $user->removeRole(User_Model_Role::loadByRef('projectAdministrator_'.$project->getId()));

        $globalCell = Orga_Model_Granularity::loadByRefAndProject('global', $project)->getCells()[0];
        $user->removeRole(
            User_Model_Role::loadByRef('cellAdministrator_'.$globalCell->getId())
        );
    }

}