<?php

use AF\Domain\AF;
use AF\Domain\AFDeletionService;
use AF\Domain\AFLibrary;
use AF\Domain\Category;
use AF\Domain\Component\SubAF;
use AF\Domain\InputSet\InputSet;
use AF\Domain\Output\OutputElement;
use Core\Translation\TranslatedString;
use DI\Annotation\Inject;
use Core\Annotation\Secure;

/**
 * @author matthieu.napoli
 * @author hugo.charbonnier
 * @author thibaud.rolland
 */
class AF_Datagrid_AfController extends UI_Controller_Datagrid
{
    /**
     * @Inject
     * @var AFDeletionService
     */
    private $afDeletionService;

    /**
     * @Secure("editAFLibrary")
     */
    public function getelementsAction()
    {
        /** @var $library AFLibrary */
        $library = AFLibrary::load($this->getParam('library'));

        $this->request->filter->addCondition('library', $library);

        $this->totalElements = AF::countTotal($this->request);
        $afList = AF::loadList($this->request);

        foreach ($afList as $af) {
            $data = [];
            $data['index'] = $af->getId();
            $data['category'] = $this->cellList($af->getCategory()->getId());
            $data['label'] = $this->cellTranslatedText($af->getLabel());
            $data['configuration'] = $this->cellLink($this->view->url([
                'module'     => 'af',
                'controller' => 'edit',
                'action'     => 'menu',
                'id'         => $af->getId(),
            ]), __('UI', 'name', 'configuration'));
            $data['test'] = $this->cellLink($this->view->url([
                'module'     => 'af',
                'controller' => 'af',
                'action'     => 'test',
                'id'         => $af->getId(),
            ]), __('UI', 'name', 'test'));
            $data['duplicate'] = $this->cellPopup($this->view->url([
                'module'     => 'af',
                'controller' => 'af',
                'action'     => 'duplicate-popup',
                'idAF'       => $af->getId(),
                'library'    => $library->getId(),
            ]), __('UI', 'verb', 'duplicate'), 'plus-circle');
            $this->addLine($data);
        }
        $this->send();
    }


    /**
     * @Secure("editAFLibrary")
     */
    public function addelementAction()
    {
        /** @var $library AFLibrary */
        $library = AFLibrary::load($this->getParam('library'));

        // Validation du formulaire
        $idCategory = $this->getAddElementValue('category');
        if (empty($idCategory)) {
            $this->setAddElementErrorMessage('category', __('UI', 'formValidation', 'emptyRequiredField'));
        }
        $label = $this->getAddElementValue('label');
        if (empty($label)) {
            $this->setAddElementErrorMessage('label', __('UI', 'formValidation', 'emptyRequiredField'));
        }
        // Pas d'erreurs
        if (empty($this->_addErrorMessages)) {

            /** @var $category Category */
            $category = Category::load($idCategory);

            $label = $this->translator->set(new TranslatedString(), $label);
            $af = new AF($library, $label);
            $library->addAF($af);
            $af->setCategory($category);
            $af->save();

            $this->entityManager->flush();
            $this->message = __('UI', 'message', 'added');
        }
        $this->send();
    }

    /**
     * @Secure("editAFLibrary")
     */
    public function updateelementAction()
    {
        /** @var $af AF */
        $af = AF::load($this->update['index']);
        $newValue = $this->update['value'];
        switch ($this->update['column']) {
            case 'category':
                /** @var $category Category */
                $category = Category::load($newValue);
                $af->setCategory($category);
                $this->data = $this->cellList($newValue);
                break;
            case 'label':
                if (empty($newValue)) {
                    throw new Core_Exception_User('UI', 'formValidation', 'emptyRequiredField');
                }
                $this->translator->set($af->getLabel(), $newValue);
                $this->data = $this->cellTranslatedText($af->getLabel());
                break;
        }
        $af->save();
        try {
            $this->entityManager->flush();
        } catch (Core_ORM_DuplicateEntryException $e) {
            throw new Core_Exception_User('UI', 'formValidation', 'alreadyUsedIdentifier');
        }
        $this->message = __('UI', 'message', 'updated');
        $this->send();
    }

    /**
     * @Secure("editAFLibrary")
     */
    public function deleteelementAction()
    {
        /** @var $af AF */
        $af = AF::load($this->getParam('index'));
        try {
            $this->afDeletionService->deleteAF($af);
        } catch (Core_ORM_ForeignKeyViolationException $e) {
            if ($e->isSourceEntityInstanceOf(SubAF::class)
                && $e->getSourceField() == 'calledAF') {
                throw new Core_Exception_User('AF', 'formList', 'afUsedByOtherAF');
            }
            if ($e->isSourceEntityInstanceOf(Orga_Model_CellsGroup::class)) {
                throw new Core_Exception_User('AF', 'formList', 'afUsedByOrga');
            }
            if ($e->isSourceEntityInstanceOf(OutputElement::class)) {
                throw new Core_Exception_User('AF', 'formList', 'afUsedByInput');
            }
            if ($e->isSourceEntityInstanceOf(InputSet::class)) {
                throw new Core_Exception_User('AF', 'formList', 'afUsedByInput');
            }
            throw $e;
        }
        $this->message = __('UI', 'message', 'deleted');
        $this->send();
    }
}
