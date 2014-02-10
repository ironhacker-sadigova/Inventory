<?php

namespace Inventory\Command\PopulateDB;

use Doctrine\ORM\EntityManager;
use Inventory\Command\PopulateDB\TestDataSet\PopulateAF;
use Inventory\Command\PopulateDB\TestDataSet\PopulateClassif;
use Inventory\Command\PopulateDB\TestDataSet\PopulateOrga;
use Inventory\Command\PopulateDB\TestDataSet\PopulateTechno;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Jeu de données de test.
 *
 * @author matthieu.napoli
 */
class TestDataSet
{
    /**
     * @var BasicDataSet
     */
    private $basicDataSet;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var PopulateClassif
     */
    private $populateClassif;

    /**
     * @var PopulateTechno
     */
    private $populateTechno;

    /**
     * @var PopulateAF
     */
    private $populateAF;

    /**
     * @var PopulateOrga
     */
    private $populateOrga;

    public function __construct(
        BasicDataSet $basicDataSet,
        EntityManager $entityManager,
        PopulateClassif $populateClassif,
        PopulateTechno $populateTechno,
        PopulateAF $populateAF,
        PopulateOrga $populateOrga
    ) {
        $this->basicDataSet = $basicDataSet;
        $this->entityManager = $entityManager;
        $this->populateClassif = $populateClassif;
        $this->populateTechno = $populateTechno;
        $this->populateAF = $populateAF;
        $this->populateOrga = $populateOrga;
    }

    public function run(OutputInterface $output)
    {
        // Charge le jeu de données basique
        $this->basicDataSet->run($output);
        $this->entityManager->clear();

        $output->writeln('<comment>Populating with the test data set</comment>');

        $this->populateClassif->run($output);
        $this->populateTechno->run($output);
        $this->populateAF->run($output);
        $this->populateOrga->run($output);
    }
}