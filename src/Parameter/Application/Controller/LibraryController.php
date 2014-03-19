<?php

use AF\Domain\AFLibrary;
use Core\Annotation\Secure;

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
        /** @var $library AFLibrary */
        $library = AFLibrary::load($this->getParam('id'));

        $this->view->assign('library', $library);
        // TODO droit d'édition
        $this->view->assign('edit', true);
    }
}
