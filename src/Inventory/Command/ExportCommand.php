<?php

namespace Inventory\Command;

use AF\Domain\Category as AFCategory;
use Doctrine\ORM\EntityManager;
use Gedmo\Translatable\Entity\Translation;
use Serializer\Serializer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Techno\Domain\Category as TechnoCategory;
use User\Domain\User;

/**
 * Exporte les données.
 *
 * @author matthieu.napoli
 */
class ExportCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('export')
            ->setDescription('Exporte les données');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('xdebug.max_nesting_level', 1000);

        $root = PACKAGE_PATH . '/data/exports/migration-3.0';

        $serializer = new Serializer([
            \Classif_Model_Axis::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \Classif_Model_Context::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \Classif_Model_Indicator::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \Classif_Model_Member::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \Techno\Domain\Category::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \Techno\Domain\Family\Family::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'documentation' => ['translated' => true],
                ],
            ],
            \Techno\Domain\Family\Dimension::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'documentation' => ['translated' => true],
                ],
            ],
            \Techno\Domain\Family\Member::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'documentation' => ['translated' => true],
                ],
            ],
            \AF\Domain\Category::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \AF\Domain\AF::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \AF\Domain\Algorithm\Numeric\NumericConstantAlgo::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \AF\Domain\Algorithm\Numeric\NumericExpressionAlgo::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \AF\Domain\Algorithm\Numeric\NumericInputAlgo::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \AF\Domain\Algorithm\Numeric\NumericParameterAlgo::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\Checkbox::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'help' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\Group::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'help' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\NumericField::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'help' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\Select\SelectMulti::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'help' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\Select\SelectSingle::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'help' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\SubAF\NotRepeatedSubAF::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'help' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\SubAF\RepeatedSubAF::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'help' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\TextField::class => [
                'properties' => [
                    'label' => ['translated' => true],
                    'help' => ['translated' => true],
                ],
            ],
            \AF\Domain\Component\Select\SelectOption::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \Orga\Model\ACL\Role\OrganizationAdminRole::class => [ 'exclude' => true ],
            \Orga\Model\ACL\Role\CellAdminRole::class => [ 'exclude' => true ],
            \Orga\Model\ACL\Role\CellManagerRole::class => [ 'exclude' => true ],
            \Orga\Model\ACL\Role\CellContributorRole::class => [ 'exclude' => true ],
            \Orga\Model\ACL\Role\CellObserverRole::class => [ 'exclude' => true ],
            \Orga\Model\ACL\OrganizationAuthorization::class => [ 'exclude' => true ],
            \Orga\Model\ACL\CellAuthorization::class => [ 'exclude' => true ],
            \DW_Model_Cube::class => [ 'exclude' => true ],
            \DW_Model_Axis::class => [ 'exclude' => true ],
            \DW_Model_Member::class => [ 'exclude' => true ],
            \DW_Model_Indicator::class => [ 'exclude' => true ],
            \DW_Model_Result::class => [ 'exclude' => true ],
            \DW_Model_Report::class => [ 'exclude' => true ],
            \DW_Model_Filter::class => [ 'exclude' => true ],
            \DateTime::class => [
                'serialize' => true,
            ],
            User::class => [
                'properties' => [
                    'roles' => [
                        'exclude' => true,
                    ],
                    'authorizations' => [
                        'exclude' => true,
                    ],
                    'acl' => [
                        'exclude' => true,
                    ],
                ],
            ],
            \Social_Model_Comment::class => [
                'properties' => [
                    'author' => [
                        'transform' => function (User $author) {
                            return $author->getEmail();
                        },
                    ],
                ],
            ],
            \Orga_Model_Organization::class => [
                'properties' => [
                    'acl' => [ 'exclude' => true ],
                    'label' => ['translated' => true],
                ],
            ],
            \Orga_Model_Axis::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \Orga_Model_Member::class => [
                'properties' => [
                    'label' => ['translated' => true],
                ],
            ],
            \Orga_Model_Cell::class => [
                'properties' => [
                    'acl' => [ 'exclude' => true ],
                    'dwResults' => [ 'exclude' => true ],
                ],
            ],
            \Calc_UnitValue::class => [
                'serialize' => true,
            ],
            \Calc_Value::class => [
                'serialize' => true,
            ],
        ], $this->entityManager->getRepository(Translation::class));

        $output->writeln('<comment>Exporting users</comment>');
        $data = User::loadList();
        file_put_contents($root . '/users.json', $serializer->serialize($data));

        $output->writeln('<comment>Exporting classification</comment>');
        $data = [
            \Classif_Model_Indicator::loadList(),
            \Classif_Model_Axis::loadList(),
            \Classif_Model_Context::loadList(),
            \Classif_Model_ContextIndicator::loadList(),
        ];
        file_put_contents($root . '/classification.json', $serializer->serialize($data));

        $output->writeln('<comment>Exporting parameters</comment>');
        $data = TechnoCategory::loadRootCategories();
        file_put_contents($root . '/parameters.json', $serializer->serialize($data));

        $output->writeln('<comment>Exporting AF</comment>');
        $data = AFCategory::loadRootCategories();
        file_put_contents($root . '/af.json', $serializer->serialize($data));

        $output->writeln('<comment>Exporting Orga</comment>');
        $data = \Orga_Model_Organization::loadList();
        file_put_contents($root . '/orga.json', $serializer->serialize($data));

        $reportsData = [];
        $aclData = [];
        /** @var \Orga_Model_Organization $organization */
        foreach (\Orga_Model_Organization::loadList() as $organization) {
            $organizationAdmins = [];
            foreach ($organization->getAdminRoles() as $adminRoles) {
                $organizationAdmins[] = $adminRoles->getUser()->getEmail();
            }

            $granularitiesACL = [];
            $granularitiesReports = [];

            foreach ($organization->getGranularities() as $granularity) {
                if ($granularity->getCellsWithACL()) {
                    $cellsACL = [];
                    foreach ($granularity->getCells() as $cell) {
                        $cellAdmins = [];
                        foreach ($cell->getAdminRoles() as $cellAdmin) {
                            $cellAdmins[] = $cellAdmin->getUser()->getEmail();
                        }
                        $cellManagers = [];
                        foreach ($cell->getManagerRoles() as $cellManager) {
                            $cellManagers[] = $cellManager->getUser()->getEmail();
                        }
                        $cellContributors = [];
                        foreach ($cell->getContributorRoles() as $cellContributor) {
                            $cellContributors[] = $cellContributor->getUser()->getEmail();
                        }
                        $cellObservers = [];
                        foreach ($cell->getObserverRoles() as $cellObserver) {
                            $cellObservers[] = $cellObserver->getUser()->getEmail();
                        }
                        if ((count($cellAdmins) > 0) || (count($cellManagers) > 0)
                            || (count($cellContributors) > 0) || (count($cellObservers) > 0)) {
                            $cellMembers = $cell->getMembers();
                            $cellDataObject = new \StdClass();
                            $cellDataObject->type = 'cell';
                            $cellDataObject->members = array_map(
                                function ($m) { return $m->getAxis()->getRef() . ';' . $m->getCompleteRef(); },
                                $cellMembers
                            );
                            $cellDataObject->admins = $cellAdmins;
                            $cellDataObject->managers = $cellManagers;
                            $cellDataObject->contributors = $cellContributors;
                            $cellDataObject->observers = $cellObservers;
                            $cellsACL[] = $cellDataObject;
                        }
                    }

                    if (count($cellsACL) > 0) {
                        $granularityAxes = $granularity->getAxes();
                        $granularityDataObject = new \StdClass();
                        $granularityDataObject->type = 'granularity';
                        $granularityDataObject->granularityAxes = array_map(
                            function ($a) { return $a->getRef(); },
                            $granularityAxes
                        );
                        $granularityDataObject->cellsACL = $cellsACL;
                        $granularitiesACL[] = $granularityDataObject;
                    }
                }

                if ($granularity->getCellsGenerateDWCubes()) {
                    $granularityReports = [];
                    foreach ($granularity->getDWCube()->getReports() as $granularityReport) {
                        $granularityReports[] = $granularityReport;
                    }
                    // Les rapports personnalisés ne fonctionnent pas dans la version actuelle.
                    //@see http://tasks.myc-sense.com/issues/7077
                    $cellsReports = [];
                    $granularityAxes = $granularity->getAxes();
                    $granularityDataObject = new \StdClass();
                    $granularityDataObject->type = 'granularity';
                    $granularityDataObject->granularityAxes = array_map(
                        function ($a) { return $a->getRef(); },
                        $granularityAxes
                    );
                    $granularityDataObject->granularityReports = $granularityReports;
                    $granularityDataObject->cellsReports = $cellsReports;
                    $granularitiesReports[] = $granularityDataObject;
                }
            }
            if ((count($organizationAdmins) > 0) || (count($granularitiesACL) > 0)) {
                $organizationDataObject = new \StdClass();
                $organizationDataObject->type = 'organization';
                $organizationDataObject->label = $organization->getLabel();
                $organizationDataObject->admins = $organizationAdmins;
                $organizationDataObject->granularitiesACL = $granularitiesACL;
                $aclData[] = $organizationDataObject;
            }
            if (count($granularitiesReports) > 0) {
                $organizationDataObject = new \StdClass();
                $organizationDataObject->type = 'organization';
                $organizationDataObject->label = $organization->getLabel();
                $organizationDataObject->granularitiesReports = $granularitiesReports;
                $reportsData[] = $organizationDataObject;
            }
        }

        $output->writeln('<comment>Exporting Reports</comment>');
        $reportsSerializer = new Serializer(
            [
                \DW_Model_Report::class => [
                    'properties' => [
                        'cube' => [
                            'exclude' => true,
                        ],
                        'numerator' => [
                            'transform' => function ($i) { return ($i != null) ? $i->getRef() : null; },
                        ],
                        'denominator' => [
                            'transform' => function ($i) { return ($i != null) ? $i->getRef() : null; },
                        ],
                        'numeratorAxis1' => [
                            'transform' => function ($i) { return ($i != null) ? $i->getRef() : null; },
                        ],
                        'numeratorAxis2' => [
                            'transform' => function ($i) { return ($i != null) ? $i->getRef() : null; },
                        ],
                        'denominatorAxis1' => [
                            'transform' => function ($i) { return ($i != null) ? $i->getRef() : null; },
                        ],
                        'denominatorAxis2' => [
                            'transform' => function ($i) { return ($i != null) ? $i->getRef() : null; },
                        ],
                    ],
                ],
                \DW_Model_Filter::class => [
                    'properties' => [
                        'cube' => [
                            'exclude' => true,
                        ],
                        'axis' => [
                            'transform' => function ($i) { return $i->getRef(); },
                        ],
                        'members' => [
                            'transform' => function ($i) {
                                    $members = $i->toArray();
                                    return array_map(function ($m) { return $m->getRef(); }, $members);
                                },
                        ],
                    ],
                ],
            ],
            $this->entityManager->getRepository(Translation::class)
        );
        file_put_contents($root . '/reports.json', $reportsSerializer->serialize($reportsData));

        $output->writeln('<comment>Exporting ACL</comment>');
        $aclSerializer = new Serializer([]);
        file_put_contents($root . '/acl.json', $aclSerializer->serialize($aclData));
    }
}
