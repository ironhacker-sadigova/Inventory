<?php
/**
 * Classe du controller du datagrid des indicateurs
 * @author cyril.perraud
 * @package Classif
 * @subpackage Controller
 */

use Core\Annotation\Secure;

/**
 * Classe du controller du datagrid des indicateurs
 * @package Classif
 * @subpackage Controller
 */
class Classif_Datagrid_IndicatorController extends UI_Controller_Datagrid
{
    /**
     * Fonction renvoyant la liste des éléments peuplant la Datagrid.
     *
     * @Secure("viewClassif")
     */
    public function getelementsAction()
    {
        foreach (Classif_Model_Indicator::loadList($this->request) as $indicator) {
            $data = array();
            $data['index'] = $indicator->getRef();
            $data['label'] = $this->cellText($indicator->getLabel());
            $data['ref'] = $this->cellText($indicator->getRef());
            $data['unit'] = $this->cellText($indicator->getUnit()->getRef());
            $data['ratioUnit'] = $this->cellText($indicator->getRatioUnit()->getRef());
            $canUp = !($indicator->getPosition() === 1);
            $canDown = !($indicator->getPosition() === $indicator->getLastEligiblePosition());
            $data['position'] = $this->cellPosition($indicator->getPosition(), $canUp, $canDown);
            $this->addline($data);
        }
        $this->totalElements = Classif_Model_Indicator::countTotal($this->request);

        $this->send();
    }

    /**
     * Fonction permettant d'ajouter un élément.
     *
     * @Secure("editClassif")
     */
    public function addelementAction()
    {
        $ref = $this->getAddElementValue('ref');
        $label = $this->getAddElementValue('label');
        $unit = $this->getAddElementValue('unit');
        $ratioUnit = $this->getAddElementValue('ratioUnit');

        try {
            Core_Tools::checkRef($ref);
            try {
                Classif_Model_Indicator::loadByRef($ref);
                $this->setAddElementErrorMessage('ref', __('UI', 'formValidation', 'alreadyUsedIdentifier'));
            } catch (Core_Exception_NotFound $e) {
                $indicator = new Classif_Model_Indicator();
                $indicator->setRef($ref);
                $indicator->setLabel($label);
                $indicator->setUnit($unit);
                $indicator->setRatioUnit($ratioUnit);
                $indicator->save();
                $this->message = __('UI', 'message', 'added');
            }
        } catch (Core_Exception_User $e) {
            $this->setAddElementErrorMessage('ref', $e->getMessage());
        }

        $this->send();
    }

    /**
     * Fonction supprimant un élément.
     *
     * @Secure("editClassif")
     */
    public function deleteelementAction()
    {
        $indicator = Classif_Model_Indicator::loadByRef($this->delete);

        $queryContextIndicator = new Core_Model_Query();
        $queryContextIndicator->filter->addCondition(Classif_Model_ContextIndicator::QUERY_INDICATOR, $indicator);
        if (Classif_Model_ContextIndicator::countTotal($queryContextIndicator) > 0) {
            throw new Core_Exception_User('Classif', 'indicator', 'IndicatorIsUsedInContextIndicator');
        }

        $indicator->delete();
        $this->message = __('UI', 'message', 'deleted');
        $this->send();
    }

    /**
     * Fonction modifiant la valeur d'un élément.
     *
     * @Secure("editClassif")
     */
    public function updateelementAction()
    {
        $indicator = Classif_Model_Indicator::loadByRef($this->update['index']);
        switch ($this->update['column']) {
            case 'label':
                $indicator->setLabel($this->update['value']);
                $this->message = __('UI', 'message', 'updated');
                break;
            case 'ref':
                Core_Tools::checkRef($this->update['value']);
                try {
                    if (Classif_Model_Indicator::loadByRef($this->update['value']) !== $indicator) {
                        throw new Core_Exception_User('UI', 'formValidation', 'alreadyUsedIdentifier');
                    }
                } catch (Core_Exception_NotFound $e) {
                    $indicator->setRef($this->update['value']);
                    $this->message = __('UI', 'message', 'updated');
                }
                break;
            case 'unit':
                $indicator->setUnit($this->update['value']);
                $this->message = __('UI', 'message', 'updated');
                break;
            case 'ratioUnit':
                $indicator->setRatioUnit($this->update['value']);
                $this->message = __('UI', 'message', 'updated');
                break;
            case 'position' :
                switch ($this->update['value']) {
                    case 'goFirst':
                        $indicator->setPosition(1);
                        break;
                    case 'goUp':
                        $indicator->goUp();
                        break;
                    case 'goDown':
                        $indicator->goDown();
                        break;
                    case 'goLast':
                        $indicator->setPosition($indicator->getLastEligiblePosition());
                        break;
                    default :
                        if ($this->update['value'] > $indicator->getLastEligiblePosition()) {
                            $this->update['value'] = $indicator->getLastEligiblePosition();
                        }
                        $indicator->setPosition((int) $this->update['value']);
                        break;
                }
                $this->update['value'] = $indicator->getPosition();
                $this->message = __('UI', 'message', 'updated');
                break;
            default:
                parent::updateelementAction();
                break;
        }
        $this->data = $this->update['value'];

        $this->send(true);
    }
}