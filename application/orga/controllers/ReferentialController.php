<?php

use Classification\Application\Service\ClassificationExportService;
use Core\Annotation\Secure;
use Parameter\Application\Service\ExportService;
use User\Domain\User;

/**
 * @author valentin.claras
 */
class Orga_ReferentialController extends Core_Controller
{
    use UI_Controller_Helper_Form;

    /**
     * Redirection sur la liste.
     * @Secure("loggedIn")
     */
    public function indexAction()
    {
        $this->forward('exports');
    }

    /**
     * @Secure("loggedIn")
     */
    public function exportsAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        // Formats d'exports.
        $this->view->defaultFormat = 'xls';
        $this->view->formats = [
            'xls' => __('UI', 'export', 'xls'),
//            'xlsx' => __('UI', 'export', 'xlsx'),
//            'ods' => __('UI', 'export', 'ods'),
        ];

        // Liste des exports.
        $this->view->exports = [];

        // Classification.
        $this->view->exports['Classification'] = [
            'label' => __('Classification', 'classification', 'classification'),
            'versions' => [
                'latest' => __('Classification', 'classification', 'classification')
            ]
        ];

        // Parameter.
        $this->view->exports['Parameter'] = [
            'label' => __('Parameter', 'name', 'parameters'),
            'versions' => [
                'latest' => __('Parameter', 'name', 'parameters')
            ]
        ];

        // Unit.
        $this->view->exports['Unit'] = [
            'label' => __('Unit', 'name', 'units'),
        ];

    }

    /**
     * @Secure("loggedIn")
     */
    public function exportAction()
    {
        session_write_close();
        set_time_limit(0);
        PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_in_memory_gzip);

        $export = $this->getParam('export');
        $format = $this->getParam('format');
        if ($this->hasParam('version')) {
            $refVersion = $this->getParam('version');
        }
        $version = null;

        switch ($export) {
            case 'Classification':
            $exportService = new ClassificationExportService();
            $streamFunction = 'stream';
            $baseFilename = __('Classification', 'classification', 'classification');
            break;
            case 'Parameter':
                $exportService = new ExportService();
                $streamFunction = 'stream';
                $baseFilename = __('UI', 'name', 'parameters');
                break;
            case 'Unit':
                $exportService = new \Unit\Application\Service\UnitExport();
                $streamFunction = 'stream';
                $baseFilename = __('Unit', 'name', 'units');
                break;
            default:
                UI_Message::addMessageStatic(__('Orga', 'export', 'notFound'), UI_Message::TYPE_ERROR);
                $this->redirect('orga/referential/exports');
                break;
        }

        $date = date(str_replace('&nbsp;', '', __('DW', 'export', 'dateFormat')));
        $filename = $date.'_'.$baseFilename.'.'.$format;

        switch ($format) {
            case 'xlsx':
                $contentType = "Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                break;
            case 'xls':
                $contentType = "Content-type: application/vnd.ms-excel";
                break;
            case 'ods':
                $contentType = "Content-type: application/vnd.oasis.opendocument.spreadsheet";
                break;
        }
        header($contentType);
        header('Content-Disposition:attachement;filename='.$filename);
        header('Cache-Control: max-age=0');

        Zend_Layout::getMvcInstance()->disableLayout();
        Zend_Controller_Front::getInstance()->setParam('noViewRenderer', true);

        if ($version === null) {
            $exportService->$streamFunction($format);
        } else {
            $exportService->$streamFunction($format, $version);
        }
    }

}
