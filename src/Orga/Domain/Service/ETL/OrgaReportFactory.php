<?php

namespace Orga\Domain\Service\ETL;

use Doctrine\ORM\EntityManager;
use Core_Event_ObserverInterface;
use Core_Exception_NotFound;
use DW\Application\Service\ReportService;
use DW\Domain\Cube;
use DW\Domain\Report;
use Orga\Domain\Cell;
use Orga\Domain\Report\CellReport;
use Orga\Domain\Granularity;
use Orga\Domain\Report\GranularityReport;
use Orga\Domain\Service\OrgaDomainHelper;
use User\Application\ForbiddenException;
use User\Domain\User;
use Zend_Auth;

/**
 * OrgaReportFactory.
 *
 * @author valentin.claras
 */
class OrgaReportFactory implements Core_Event_ObserverInterface
{
    /**
     * @var string[]
     */
    private static $copiedReports = [];

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ReportService
     */
    private $reportService;


    /**
     * @param EntityManager $entityManager
     * @param ReportService $reportService
     */
    public function __construct(EntityManager $entityManager, ReportService $reportService)
    {
        $this->entityManager = $entityManager;
        $this->reportService = $reportService;
    }

    /**
     * @param string $event
     * @param Report $subject
     * @param array $arguments
     * @throws ForbiddenException
     */
    public static function applyEvent($event, $subject, $arguments = [])
    {
        $orgaReportFactory = OrgaDomainHelper::getOrgaReportFactory();

        switch ($event) {
            case Report::EVENT_SAVE:
                if ($subject->getCube()->getId() == null) {
                    return;
                }
                try {
                    // Nécessaire pour détecter d'où est issu le Report.
                    Granularity::loadByDWCube($subject->getCube());
                    $granularityReport = new GranularityReport($subject);
                    $orgaReportFactory->createCellsDWReportFromGranularityReport($granularityReport);
                    $granularityReport->save();
                } catch (Core_Exception_NotFound $e) {
                    if (!in_array(spl_object_hash($subject), self::$copiedReports)) {
                        // Le Report n'est pas issue d'un Cube de Granularity.
                        $auth = Zend_Auth::getInstance();
                        if (!$auth->hasIdentity()) {
                            throw new ForbiddenException();
                        }
                        $connectedUser = User::load($auth->getIdentity());
                        $cellReport = new CellReport($subject, $connectedUser);
                        $cellReport->save();
                    }
                }
                break;
            case Report::EVENT_UPDATED:
                try {
                    $orgaReportFactory->updateCellsDWReportFromGranularityReport(
                        GranularityReport::loadByGranularityDWReport($subject)
                    );
                } catch (Core_Exception_NotFound $e) {
                    // Le Report n'est pas issue d'un Cube de DW de Granularity.
                }
                break;
            case Report::EVENT_DELETE:
                try {
                    $granularityReport = GranularityReport::loadByGranularityDWReport($subject);
                    $granularityReport->delete();
                } catch (Core_Exception_NotFound $e) {
                    // Le Report n'est pas issue d'un Cube de Granularity.
                    try {
                        $cellReport = CellReport::loadByCellDWReport($subject);
                        $cellReport->delete();
                    } catch (Core_Exception_NotFound $e) {
                        // Le Report n'est pas issue d'un Utilisateur.
                    }
                }
                break;
        }
    }

    /**
     * @param GranularityReport $granularityReport
     */
    private function createCellsDWReportFromGranularityReport(GranularityReport $granularityReport)
    {
        $granularity = Granularity::loadByDWCube($granularityReport->getGranularityDWReport()->getCube());
        foreach ($granularity->getCells() as $cell) {
            $this->copyGranularityReportToCellDWCube($granularityReport, $cell->getDWCube());
        }
    }

    /**
     * @param GranularityReport $granularityReport
     */
    private function updateCellsDWReportFromGranularityReport(GranularityReport $granularityReport)
    {
        $granularityDWReport = $granularityReport->getGranularityDWReport();
        foreach ($granularityReport->getCellDWReports() as $cellDWReport) {
            $this->reportService->updateReportFromAnother(
                $cellDWReport,
                $granularityDWReport
            )->save();
            $this->entityManager->flush($cellDWReport);
        }
    }

    /**
     * @param \Orga\Domain\Report\GranularityReport $granularityReport
     * @param Cube $dWCube
     */
    private function copyGranularityReportToCellDWCube(
        GranularityReport $granularityReport,
        Cube $dWCube
    ) {
        $reportCopy = $this->reportService->copyReportToCube($granularityReport->getGranularityDWReport(), $dWCube);
        self::$copiedReports[] = spl_object_hash($reportCopy);
        $granularityReport->addCellDWReport($reportCopy);
    }

    /**
     * @param Cell $cell
     */
    public function addGranularityDWReportsToCellDWCube(Cell $cell)
    {
        foreach ($cell->getGranularity()->getDWCube()->getReports() as $granularityDWReport) {
            $granularityReport = GranularityReport::loadByGranularityDWReport($granularityDWReport);
            $this->copyGranularityReportToCellDWCube($granularityReport, $cell->getDWCube());
        }
    }
}
