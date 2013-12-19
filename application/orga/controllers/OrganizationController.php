<?php

use Core\Annotation\Secure;
use Core\Work\ServiceCall\ServiceCallTask;
use Doctrine\Common\Collections\Criteria;
use MyCLabs\Work\Dispatcher\WorkDispatcher;
use Orga\ViewModel\OrganizationViewModelFactory;
use Orga\ViewModel\CellViewModelFactory;
use User\Domain\ACL\Action;
use User\Domain\ACL\ACLService;
use User\Domain\ACL\Resource\NamedResource;
use User\Domain\User;

/**
 * @author valentin.claras
 */
class Orga_OrganizationController extends Core_Controller
{
    use UI_Controller_Helper_Form;

    /**
     * @Inject
     * @var ACLService
     */
    private $aclService;

    /**
     * @Inject
     * @var Orga_Service_ACLManager
     */
    private $aclManager;

    /**
     * @Inject
     * @var OrganizationViewModelFactory
     */
    private $organizationVMFactory;

    /**
     * @Inject
     * @var CellViewModelFactory
     */
    private $cellVMFactory;

    /**
     * @Inject
     * @var Orga_Service_ETLStructure
     */
    private $etlStructureService;

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
     * @Secure("loggedIn")
     */
    public function indexAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $organizationResource = NamedResource::loadByName(Orga_Model_Organization::class);
        $isConnectedUserAbleToCreateOrganizations = $this->aclService->isAllowed(
            $connectedUser,
            Action::CREATE(),
            $organizationResource
        );

        if (!$isConnectedUserAbleToCreateOrganizations) {
            $aclQuery = new Core_Model_Query();
            $aclQuery->aclFilter->enabled = true;
            $aclQuery->aclFilter->user = $connectedUser;
            $aclQuery->aclFilter->action = Action::DELETE();
            $isConnectedUserAbleToDeleteOrganizations = (Orga_Model_Organization::countTotal($aclQuery) > 0);
            if (!$isConnectedUserAbleToDeleteOrganizations) {
                $aclQuery->aclFilter->action = Action::VIEW();
                $isConnectedUserAbleToSeeManyOrganizations = (Orga_Model_Organization::countTotal($aclQuery) > 1);
            }
        }

        if (($isConnectedUserAbleToCreateOrganizations)
            || ($isConnectedUserAbleToDeleteOrganizations)
            || ($isConnectedUserAbleToSeeManyOrganizations)
        ) {
            $this->redirect('orga/organization/manage');
        } else {
            $organizationsWithAccess = Orga_Model_Organization::loadList($aclQuery);
            if (count($organizationsWithAccess) === 1) {
                $idOrganization = array_pop($organizationsWithAccess)->getId();
                $this->redirect('orga/organization/view/idOrganization/'.$idOrganization);
            }
        }

        $this->forward('noaccess', 'organization', 'orga');
    }

    /**
     * @Secure("viewOrganizations")
     */
    public function manageAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        // Retrouve la liste des organisations
        $query = new Core_Model_Query();
        $query->aclFilter->enabled = true;
        $query->aclFilter->user = $connectedUser;
        $query->aclFilter->action = Action::VIEW();
        $organizations = Orga_Model_Organization::loadList($query);

        // Crée les ViewModel
        $organizationsViewModel = [];
        foreach ($organizations as $organization) {
            $organizationsViewModel[] = $this->organizationVMFactory->createOrganizationViewModel($organization, $connectedUser);
        }
        $this->view->assign('organizations', $organizationsViewModel);

        $organizationResource = NamedResource::loadByName(Orga_Model_Organization::class);
        $this->view->assign('canCreateOrganization', $this->aclService->isAllowed(
            $connectedUser,
            Action::CREATE(),
            $organizationResource
        ));
    }

    /**
     * @Secure("createOrganization")
     */
    public function addAction()
    {
        UI_Form::addHeader();
    }

    /**
     * @Secure("createOrganization")
     */
    public function addSubmitAction()
    {
        $user = $this->_helper->auth();
        $formData = json_decode($this->getRequest()->getParam('addOrganization'), true);
        $label = $formData['organization']['elements']['organizationLabel']['value'];

        $success = function () {
            $this->sendJsonResponse(
                [
                    'message' => __('UI', 'message', 'added'),
                    'typeMessage' => 'success',
                    'info' => __('Orga', 'add', 'complementaryInfo')
                ]
            );
        };
        $timeout = function () {
            $this->sendJsonResponse(
                [
                    [
                        'message' => __('UI', 'message', 'addedLater'),
                        'typeMessage' => 'info',
                        'info' => __('Orga', 'add', 'addOrganizationComplementaryInfo')
                    ]
                ]
            );
        };
        $error = function (Exception $e) {
            throw $e;
        };

        // Lance la tache en arrière plan
        $task = new ServiceCallTask(
            'Orga_Service_OrganizationService',
            'createOrganization',
            [$user, $formData],
            __('Orga', 'backgroundTasks', 'createOrganization', ['LABEL' => $label])
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);
    }

    /**
     * @Secure("deleteOrganization")
     */
    public function deleteAction()
    {
        $organization = Orga_Model_Organization::load($this->_getParam('idOrganization'));

        $success = function () {
            UI_Message::addMessageStatic(__('UI', 'message', 'deleted'), UI_Message::TYPE_SUCCESS);
        };
        $timeout = function () {
            UI_Message::addMessageStatic(__('UI', 'message', 'deletedLater'), UI_Message::TYPE_INFO);
        };
        $error = function (Exception $e) {
            throw $e;
        };

        // Lance la tache en arrière plan
        $task = new ServiceCallTask(
            'Orga_Service_OrganizationService',
            'deleteOrganization',
            [$organization]
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);

        $this->redirect('orga/organization/manage');
    }

    /**
     * @Secure("viewOrganization")
     */
    public function viewAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $cellsWithAccess = $this->aclManager->getTopCellsWithAccessForOrganization($connectedUser, $organization);
        if (count($cellsWithAccess['cells']) === 1) {
            $this->redirect('orga/cell/view/idCell/'.array_pop($cellsWithAccess['cells'])->getId());
        }
        
        $organizationViewModel = $this->organizationVMFactory->createOrganizationViewModel($organization, $connectedUser);
        $this->view->assign('organization', $organizationViewModel);
        $cellViewModels = [];
        foreach ($cellsWithAccess['cells'] as $cellWithAccess) {
            $cellViewModels[] = $this->cellVMFactory->createCellViewModel($cellWithAccess, $connectedUser);
        }
        $this->view->assign('cells', $cellViewModels);
        $this->view->assign('cellsAccess', $cellsWithAccess['accesses']);
    }

    /**
     * @Secure("editOrganizationAndCells")
     */
    public function editAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $this->view->assign(
            'organization',
            $this->organizationVMFactory->createOrganizationViewModel($organization, $connectedUser)
        );
        $isUserAllowedToEditOrganization = $this->aclService->isAllowed(
            $connectedUser,
            Action::EDIT(),
            $organization
        );
        if (!$isUserAllowedToEditOrganization) {
            $numberCellsUserCanEdit = 0;
            foreach ($organization->getOrderedGranularities() as $granularity) {
                $aclCellQuery = new Core_Model_Query();
                $aclCellQuery->aclFilter->enabled = true;
                $aclCellQuery->aclFilter->user = $connectedUser;
                $aclCellQuery->aclFilter->action = Action::EDIT();
                $aclCellQuery->filter->addCondition(Orga_Model_Cell::QUERY_GRANULARITY, $granularity);

                $numberCellsUserCanEdit = Orga_Model_Cell::countTotal($aclCellQuery);
                if ($numberCellsUserCanEdit > 0) {
                    break;
                }
            }
            $isUserAllowedToEditCells = ($numberCellsUserCanEdit > 0);
        } else {
            $isUserAllowedToEditCells = true;
        }

        $tabView = new UI_Tab_View('orga');
        $parameters = '/idOrganization/'.$idOrganization.'/display/render/';

        // Tab Organization & Axes.
        if ($isUserAllowedToEditOrganization) {
            $organizationTab = new UI_Tab('organization');
            $organizationTab->label = __('Orga', 'configuration', 'generalInfoTab');
            $organizationTab->dataSource = 'orga/organization/edit-organization'.$parameters;
            $organizationTab->useCache = false;
            $tabView->addTab($organizationTab);

            $axisTab = new UI_Tab('axes');
            $axisTab->label = __('UI', 'name', 'axes');
            $axisTab->dataSource = 'orga/axis/manage'.$parameters;
            $axisTab->useCache = true;
            $tabView->addTab($axisTab);
        }

        // Tab Members.
        if ($isUserAllowedToEditCells) {
            $membersTab = new UI_Tab('members');
            $membersTab->label = __('UI', 'name', 'elements');
            $membersTab->dataSource = 'orga/member/manage'.$parameters;
            $membersTab->useCache = false;
            $tabView->addTab($membersTab);
        }

        // Tab Granularities.
        if ($isUserAllowedToEditOrganization) {
            $granularityTab = new UI_Tab('granularities');
            $granularityTab->label = __('Orga', 'granularity', 'granularities');
            $granularityTab->dataSource = 'orga/granularity/manage'.$parameters;
            $granularityTab->useCache = false;
            $tabView->addTab($granularityTab);
        }

        // Tab Relevant.
        if ($isUserAllowedToEditCells) {
            $relevanceTab = new UI_Tab('relevance');
            $relevanceTab->label = __('Orga', 'cellRelevance', 'relevance');
            $relevanceTab->dataSource = 'orga/organization/edit-relevance'.$parameters;
            $relevanceTab->useCache = false;
            $tabView->addTab($relevanceTab);
        }

        // Tab AFConfiguration.
        if ($isUserAllowedToEditCells) {
            $afTab = new UI_Tab('afs');
            $afTab->label = __('UI', 'name', 'forms');
            $afTab->dataSource = 'orga/organization/edit-afs'.$parameters;
            $afTab->useCache = !$isUserAllowedToEditOrganization;
            $tabView->addTab($afTab);
        }

        // Tab DW
        if ($isUserAllowedToEditCells) {
            $dwTab = new UI_Tab('reports');
            $dwTab->label = __('DW', 'name', 'analyses');
            $dwTab->dataSource = 'orga/organization/edit-reports'.$parameters;
            $dwTab->useCache = !$isUserAllowedToEditOrganization;
            $tabView->addTab($dwTab);
        }

        // Tab Consistency.
        if ($isUserAllowedToEditOrganization) {
            $consistencyTab = new UI_Tab('consistency');
            $consistencyTab->label = __('UI', 'name', 'control');
            $consistencyTab->dataSource = 'orga/organization/consistency'.$parameters;
            $consistencyTab->useCache = true;
            $tabView->addTab($consistencyTab);
        }

        $activeTab = $this->hasParam('tab') ? $this->getParam('tab') : 'organization';
        $editOrganizationTabs = ['organization', 'axes', 'granularities', 'consistency'];
        if (!$isUserAllowedToEditOrganization && in_array($activeTab, $editOrganizationTabs)) {
            $activeTab = 'members';
        }
        switch ($activeTab) {
            case 'organization':
                $organizationTab->active = true;
                break;
            case 'axes':
                $axisTab->active = true;
                break;
            case 'members':
                $membersTab->active = true;
                break;
            case 'granularities':
                $granularityTab->active = true;
                break;
            case 'relevance':
                $relevanceTab->active = true;
                break;
            case 'afs':
                $afTab->active = true;
                break;
            case 'reports':
                $dwTab->active = true;
                break;
            case 'consistency':
                $consistencyTab->active = true;
                break;
        }

        $this->view->assign('tabView', $tabView);
        UI_Datagrid::addHeader();
        UI_Tree::addHeader();
        UI_Form::addHeader();
        UI_Popup_Ajax::addHeader();
    }

    /**
     * @Secure("editOrganization")
     */
    public function editOrganizationAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $this->view->assign('idOrganization', $idOrganization);
        $this->view->assign('organizationLabel', $organization->getLabel());
        $this->view->assign('granularities', $organization->getOrderedGranularities());
        try {
            $this->view->granularityRefForInventoryStatus = $organization->getGranularityForInventoryStatus()->getRef();
        } catch (Core_Exception_UndefinedAttribute $e) {
            $this->view->granularityRefForInventoryStatus = null;
        }
        $this->view->granularitiesWithDWCube = array();
        foreach ($organization->getOrderedGranularities() as $granularity) {
            if ($granularity->getCellsGenerateDWCubes()) {
                $this->view->granularitiesWithDWCube[] = $granularity;
            }
        }

        if ($this->hasParam('display') && ($this->getParam('display') === 'render')) {
            $this->_helper->layout()->disableLayout();
            $this->view->assign('display', false);
        } else {
            $this->view->assign('display', true);
        }
        $this->view->granularityReportBaseUrl = 'orga/granularity/report/idOrganization/'.$idOrganization;
    }

    /**
     * @Secure("editOrganization")
     */
    public function editOrganizationSubmitAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);
        $formData = $this->getFormData('organizationDetails');

        $refGranularityForInventoryStatus = $formData->getValue('granularityForInventoryStatus');
        if (!empty($refGranularityForInventoryStatus)) {
            $granularityForInventoryStatus = $organization->getGranularityByRef($refGranularityForInventoryStatus);
            try {
                $organization->setGranularityForInventoryStatus($granularityForInventoryStatus);
            } catch (Core_Exception_InvalidArgument $e) {
                $this->addFormError(
                    'granularityForInventoryStatus',
                    __('Orga', 'exception', 'broaderInputGranularity')
                );
            }
        }

        $label = (string) $formData->getValue('label');
        if ($organization->getLabel() !== $label) {
            $organization->setLabel($label);
        }

        $this->setFormMessage(__('UI', 'message', 'updated'));

        $this->sendFormResponse();
    }

    /**
     * @param array|Orga_Model_Granularity[] $granularities
     * @param User $user
     *
     * @return Orga_Model_Granularity[]
     */
    protected function getGranularitiesUserCanEdit(array $granularities, User $user)
    {
        /** @var Orga_Model_Granularity[] $granularitiesCanEdit */
        $granularitiesCanEdit = [];

        foreach ($granularities as $granularity) {
            $aclCellQuery = new Core_Model_Query();
            $aclCellQuery->aclFilter->enabled = true;
            $aclCellQuery->aclFilter->user = $user;
            $aclCellQuery->aclFilter->action = Action::EDIT();
            $aclCellQuery->filter->addCondition(Orga_Model_Cell::QUERY_GRANULARITY, $granularity);

            $numberCellsUserCanEdit = Orga_Model_Cell::countTotal($aclCellQuery);
            if ($numberCellsUserCanEdit > 0) {
                $granularitiesCanEdit[] = $granularity;
            }
        }

        return $granularitiesCanEdit;
    }

    /**
     * @param Orga_Model_Organization $organization
     *
     * @throws Core_Exception_User
     *
     * @return Orga_Model_Axis
     */
    protected function getAxesAddGranularity(Orga_Model_Organization $organization)
    {
        $refAxes = explode(',', $this->getParam('axes'));

        /** @var Orga_Model_Axis $axes */
        $axes = [];
        if (!empty($this->getParam('axes'))) {
            foreach ($refAxes as $refAxis) {
                $axis = $organization->getAxisByRef($refAxis);
                // On regarde si les axes précédement ajouter ne sont pas lié hierachiquement à l'axe actuel.
                if (!$axis->isTransverse($axes)) {
                    throw new Core_Exception_User('Orga', 'granularity', 'hierarchicallyLinkedAxes');
                    break;
                } else {
                    $axes[] = $axis;
                }
            }
        }

        return $axes;
    }

    /**
     * @Secure("editOrganizationAndCells")
     */
    public function editRelevanceAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $this->view->assign('organization', $organization);

        $criteriaRelevance = new Criteria();
        $criteriaRelevance->where($criteriaRelevance->expr()->eq('cellsControlRelevance', true));
        $relevanceGranularities = $organization->getOrderedGranularities()->matching($criteriaRelevance)->toArray();

        $isUserAllowedToEditOrganization = $this->aclService->isAllowed(
            $connectedUser,
            Action::EDIT(),
            $organization
        );
        $this->view->assign('isUserAllowedToEditOrganization', $isUserAllowedToEditOrganization);

        if (!$isUserAllowedToEditOrganization) {
            $relevanceGranularities = $this->getGranularitiesUserCanEdit($relevanceGranularities, $connectedUser);
        }
        $this->view->assign('granularities', $relevanceGranularities);

        if ($this->hasParam('display') && ($this->getParam('display') === 'render')) {
            $this->_helper->layout()->disableLayout();
            $this->view->assign('display', false);
        } else {
            $this->view->assign('display', true);
        }
    }

    /**
     * @Secure("editOrganization")
     */
    public function addGranularityRelevanceAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $axes = $this->getAxesAddGranularity($organization);

        try {
            $granularity = $organization->getGranularityByRef(Orga_Model_Granularity::buildRefFromAxes($axes));
            if ($granularity->getCellsControlRelevance()) {
                throw new Core_Exception_User('Orga', 'edit', 'granularityAlreadyConfigured');
            }
            $granularity->setCellsControlRelevance(true);
            $this->sendJsonResponse(['message' => __('UI', 'message', 'added')]);
        } catch (Core_Exception_NotFound $e) {
            $success = function () {
                $this->sendJsonResponse(['message' => __('UI', 'message', 'added')]);
            };
            $timeout = function () {
                $this->sendJsonResponse(['message' => __('UI', 'message', 'addedLater')]);
            };
            $error = function (Exception $e) {
                throw $e;
            };

            // Lance la tache en arrière plan
            $task = new ServiceCallTask(
                'Orga_Service_OrganizationService',
                'addGranularity',
                [
                    $organization,
                    $axes,
                    ['relevance' => true]
                ],
                __('Orga', 'backgroundTasks', 'addGranularity', ['LABEL' => implode(', ', $axes)])
            );
            $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);
        }
    }

    /**
     * @Secure("editOrganizationAndCells")
     */
    public function editAfsAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $this->view->assign('organization', $organization);

        $inputGranularities = $organization->getInputGranularities();

        $isUserAllowedToEditOrganization = $this->aclService->isAllowed(
            $connectedUser,
            Action::EDIT(),
            $organization
        );
        $this->view->assign('isUserAllowedToEditOrganization', $isUserAllowedToEditOrganization);

        if (!$isUserAllowedToEditOrganization) {
            /** @var Orga_Model_Granularity[] $inputConfigGranularities */
            $configGranularities = [];
            foreach ($inputGranularities as $inputGranularity) {
                $configGranularities[] = $inputGranularity->getInputConfigGranularity();
            }
            $configGranularities = $this->getGranularitiesUserCanEdit($configGranularities, $connectedUser);

            $inputGranularities = [];
            foreach ($organization->getInputGranularities() as $inputGranularity) {
                if (in_array($inputGranularity->getInputConfigGranularity(), $configGranularities)) {
                    $inputGranularities[] = $inputGranularity;
                }
            }
        }
        $this->view->assign('granularities', $inputGranularities);

        $afs = [];
        /** @var AF_Model_AF $af */
        foreach (AF_Model_AF::loadList() as $af) {
            $afs[$af->getRef()] = $af->getLabel();
        }
        $this->view->assign('afs', $afs);

        if ($this->hasParam('display') && ($this->getParam('display') === 'render')) {
            $this->_helper->layout()->disableLayout();
            $this->view->assign('display', false);
        } else {
            $this->view->assign('display', true);
        }
    }

    /**
     * @Secure("editOrganization")
     */
    public function addGranularityAfsAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $inputAxes = $this->getAxesAddGranularity($organization);

        $refConfigAxes = explode(',', $this->getParam('inputConfigAxes'));
        /** @var Orga_Model_Axis[] $configAxes */
        $configAxes = [];
        if (!empty($this->getParam('inputConfigAxes')))
            foreach ($refConfigAxes as $refConfigAxis) {
            $configAxis = $organization->getAxisByRef($refConfigAxis);
            // On regarde si les axes précédement ajouter ne sont pas lié hierachiquement à l'axe actuel.
            if (!$configAxis->isTransverse($configAxes)) {
                throw new Core_Exception_User('Orga', 'granularity', 'hierarchicallyLinkedAxes');
                break;
            } else {
                $configAxes[] = $configAxis;
            }
        }

        foreach ($configAxes as $configAxis) {
            foreach ($inputAxes as $inputAxis) {
                if ($inputAxis->isBroaderThan($configAxis)) {
                    throw new Core_Exception_User('Orga', 'configuration', 'inputGranularityNeedsToBeNarrowerThanFormChoiceGranularity');
                }
            }
            if ($configAxis->isTransverse($inputAxes)) {
                throw new Core_Exception_User('Orga', 'configuration', 'inputGranularityNeedsToBeNarrowerThanFormChoiceGranularity');
            }
        }

        try {
            $inputGranularity = $organization->getGranularityByRef(Orga_Model_Granularity::buildRefFromAxes($inputAxes));

            $configGranularity = $organization->getGranularityByRef(Orga_Model_Granularity::buildRefFromAxes($configAxes));
            $inputGranularity->setInputConfigGranularity($configGranularity);
            $this->sendJsonResponse(['message' => __('UI', 'message', 'added')]);
        } catch (Core_Exception_NotFound $e) {
            $success = function () {
                $this->sendJsonResponse(['message' => __('UI', 'message', 'added')]);
            };
            $timeout = function () {
                $this->sendJsonResponse(['message' => __('UI', 'message', 'addedLater')]);
            };
            $error = function (Exception $e) {
                throw $e;
            };

            // Lance la tache en arrière plan
            $task = new ServiceCallTask(
                'Orga_Service_OrganizationService',
                'addGranularity',
                [
                    $organization,
                    $inputAxes,
                    ['afs' => $configAxes]
                ],
                __('Orga', 'backgroundTasks', 'addGranularity', ['LABEL' => implode(', ', $inputAxes)])
            );
            $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);
        }
    }

    /**
     * @Secure("editOrganizationAndCells")
     */
    public function editReportsAction()
    {
        /** @var User $connectedUser */
        $connectedUser = $this->_helper->auth();

        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $this->view->assign('organization', $organization);

        $criteriaReports = new Criteria();
        $criteriaReports->where($criteriaReports->expr()->eq('cellsGenerateDWCubes', true));
        $reportsGranularities = $organization->getOrderedGranularities()->matching($criteriaReports)->toArray();

        $isUserAllowedToEditOrganization = $this->aclService->isAllowed(
            $connectedUser,
            Action::EDIT(),
            $organization
        );
        $this->view->assign('isUserAllowedToEditOrganization', $isUserAllowedToEditOrganization);

        if (!$isUserAllowedToEditOrganization) {
            $reportsGranularities = $this->getGranularitiesUserCanEdit($reportsGranularities, $connectedUser);
        }
        $this->view->assign('granularities', $reportsGranularities);

        if ($this->hasParam('display') && ($this->getParam('display') === 'render')) {
            $this->_helper->layout()->disableLayout();
            $this->view->assign('display', false);
        } else {
            $this->view->assign('display', true);
        }
    }

    /**
     * @Secure("editOrganization")
     */
    public function addGranularityReportsAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $axes = $this->getAxesAddGranularity($organization);

        try {
            $granularity = $organization->getGranularityByRef(Orga_Model_Granularity::buildRefFromAxes($axes));
            if ($granularity->getCellsGenerateDWCubes()) {
                throw new Core_Exception_User('Orga', 'edit', 'granularityAlreadyConfigured');
            }
            $granularity->setCellsGenerateDWCubes(true);
            $this->sendJsonResponse(['message' => __('UI', 'message', 'added')]);
        } catch (Core_Exception_NotFound $e) {
            $success = function () {
                $this->sendJsonResponse(['message' => __('UI', 'message', 'added')]);
            };
            $timeout = function () {
                $this->sendJsonResponse(['message' => __('UI', 'message', 'addedLater')]);
            };
            $error = function (Exception $e) {
                throw $e;
            };

            // Lance la tache en arrière plan
            $task = new ServiceCallTask(
                'Orga_Service_OrganizationService',
                'addGranularity',
                [
                    $organization,
                    $axes,
                    ['reports' => true]
                ],
                __('Orga', 'backgroundTasks', 'addGranularity', ['LABEL' => implode(', ', $axes)])
            );
            $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);
        }
    }

    /**
     * @Secure("editOrganizationAndCells")
     */
    public function dwStateAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        set_time_limit(0);
        $this->sendJsonResponse(
            [
                'organizationDWCubesState' => $this->etlStructureService->areOrganizationDWCubesUpToDate($organization)
            ]
        );
    }

    /**
     * @Secure("editOrganizationAndCells")
     */
    public function dwResetAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $success = function () {
            $this->sendJsonResponse(__('DW', 'rebuild', 'analysisDataRebuildConfirmationMessage'));
        };
        $timeout = function () {
            $this->sendJsonResponse(__('UI', 'message', 'operationInProgress'));
        };
        $error = function () {
            throw new Core_Exception_User('DW', 'rebuild', 'analysisDataRebuildFailMessage');
        };

        // Lance la tache en arrière plan
        $task = new ServiceCallTask(
            'Orga_Service_ETLStructure',
            'resetOrganizationDWCubes',
            [$organization],
            __('Orga', 'backgroundTasks', 'resetDWOrga', ['LABEL' => $organization->getLabel()])
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);
    }

    /**
     * @Secure("editOrganization")
     */
    public function inputsCalculateAction()
    {
        $idOrganization = $this->getParam('idOrganization');
        /** @var Orga_Model_Organization $organization */
        $organization = Orga_Model_Organization::load($idOrganization);

        $success = function () {
            $this->sendJsonResponse(['message' => __('DW', 'rebuild', 'outputDataRebuildConfirmationMessage')]);
        };
        $timeout = function () {
            $this->sendJsonResponse(['message' => __('UI', 'message', 'operationInProgress')]);
        };
        $error = function () {
            throw new Core_Exception_User('DW', 'rebuild', 'outputDataRebuildFailMessage');
        };

        // Lance la tache en arrière plan
        $task = new ServiceCallTask(
            'Orga_Service_ETLStructure',
            'resetCellAndChildrenCalculationsAndDWCubes',
            [$organization->getGranularityByRef('global')->getCellByMembers([])],
            __('Orga', 'backgroundTasks', 'resetDWCellAndResults', ['LABEL' => $organization->getLabel()])
        );
        $this->workDispatcher->runBackground($task, $this->waitDelay, $success, $timeout, $error);
    }

    /**
     * @Secure("editOrganization")
     */
    public function consistencyAction()
    {
        $this->view->assign('idOrganization', $this->getParam('idOrganization'));

        if ($this->hasParam('display') && ($this->getParam('display') === 'render')) {
            $this->_helper->layout()->disableLayout();
            $this->view->assign('display', false);
        } else {
            $this->view->assign('display', true);
        }
    }

    /**
     * @Secure("loggedIn")
     */
    public function noaccessAction()
    {
    }
}
