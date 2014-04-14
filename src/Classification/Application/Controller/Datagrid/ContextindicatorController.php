<?php

use Classification\Domain\ClassificationLibrary;
use Classification\Domain\ContextIndicator;
use Classification\Domain\Axis;
use Classification\Domain\Context;
use Classification\Domain\Indicator;
use Core\Annotation\Secure;

class Classification_Datagrid_ContextindicatorController extends UI_Controller_Datagrid
{
    /**
     * @Secure("editClassificationLibrary")
     */
    public function getelementsAction()
    {
        /** @var ClassificationLibrary $library */
        $library = ClassificationLibrary::load($this->getParam('library'));

        foreach ($library->getContextIndicators() as $contextIndicator) {
            $data = array();
            $data['index'] = $contextIndicator->getId();
            $data['context'] = $this->cellList($contextIndicator->getContext()->getId());
            $data['indicator'] = $this->cellList($contextIndicator->getIndicator()->getId());
            $refAxes = array();
            foreach ($contextIndicator->getAxes() as $axis) {
                $refAxes[] = $axis->getId();
            }
            $data['axes'] = $this->cellList($refAxes);
            $this->addline($data);
        }

        $this->send();
    }

    /**
     * @Secure("editClassificationLibrary")
     */
    public function addelementAction()
    {
        /** @var ClassificationLibrary $library */
        $library = ClassificationLibrary::load($this->getParam('library'));

        $idContext = $this->getAddElementValue('context');
        if (empty($idContext)) {
            $this->setAddElementErrorMessage('context', __('UI', 'formValidation', 'emptyRequiredField'));
        }
        $idIndicator = $this->getAddElementValue('indicator');
        if (empty($idIndicator)) {
            $this->setAddElementErrorMessage('indicator', __('UI', 'formValidation', 'emptyRequiredField'));
        }

        if (empty($this->_addErrorMessages)) {
            $context = Context::load($idContext);
            $indicator = Indicator::load($idIndicator);
            try {
                ContextIndicator::loadByRef($context->getRef(), $indicator->getRef());
                $this->setAddElementErrorMessage('context', __('Classification', 'contextIndicator', 'ContextIndicatorAlreadyExists'));
                $this->setAddElementErrorMessage('indicator', __('Classification', 'contextIndicator', 'ContextIndicatorAlreadyExists'));
            } catch (Core_Exception_NotFound $e) {
                $contextIndicator = new ContextIndicator($library, $context, $indicator);

                try {
                    if ($this->getAddElementValue('axes') != null) {
                        foreach ($this->getAddElementValue('axes') as $idAxis) {
                            $contextIndicator->addAxis(Axis::load($idAxis));
                        }
                    }

                    $contextIndicator->save();
                    $this->message = __('UI', 'message', 'added');
                } catch (Core_Exception_InvalidArgument $e) {
                    $this->setAddElementErrorMessage('axes', __('Classification', 'contextIndicator', 'axesMustBeTransverse'));
                }
            }
        }

        $this->send();
    }

    /**
     * @Secure("editClassificationLibrary")
     */
    public function deleteelementAction()
    {
        $contextIndicator = ContextIndicator::load($this->delete);
        $contextIndicator->delete();
        $this->message = __('UI', 'message', 'deleted');

        $this->send();
    }

    /**
     * @Secure("editClassificationLibrary")
     */
    public function updateelementAction()
    {
        $contextIndicator = ContextIndicator::load($this->update['index']);

        switch ($this->update['column']) {
            case 'axes':
                if (empty($this->update['value'])) {
                    $listRefAxes = [];
                } else {
                    $listRefAxes = explode(',', $this->update['value']);
                }
                foreach ($contextIndicator->getAxes() as $axis) {
                    if (in_array($axis->getRef(), $listRefAxes)) {
                        unset($listRefAxes[array_search($axis->getRef(), $listRefAxes)]);
                    } else {
                        $contextIndicator->removeAxis($axis);
                    }
                }
                foreach ($listRefAxes as $axisId) {
                    $axis = Axis::load($axisId);
                    try {
                        $contextIndicator->addAxis($axis);
                    } catch (Core_Exception_InvalidArgument $e) {
                        throw new Core_Exception_User('Classification', 'contextIndicator', 'axesMustBeTransverse');
                    }
                }
                break;
            default:
                parent::updateelementAction();
                break;
        }

        $this->send();
    }
}
