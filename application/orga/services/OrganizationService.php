<?php

use Doctrine\ORM\EntityManager;
use User\Domain\ACL\Role\Role;
use User\Domain\User;

class Orga_Service_OrganizationService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Orga_Service_ACLManager
     */
    private $aclManager;

    /**
     * @param EntityManager           $entityManager
     * @param Orga_Service_ACLManager $aclManager
     */
    public function __construct(EntityManager $entityManager, Orga_Service_ACLManager $aclManager)
    {
        $this->entityManager = $entityManager;
        $this->aclManager = $aclManager;
    }

    /**
     * Crée un projet et assigne un utilisateur comme administrateur
     *
     * @param User $administrator
     * @param string $label
     * @throws Exception
     * @return Orga_Model_Organization
     */
    public function createOrganization(User $administrator, $label)
    {
        $this->entityManager->beginTransaction();

        try {
            // Création de l'organization.
            $organization = new Orga_Model_Organization();
            $organization->setLabel($label);
            // Création d'une granularité globale par défaut.
            $defaultGranularity = new Orga_Model_Granularity($organization);
            $defaultGranularity->setNavigability(true);
            $defaultGranularity->setCellsWithOrgaTab(true);
            $defaultGranularity->setCellsWithACL(true);
            $defaultGranularity->setCellsWithAFConfigTab(true);
            // Sauvegarde.
            $organization->save();
            $this->entityManager->flush();
            // Définition de la création des DW après pour éviter un bug d'insertion.
            $defaultGranularity->setCellsGenerateDWCubes(true);
            $organization->save();

            // Ajout de l'utilisateur courant en tant qu'administrateur.
            $this->aclManager->addOrganizationAdministrator($organization, $administrator->getEmail(), false);
            $this->entityManager->flush();

            $this->entityManager->commit();

            return $organization;
        } catch (Exception $e) {
            $this->entityManager->rollback();
            $this->entityManager->clear();

            throw $e;
        }
    }

    /**
     * Supprime un projet
     *
     * @param Orga_Model_Organization $organization
     * @throws Exception
     */
    public function deleteOrganization(Orga_Model_Organization $organization)
    {
        $this->entityManager->beginTransaction();

        try {
            $organization = Orga_Model_Organization::load($organization->getId());
            $organization->setGranularityForInventoryStatus();

            $this->entityManager->flush();
            $this->entityManager->clear();

            $organization = Orga_Model_Organization::load($organization->getId());
            $granularities = $organization->getGranularities()->toArray();
            foreach (array_reverse($granularities) as $granularity) {
                $granularity = Orga_Model_Granularity::load($granularity->getId());
                $granularity->setInputConfigGranularity();
                $granularity->delete();
            }

            $this->entityManager->flush();
            $this->entityManager->clear();

            $organization = Orga_Model_Organization::load($organization->getId());
            $organization->delete();

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            $this->entityManager->clear();

            throw $e;
        }
    }
}
