<?php

use Core\Annotation\Secure;
use Core\Translation\TranslatedString;
use MyCLabs\ACL\ACL;
use Parameter\Domain\Family\Family;
use Parameter\Domain\Category;
use Parameter\Domain\ParameterLibrary;
use Unit\UnitAPI;
use User\Domain\ACL\Actions;

/**
 * @author matthieu.napoli
 */
class Parameter_Datagrid_FamilyDatagridController extends UI_Controller_Datagrid
{
    /**
     * @Inject
     * @var ACL
     */
    private $acl;

    /**
     * @Secure("viewParameterLibrary")
     */
    public function getelementsAction()
    {
        /** @var $library ParameterLibrary */
        $library = ParameterLibrary::load($this->getParam('library'));

        $this->totalElements = count($library->getFamilies());
        $families = $library->getFamilies($this->request->totalElements, $this->request->startIndex);

        foreach ($families as $family) {
            /** @var $family Family */
            $data = [];
            $data['category'] = $family->getCategory()->getId();
            $data['label'] = $this->cellTranslatedText($family->getLabel());
            $data['ref'] = $family->getRef();
            $data['unit'] = $this->cellTranslatedText($family->getValueUnit()->getSymbol());
            // Test des droits (consultation/édition)
            $canEdit = $this->acl->isAllowed($this->_helper->auth(), Actions::EDIT, $library);
            if ($canEdit) {
                $data['detail'] = $this->cellLink(
                    $this->_helper->url('edit', 'family', 'parameter', ['id' => $family->getId()])
                );
            } else {
                $data['detail'] = $this->cellLink(
                    $this->_helper->url('details', 'family', 'parameter', ['id' => $family->getId()])
                );
            }
            $this->addLine($data);
        }

        $this->send();
    }

    /**
     * @Secure("editParameterLibrary")
     */
    public function addelementAction()
    {
        /** @var $library ParameterLibrary */
        $library = ParameterLibrary::load($this->getParam('library'));

        // Validation du formulaire
        $idCategory = $this->getAddElementValue('category');
        if (empty($idCategory)) {
            $this->setAddElementErrorMessage('category', __('UI', 'formValidation', 'emptyRequiredField'));
        }
        $ref = $this->getAddElementValue('ref');
        if (empty($ref)) {
            $this->setAddElementErrorMessage('ref', __('UI', 'formValidation', 'emptyRequiredField'));
        }
        $label = $this->getAddElementValue('label');
        if (empty($label)) {
            $this->setAddElementErrorMessage('label', __('UI', 'formValidation', 'emptyRequiredField'));
        }
        $refUnit = $this->getAddElementValue('unit');
        if (empty($refUnit)) {
            $this->setAddElementErrorMessage('unit', __('UI', 'formValidation', 'emptyRequiredField'));
        } else {
            $unit = new UnitAPI($refUnit);
            if (! $unit->exists()) {
                $this->setAddElementErrorMessage('unit', __('UI', 'formValidation', 'invalidUnit'));
            }
        }

        // Pas d'erreurs
        if (empty($this->_addErrorMessages)) {
            /** @var $category Category */
            $category = Category::load($idCategory);

            $label = $this->translator->set(new TranslatedString(), $label);

            try {
                $family = new Family($library, $ref, $label);
            } catch (Exception $e) {
                $this->setAddElementErrorMessage('ref', __('Core', 'exception', 'unauthorizedRef'));
                $this->send();
                return;
            }
            /** @noinspection PhpUndefinedVariableInspection */
            $family->setUnit($unit);
            $family->setCategory($category);
            $family->save();
            try {
                $this->entityManager->flush();
                $this->message = __('UI', 'message', 'added');
            } catch (Exception $e) {
                $this->setAddElementErrorMessage('ref', __('UI', 'formValidation', 'alreadyUsedIdentifier'));
            }
        }
        $this->send();
    }
}
