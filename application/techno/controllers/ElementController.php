<?php
/**
 * @author matthieu.napoli
 * @package Techno
 */

use Core\Annotation\Secure;
use Unit\UnitAPI;

/**
 * Controleur des éléments
 * @package Techno
 */
class Techno_ElementController extends Core_Controller
{

    use UI_Controller_Helper_Form;

    /**
     * Détails d'un élément
     * @Secure("editTechno")
     */
    public function detailsAction()
    {
        $this->_helper->layout()->disableLayout();
        $idElement = $this->getParam('id');
        $this->view->element = Techno_Model_Element::load($idElement);
    }

    /**
     * Modification d'un élément
     * @Secure("editTechno")
     */
    public function editSubmitAction()
    {
        $formData = $this->getFormData('element_editForm');
        $idElement = $formData->getValue('id');
        /** @var $element Techno_Model_Element_Process|Techno_Model_Element_Coeff */
        $element = Techno_Model_Element::load($idElement);
        // Validation du formulaire
        $digitalValue = $formData->getValue('digitalValue');
        $uncertainty = $formData->getValue('uncertainty');
        $refUnit = $formData->getValue('unit');
        if (empty($refUnit)) {
            $this->addFormError('unit', __('UI', 'formValidation', 'emptyRequiredField'));
        }
        $documentation = $formData->getValue('documentation');
        // Modification
        if (! $this->hasFormError()) {
            $unit = new UnitAPI($refUnit);
            if ($element->getUnit()->getRef() != $unit->getRef()) {
                try {
                    $element->setUnit($unit);
                } catch (Core_Exception_InvalidArgument $e) {
                    throw new Core_Exception_User('Techno', 'element', 'incompatibleUnit');
                }
            }
            $element->setValue(new Calc_Value($digitalValue, $uncertainty));
            $element->setDocumentation($documentation);
            $element->save();
            $this->entityManager->flush();
            $this->setFormMessage(__('UI', 'message', 'updated'));
        } else {
            $this->setFormMessage('Erreur de validation du formulaire.');
        }
        $this->sendFormResponse(
            [
                'elementId' => $element->getId(),
                'value' => $element->getValue()->getDigitalValue(),
                'uncertainty' => $element->getValue()->getRelativeUncertainty()
            ]
        );
    }

}
