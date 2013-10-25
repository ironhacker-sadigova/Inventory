<?php

use Core\Autoloader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Driver\SimplifiedYamlDriver;
use Keyword\Application\Service\KeywordService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Keyword\Architecture\TypeMapping\DoctrineKeywordType;
use User\Application\Plugin\ACLPlugin;
use User\Application\ViewHelper\IsAllowedHelper;

/**
 * Application bootstrap
 */
class Bootstrap extends Core_Bootstrap
{
    /**
     * Add and Configure all modules dependencies.
     */
    protected function _initModules()
    {
        $autoloader = Autoloader::getInstance();
        $frontController = Zend_Controller_Front::getInstance();
        /* @var $doctrineConfig Doctrine\ORM\Configuration */
        $doctrineConfig = Zend_Registry::get('doctrineConfiguration');
        /** @var Doctrine\ORM\Mapping\Driver\DriverChain $driver */
        $driver = $doctrineConfig->getMetadataDriverImpl();

        $autoloader->addModule('Inventory', APPLICATION_PATH);

        $modules = [
            'Unit',
            'User',
            'TEC',
            'Classif',
            'Keyword',
            'Techno',
            'Doc',
            'DW',
            'Algo',
            'AF',
            'Social',
            'Orga',
            'Simulation',
            'AuditTrail',
        ];

        foreach ($modules as $module) {
            $moduleRoot = APPLICATION_PATH . '/' . strtolower($module);
            $moduleRoot2 = PACKAGE_PATH . '/src/' . $module;

            if (file_exists($moduleRoot)) {
                // Autoloader
                $autoloader->addModule($module, $moduleRoot);

                // Controllers
                $frontController->addControllerDirectory($moduleRoot . '/controllers', strtolower($module));

                // Bootstrap
                $bootstrapFile = $moduleRoot . '/Bootstrap.php';
                if (file_exists($bootstrapFile)) {
                    require_once $bootstrapFile;
                    $bootstrapName = $module . '_Bootstrap';
                    /** @var $bootstrap Core_Package_Bootstrap */
                    $bootstrap = new $bootstrapName($this->_application);
                    $bootstrap->container = $this->container;
                    $bootstrap->setRun($this->_run);
                    $bootstrap->bootstrap();
                    foreach ($bootstrap->getRun() as $run) {
                        $this->_markRun($run);
                    }
                }

                // Doctrine Mappers
                if (file_exists($moduleRoot . '/models/mappers')) {
                    $driver->getDefaultDriver()->getLocator()->addPaths([$moduleRoot . '/models/mappers']);
                }
            } elseif (file_exists($moduleRoot2)) {
                if (file_exists($moduleRoot2 . '/Application/Controller')) {
                    // Controllers
                    $frontController->addControllerDirectory($moduleRoot2 . '/Application/Controller',
                        strtolower($module));
                }

                // Bootstrap
                $bootstrapFile = $moduleRoot2 . '/Application/Bootstrap.php';
                if (file_exists($bootstrapFile)) {
                    require_once $bootstrapFile;
                    $bootstrapName = $module . '\Application\Bootstrap';
                    /** @var $bootstrap Core_Package_Bootstrap */
                    $bootstrap = new $bootstrapName($this->_application);
                    $bootstrap->container = $this->container;
                    $bootstrap->setRun($this->_run);
                    $bootstrap->bootstrap();
                    foreach ($bootstrap->getRun() as $run) {
                        $this->_markRun($run);
                    }
                }

                // Doctrine Mappers
                if (file_exists($moduleRoot2 . '/Architecture/DBMapper')) {
                    $yamlDriver = new SimplifiedYamlDriver(
                        [$moduleRoot2 . '/Architecture/DBMapper' => $module . '\Domain'],
                        '.yml'
                    );
                    $driver->addDriver($yamlDriver, $module . '\Domain');
                }
            }
        }
    }

    /**
     * Locale et traductions
     */
    protected function _initI18n()
    {
        Zend_Registry::set(Core_Translate::registryKey, $this->container->get(Core_Translate::class));
        Zend_Registry::set(Core_Locale::registryKey, Core_Locale::loadDefault());
    }

    /**
     * Enregistre les helpers de vue
     */
    protected function _initViewHelpers()
    {
        $this->bootstrap('View');
        $view = $this->getResource('view');
        $view->addHelperPath(PACKAGE_PATH . '/src/Core/View/Helper', 'Core_View_Helper');
        $view->addHelperPath(PACKAGE_PATH . '/src/UI/View/Helper', 'UI_View_Helper');
        $view->registerHelper($this->container->get(IsAllowedHelper::class, true), 'isAllowed');
    }

    /**
     * Initialise le mapping des types en BDD
     */
    protected function _initCalcTypeMapping()
    {
        Type::addType(Calc_TypeMapping_Value::TYPE_NAME, Calc_TypeMapping_Value::class);
        Type::addType(Calc_TypeMapping_UnitValue::TYPE_NAME, Calc_TypeMapping_UnitValue::class);
    }

    /**
     * Initialise le mapping des types en BDD
     */
    protected function _initKeywordTypeMapping()
    {
        Type::addType(DoctrineKeywordType::TYPE_NAME, DoctrineKeywordType::class);
        /** @var DoctrineKeywordType $doctrineKeyword */
        $doctrineKeyword = Type::getType(DoctrineKeywordType::TYPE_NAME);
        $doctrineKeyword->setKeywordService($this->container->get(KeywordService::class));
    }

    /**
     * Enregistrement du plugin pour les ACL
     */
    protected function _initPluginAcl()
    {
        $front = Zend_Controller_Front::getInstance();
        // Plugin des Acl
        if ($this->container->get('enable.acl')) {
            $front->registerPlugin($this->container->get(Inventory_Plugin_Acl::class));
            Zend_Registry::set('pluginAcl', ACLPlugin::class);
        }
    }

    /**
     * Event listeners
     */
    protected function _initEventListeners()
    {
        /** @var EventDispatcher $eventDispatcher */
        $eventDispatcher = $this->container->get(EventDispatcher::class);

        // User events (plus prioritaire)
        $userEventListener = $this->container->get(\User\Domain\Event\EventListener::class, true);
        $eventDispatcher->addListener(Orga_Service_InputCreatedEvent::NAME, [$userEventListener, 'onUserEvent'], 10);
        $eventDispatcher->addListener(Orga_Service_InputEditedEvent::NAME, [$userEventListener, 'onUserEvent'], 10);

        // AuditTrail
        $auditTrailListener = $this->container->get(AuditTrail\Application\Service\EventListener::class, true);
        $eventDispatcher->addListener(Orga_Service_InputCreatedEvent::NAME, [$auditTrailListener, 'onInputCreated']);
        $eventDispatcher->addListener(Orga_Service_InputEditedEvent::NAME, [$auditTrailListener, 'onInputEdited']);
    }

}
