<?php
/**
 * @author  matthieu.napoli
 * @package Techno
 */

use Core\Annotation\Secure;

/**
 * Controleur des familles
 * @package Techno
 */
class Techno_FamilyController extends Core_Controller_Ajax
{

    use UI_Controller_Helper_Form;

    /**
     * Arbre des familles en édition
     * @Secure("editTechno")
     */
    public function treeEditAction()
    {
        $this->_forward('tree', 'family', 'techno', array('mode' => 'edition'));
    }

    /**
     * Arbre des familles
     * @Secure("viewTechno")
     */
    public function treeAction()
    {
        $mode = $this->_getParam('mode');
        if (empty($mode)) {
            $mode = 'consultation';
        }
        $this->view->mode = $mode;
    }

    /**
     * Liste des familles en édition
     * @Secure("editTechno")
     */
    public function listEditAction()
    {
        $this->_forward('list', 'family', 'techno', array('mode' => 'edition'));
    }

    /**
     * Liste des familles
     * @Secure("viewTechno")
     */
    public function listAction()
    {
        $mode = $this->_getParam('mode');
        if (empty($mode)) {
            $mode = 'consultation';
        }
        $this->view->mode = $mode;
        $this->view->categoryList = Techno_Model_Category::loadList();
    }

    /**
     * Détails d'une famille
     * @Secure("viewTechno")
     */
    public function detailsAction()
    {
        $mode = $this->_getParam('mode');
        if (empty($mode)) {
            $mode = 'consultation';
        }
        if ($mode == 'consultation') {
            $this->view->edit = false;
        } else {
            $this->view->edit = true;
        }
        $this->view->mode = $mode;
        $this->view->family = Techno_Model_Family::load($this->_getParam('id'));
    }

    /**
     * Édition d'une famille
     * @Secure("editTechno")
     */
    public function editAction()
    {
        $this->_forward('details', 'family', 'techno', array('mode' => 'edition'));
    }

    /**
     * Détails d'une famille - Onglet Général
     * AJAX
     * @Secure("editTechno")
     */
    public function detailsMainTabAction()
    {
        $this->view->family = Techno_Model_Family::load($this->_getParam('id'));
        $this->view->meanings = Techno_Model_Meaning::loadList();
        $this->view->keywords = Keyword_Model_Keyword::loadList();
        $this->_helper->layout()->disableLayout();
    }

    /**
     * Détails d'une famille - Onglet Éléments
     * AJAX
     * @Secure("viewTechno")
     */
    public function detailsElementsTabAction()
    {
        $mode = $this->_getParam('mode');
        if (empty($mode)) {
            $mode = 'consultation';
        }
        if ($mode == 'consultation') {
            $this->view->edit = false;
        } else {
            $this->view->edit = true;
        }
        $this->view->mode = $mode;
        $this->view->family = Techno_Model_Family::load($this->_getParam('id'));
        $this->_helper->layout()->disableLayout();
    }

    /**
     * Détails d'une famille - Onglet Documentation
     * AJAX
     * @Secure("viewTechno")
     */
    public function detailsDocumentationTabAction()
    {
        $mode = $this->_getParam('mode');
        if (empty($mode)) {
            $mode = 'consultation';
        }
        if ($mode == 'consultation') {
            $this->view->edit = false;
        } else {
            $this->view->edit = true;
        }
        $this->view->family = Techno_Model_Family::load($this->_getParam('id'));
        $this->_helper->layout()->disableLayout();
    }

    /**
     * AJAX
     * @Secure("editTechno")
     */
    public function submitDocumentationAction()
    {
        /** @var $family Techno_Model_Family */
        $family = Techno_Model_Family::load($this->_getParam('id'));
        $formData = $this->getFormData('documentationForm');
        $family->setDocumentation($formData->getValue('documentation'));
        $family->save();
        $entityManagers = Zend_Registry::get('EntityManagers');
        $entityManagers['default']->flush();
        $this->setFormMessage(__('UI', 'message', 'updated'));
        $this->sendFormResponse();
    }

    /**
     * Suppression d'une famille
     * AJAX
     * @Secure("editTechno")
     */
    public function deleteAction()
    {
        $idFamily = $this->_getParam('id');
        /** @var $family Techno_Model_Family */
        $family = Techno_Model_Family::load($idFamily);
        if ($family->hasChosenElements()) {
            throw new Core_Exception_User('Techno', 'familyDetail', 'cantDeleteFamily');
        }
        $family->delete();
        $entityManagers = Zend_Registry::get('EntityManagers');
        $entityManagers['default']->flush();
        UI_Message::getInstance()->addMessage(__('UI', 'message', 'deleted'), UI_Message::TYPE_SUCCESS);
        $this->sendJsonResponse([
                                'message' => __('UI', 'message', 'deleted'),
                                'type'    => 'success',
                                ]);
    }

}
