<?php

use Core\Annotation\Secure;
use Parameter\Domain\ParameterLibrary;

/**
 * @author matthieu.napoli
 */
class Parameter_LibraryController extends Core_Controller
{
    /**
     * @Secure("viewParameter")
     */
    public function viewAction()
    {
        /** @var $library ParameterLibrary */
        $library = ParameterLibrary::load($this->getParam('id'));

        $this->view->assign('library', $library);
        // TODO droit d'édition
        $this->view->assign('edit', true);
        $this->addBreadcrumb($library->getLabel());
    }
}
