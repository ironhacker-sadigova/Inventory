<?php
/**
 * Classe Orga_CellController
 * @author valentin.claras
 * @author sidoine.tardieu
 * @package    Orga
 * @subpackage Controller
 */

use Core\Annotation\Secure;

/**
 * Classe controleur de cell.
 * @package    Orga
 * @subpackage Controller
 */
class Orga_CellController extends Core_Controller
{
    /**
     * Affiche le détail d'une cellule.
     * @Secure("viewCell")
     */
    public function detailsAction()
    {
        $this->view->headLink()->appendStylesheet('css/orga/navigation.css');
        UI_Datagrid::addHeader();
        UI_Tree::addHeader();

        $idCell = $this->getParam('idCell');
        $this->view->idCell = $idCell;
        $cell = Orga_Model_Cell::load($this->getParam('idCell'));
        $granularity = $cell->getGranularity();
        $project = $granularity->getProject();
        $idProject = $project->getId();

        $this->view->cell = $cell;

        $connectedUser = $this->_helper->auth();
        $aclService = User_Service_ACL::getInstance();

        if ($this->hasParam('tab')) {
            $tab = $this->getParam('tab');
        } else {
            $tab = 'inputs';
        }


        $this->view->tabView = new UI_Tab_View('container');
        $this->view->pageTitle = $cell->getLabelExtended().' <small>'.$project->getLabel().'</small>';
        $this->view->isParentCellReachable = array();
        foreach ($cell->getParentCells() as $parentCell) {
            $isUserAllowedToViewParentCell = $aclService->isAllowed(
                $connectedUser,
                User_Model_Action_Default::VIEW(),
                $parentCell
            );
            if (!$isUserAllowedToViewParentCell) {
                $this->view->isParentCellReachable[$parentCell->getMembersHashKey()] = false;
            }
        }


        // TAB ORGA.
        $isUserAllowedToEditProject = $aclService->isAllowed(
            $connectedUser,
            User_Model_Action_Default::EDIT(),
            $project
        );
        $isUserAllowedToEditCell = $aclService->isAllowed(
            $connectedUser,
            User_Model_Action_Default::EDIT(),
            $cell
        );
        if ($isUserAllowedToEditProject || ($isUserAllowedToEditCell && $granularity->getCellsWithOrgaTab())) {
            $projectTab = new UI_Tab('project');
            $projectTab->label = __('UI', 'name', 'project');
            $projectSubTabs = array('project', 'axes', 'granularities', 'members', 'childCells', 'relevant', 'consistency');
            if (in_array($tab, $projectSubTabs)) {
                $projectTab->active = true;
            }
            $projectTab->dataSource = 'orga/tab_celldetails/orga/idCell/'.$idCell.'/tab/'.$tab.'/display/render';
            $projectTab->useCache = true;
            $this->view->tabView->addTab($projectTab);
        }


        // TAB ACL
        $isUserAllowedToAllowAuthorizations = $aclService->isAllowed(
            $connectedUser,
            User_Model_Action_Default::ALLOW(),
            $cell
        );
        if (($isUserAllowedToAllowAuthorizations === true) && ($granularity->getCellsWithACL() === false)) {
            foreach ($granularity->getNarrowerGranularities() as $narrowerGranularity) {
                if ($narrowerGranularity->getCellsWithACL()) {
                    $isUserAllowedToAllowAuthorizations = ($isUserAllowedToAllowAuthorizations && true);
                    break;
                }
            }
        }
        if ($isUserAllowedToEditProject || $isUserAllowedToAllowAuthorizations) {
            $aclsTab = new UI_Tab('acls');
            if ($tab === 'acls') {
                $aclsTab->active = true;
            }
            $aclsTab->label = __('User', 'name', 'roles');
            $aclsTab->dataSource = 'orga/tab_celldetails/acls/idCell/'.$idCell;
            $aclsTab->useCache = !$isUserAllowedToEditProject;
            $this->view->tabView->addTab($aclsTab);
        }


        // TAB AF INPUT CONFIGURATION
        if (($isUserAllowedToEditCell) && ($granularity->getCellsWithAFConfigTab() === true)) {
            $aFConfigurationTab = new UI_Tab('aFConfiguration');
            if ($tab === 'aFConfiguration') {
                $aFConfigurationTab->active = true;
            }
            $aFConfigurationTab->label = __('UI', 'name', 'forms');
            $aFConfigurationTab->dataSource = 'orga/tab_celldetails/afconfiguration/idCell/'.$idCell;
            $aFConfigurationTab->useCache = !$isUserAllowedToEditProject;
            $this->view->tabView->addTab($aFConfigurationTab);
        }


        // TAB INVENTORIES
        $inventoriesTab = new UI_Tab('inventories');
        try {
            $granularityForInventoryStatus = $project->getGranularityForInventoryStatus();
        } catch (Core_Exception_UndefinedAttribute $e) {
            $granularityForInventoryStatus = null;
        }
        if ($granularityForInventoryStatus=== null) {
            $inventoriesTab->disabled = true;
        } else if ($tab === 'inventories') {
            $inventoriesTab->active = true;
        }
        $inventoriesTab->label = __('Orga', 'name', 'inventories');
        $inventoriesTab->dataSource = 'orga/tab_celldetails/inventories/idCell/'.$idCell;
        $this->view->tabView->addTab($inventoriesTab);


        // TAB INPUTS
        $inputsTab = new UI_Tab('inputs');
        if ($tab === 'inputs') {
            $inputsTab->active = true;
        }
        $inputsTab->label = __('UI', 'name', 'inputs');
        $inputsTab->dataSource = 'orga/tab_celldetails/afinputs/idCell/'.$idCell;
        $inputsTab->useCache = !$isUserAllowedToEditProject;
        $this->view->tabView->addTab($inputsTab);


        // TAB ANALYSES
        if ($granularity->getCellsGenerateDWCubes() === true) {
            $analysisTab = new UI_Tab('analyses');
            if ($tab === 'analyses') {
                $analysisTab->active = true;
            }
            $analysisTab->label = __('DW', 'name', 'analyses');
            $analysisTab->dataSource = 'orga/tab_celldetails/analyses/idCell/'.$idCell;
            $analysisTab->useCache = true;
            $this->view->tabView->addTab($analysisTab);
        }


        // TAB GENERIC ACTIONS
        if ($granularity->getCellsWithSocialGenericActions() === true) {
            $genericActionsTab = new UI_Tab('genericActions');
            if ($tab === 'genericActions') {
                $genericActionsTab->active = true;
            }
            $genericActionsTab->label = __('Social', 'name', 'actionTemplates');
            $genericActionsTab->dataSource = 'orga/tab_celldetails/genericactions?idCell='.$idCell;
            $this->view->tabView->addTab($genericActionsTab);
        }


        // TAB CONTEXT ACTIONS
        if ($granularity->getCellsWithSocialContextActions() === true) {
            $contextActionsTab = new UI_Tab('contextActions');
            if ($tab === 'contextActions') {
                $contextActionsTab->active = true;
            }
            $contextActionsTab->label = __('Social', 'name', 'actions');
            $contextActionsTab->dataSource = 'orga/tab_celldetails/contextactions?idCell='.$idCell;
            $this->view->tabView->addTab($contextActionsTab);
        }


        // TAB DOCUMENTS
        $isUserAllowedToInputCell = $aclService->isAllowed(
            $connectedUser,
            Orga_Action_Cell::INPUT(),
            User_Model_Resource_Entity::loadByEntity($cell)
        );
        if (($isUserAllowedToInputCell)
            && (($granularity->getCellsWithSocialContextActions() === true)
                || ($granularity->getCellsWithSocialGenericActions() === true)
                || ($granularity->getCellsWithInputDocuments() === true)
            )
        ) {
            $documentsTab = new UI_Tab('documents');
            if ($tab === 'documents') {
                $documentsTab->active = true;
            }
            $documentsTab->label = __('Doc', 'name', 'documents');
            $documentsTab->dataSource = 'orga/tab_celldetails/documents?idCell='.$idCell;
            $this->view->tabView->addTab($documentsTab);
        }


        // TAB ADMINISTRATION
        if ($isUserAllowedToEditProject) {
            $administrationTab = new UI_Tab('administration');
            if ($tab === 'administration') {
                $administrationTab->active = true;
            }
            $administrationTab->label = __('DW', 'rebuild', 'dataRebuildTab');
            $administrationTab->dataSource = 'orga/tab_celldetails/administration?idCell='.$idCell;
            $this->view->tabView->addTab($administrationTab);
        }
    }

    /**
     * Action pour les cellules enfants.
     * @Secure("viewCell")
     */
    public function childAction()
    {
        $this->view->idCell = $this->getParam('idCell');
        $cell = Orga_Model_Cell::load($this->getParam('idCell'));
        $this->view->granularities = $cell->getGranularity()->getNarrowerGranularities();

        if (($this->hasParam('minimize')) && ($this->getParam('minimize') === false)) {
            $this->view->minimize = false;
        } else {
            $this->view->minimize = true;
        }

        if ($this->hasParam('datagridConfiguration')) {
            $datagridConfiguration = $this->getParam('datagridConfiguration');
            if (is_array($datagridConfiguration)) {
                $this->view->listDatagrids = $datagridConfiguration;
            } else {
                $this->view->listDatagrids = array($datagridConfiguration);
            }
        } else {
            $this->view->listDatagrids = array();
            foreach ($cell->getGranularity()->getNarrowerGranularities() as $narrowerGranularity) {
                $datagridConfiguration = new Orga_DatagridConfiguration(
                    'child_c'.$cell->getId().'_g'.$narrowerGranularity->getId(),
                    'datagrid_cell_childs',
                    'orga',
                    $cell,
                    $narrowerGranularity
                );
                $datagridConfiguration->datagrid->addParam('idCell', $cell->getId());
                if ($narrowerGranularity->isNavigable()) {
                    $columnLink = new UI_Datagrid_Col_Link('link');
                    $columnLink->label = __('UI', 'name', 'browsing');
                    $datagridConfiguration->datagrid->addCol($columnLink);
                }
                $this->view->listDatagrids[$narrowerGranularity->getLabel()] = $datagridConfiguration;
            }
        }

        if ($this->hasParam('display') && ($this->getParam('display') === 'render')) {
            $this->_helper->layout()->disableLayout();
            $this->view->display = false;
        } else {
            $this->view->display = true;
        }
    }

    /**
     * Action pour la pertinence des cellules enfants.
     * @Secure("viewProject")
     */
    public function relevantAction()
    {
        $cell = Orga_Model_Cell::load($this->getParam('idCell'));
        $this->view->granularities = $cell->getGranularity()->getNarrowerGranularities();

        $listDatagridConfiguration = array();
        foreach ($cell->getGranularity()->getNarrowerGranularities() as $narrowerGranularity) {
            $datagridConfiguration = new Orga_DatagridConfiguration(
                'relevant_c'.$cell->getId().'_g'.$narrowerGranularity->getId(),
                'datagrid_cell_relevant',
                'orga',
                $cell,
                $narrowerGranularity
            );
            $datagridConfiguration->datagrid->addParam('idCell', $cell->getId());
            $columnRelevant = new UI_Datagrid_Col_Bool('relevant');
            $columnRelevant->label = __('Orga', 'name', 'relevance');
            $columnRelevant->editable = true;
            $columnRelevant->textTrue = __('Orga', 'property', 'relevantFem');
            $columnRelevant->textFalse = __('Orga', 'property', 'irrelevantFem');
            $columnRelevant->valueTrue = '<i class="icon-ok"></i> '.__('Orga', 'property', 'relevantFem');
            $columnRelevant->valueFalse = '<i class="icon-remove"></i> '.__('Orga', 'property', 'irrelevantFem');
            $datagridConfiguration->datagrid->addCol($columnRelevant);

            $columnAllParentsRelevant = new UI_Datagrid_Col_Bool('allParentsRelevant');
            $columnAllParentsRelevant->label = __('Orga', 'relevance', 'parentCellsRelevanceHeader');
            $columnAllParentsRelevant->editable = false;
            $columnAllParentsRelevant->valueTrue = '<i class="icon-ok"></i> '.__('Orga', 'relevance', 'allParentCellsRelevantProperty');
            $columnAllParentsRelevant->valueFalse = '<i class="icon-remove"></i> '.__('Orga', 'relevance', 'notAllParentCellsRelevantProperty');
            $datagridConfiguration->datagrid->addCol($columnAllParentsRelevant);
            $listDatagridConfiguration[$narrowerGranularity->getLabel()] = $datagridConfiguration;
        }

        $this->forward('child', 'cell', 'orga', array('datagridConfiguration' => $listDatagridConfiguration));
    }

    /**
     * Action redirigeant vers AF.
     * @Secure("viewCell")
     */
    public function inputAction()
    {
        $idCell = $this->getParam('idCell');
        $cell = Orga_Model_Cell::load($idCell);
        $aFGranularities = Orga_Model_AFGranularities::loadByAFInputOrgaGranularity($cell->getGranularity());
        $cellsGroupDataProvider = $aFGranularities->getCellsGroupDataProviderForContainerCell(
            $cell->getParentCellForGranularity($aFGranularities->getAFConfigOrgaGranularity())
        );

        $isUserAllowedToInputCell = User_Service_ACL::getInstance()->isAllowed(
            $this->_helper->auth(),
            Orga_Action_Cell::INPUT(),
            $cell
        );

        $aFViewConfiguration = new AF_ViewConfiguration();
        if ($isUserAllowedToInputCell) {
            $aFViewConfiguration->setMode(AF_ViewConfiguration::MODE_WRITE);
        } else {
            $aFViewConfiguration->setMode(AF_ViewConfiguration::MODE_READ);
        }
        $aFViewConfiguration->setPageTitle(__('UI', 'name', 'input').' <small>'.$cell->getLabel().'</small>');
        $aFViewConfiguration->addToActionStack('inputsave', 'cell', 'orga', array('idCell' => $idCell));
        $aFViewConfiguration->setExitUrl('orga/cell/details?idCell='.$this->getParam('fromIdCell'));
        $aFViewConfiguration->addUrlParam('idCell', $idCell);
        $aFViewConfiguration->setDisplayConfigurationLink(false);
        $aFViewConfiguration->addBaseTabs();
        try {
            $aFViewConfiguration->setIdInputSet($cell->getAFInputSetPrimary()->getId());
        } catch (Core_Exception_UndefinedAttribute $e) {
            // Pas d'inputSetPrimary : nouvelle saisie.
        }

        $tabComments = new UI_Tab('inputComments');
        $tabComments->label = __('Social', 'name', 'comments');
        $tabComments->dataSource = 'orga/tab_input/comments/idCell/'.$idCell;
        $tabComments->cacheData = true;
        $aFViewConfiguration->addTab($tabComments);

        $tabDocs = new UI_Tab('inputDocs');
        $tabDocs->label = __('Doc', 'name', 'documents');
        $tabDocs->dataSource = 'orga/tab_input/docs/idCell/'.$idCell;
        $tabDocs->cacheData = true;
        $aFViewConfiguration->addTab($tabDocs);

        $this->forward('display', 'af', 'af', array(
                'id' => $cellsGroupDataProvider->getAF()->getId(),
                'viewConfiguration' => $aFViewConfiguration
            ));
    }

    /**
     * Fonction de sauvegarde de l'AF.
     * @Secure("inputCell")
     */
    public function inputsaveAction()
    {
        $cell = Orga_Model_Cell::load($this->getParam('idCell'));
        $inputSet = $this->getParam('inputSet');

        $cell->setAFInputSetPrimary($inputSet);

        if ($inputSet->isInputComplete()) {
            Orga_Service_ETLData::getInstance()->clearDWResultsFromCell($cell);
            Orga_Service_ETLData::getInstance()->populateDWResultsFromCell($cell);
        }

        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     * Réinitialise le DW du Cell donné et ceux des cellules enfants.
     * @Secure("editCell")
     */
    public function resetdwsAction()
    {
        /** @var Core_Work_Dispatcher $workDispatcher */
        $workDispatcher = Zend_Registry::get('workDispatcher');

        $cell = Orga_Model_Cell::load($this->getParam('idCell'));

        try {
            // Lance la tache en arrière plan
            $workDispatcher->runBackground(
                new Core_Work_ServiceCall_Task(
                    'Orga_Service_ETLStructure',
                    'resetCellAndChildrenDWCubes',
                    [$cell]
                )
            );
        } catch (Core_Exception_NotFound $e) {
            throw new Core_Exception_User('DW', 'rebuild', 'analysisDataRebuildFailMessage');
        }
        $this->sendJsonResponse(array('message' => __('UI', 'message', 'operationInProgress')));
    }

    /**
     * Re-calcule l'input du Cell donné et ceux des cellules enfants.
     * @Secure("editCell")
     */
    public function calculateinputsAction()
    {
        /** @var Core_Work_Dispatcher $workDispatcher */
        $workDispatcher = Zend_Registry::get('workDispatcher');

        $cell = Orga_Model_Cell::load($this->getParam('idCell'));

        try {
            // Lance la tache en arrière plan
            $workDispatcher->runBackground(
                new Core_Work_ServiceCall_Task(
                    'Orga_Service_ETLStructure',
                    'resetCellAndChildrenCalculationsAndDWCubes',
                    [$cell]
                )
            );
        } catch (Core_Exception_NotFound $e) {
            throw new Core_Exception_User('DW', 'rebuild', 'outputDataRebuildFailMessage');
        }
        $this->sendJsonResponse(array('message' => __('UI', 'message', 'operationInProgress')));
    }

    /**
     * Action fournissant le détails d'une action générique.
     * @Secure("problemToSolve")
     */
    public function genericactiondetailsAction()
    {
        $idCell = $this->getParam('idCell');
        $this->view->idCell = $idCell;
        $cell = Orga_Model_Cell::load($idCell);
        $this->view->documentLibrary = $cell->getDocLibraryForSocialGenericAction();

        $this->forward('generic-action-details', 'action', 'social', array(
                'title' => __('Social', 'actionTemplate', 'actionTemplateDetails').
                ' <small>'.$cell->getLabel().'</small>',
                'returnUrl' => 'orga/cell/details/idCell/'.$idCell.'/tab/genericActions',
            ));
    }

    /**
     * Action fournissant le détails d'une action générique.
     * @Secure("problemToSolve")
     */
    public function contextactiondetailsAction()
    {
        $idCell = $this->getParam('idCell');
        $this->view->idCell = $idCell;
        $cell = Orga_Model_Cell::load($idCell);
        $this->view->documentLibrary = $cell->getDocLibraryForSocialContextAction();

        $this->forward('context-action-details', 'action', 'social', array(
                'title' => __('Social', 'action', 'actionDetails').
                ' <small>'.$cell->getLabel().'</small>',
                'returnUrl' => 'orga/cell/details/idCell/'.$idCell.'/tab/contextActions',
            ));
    }

    /**
     * Action fournissant un export spécifique.
     * @Secure("viewCell")
     */
    public function specificexportAction()
    {
        $idCell = $this->getParam('idCell');
        $cell = Orga_Model_Cell::load($idCell);

        if (!($this->hasParam('display') && ($this->getParam('display') == true))) {
            $exportUrl = 'orga/cell/specificexport/'.
                'idCell/'.$idCell.'/export/'.$this->getParam('export').'/display/true';
        } else {
            $exportUrl = null;
        }

        $specificReportsDirectoryPath = PACKAGE_PATH.'/data/specificExports/'.
            $cell->getProject()->getId().'/'.
            str_replace('|', '_', $cell->getGranularity()->getRef()).'/';
        $specificReports = new DW_Export_Specific_Pdf(
            $specificReportsDirectoryPath.$this->getParam('export').'.xml',
            $cell->getDWCube(),
            $exportUrl
        );

        if ($exportUrl !== null) {
            $this->view->html = $specificReports->html;
        } else {
            Zend_Layout::getMvcInstance()->disableLayout();
            $specificReports->display();
        }
    }

}