<?php
/**
 * @author  matthieu.napoli
 * @package AF
 */

use AF\Domain\AF;
use AF\Domain\Algorithm\Selection\TextKey\ExpressionSelectionAlgo;
use Core\Annotation\Secure;
use TEC\Exception\InvalidExpressionException;

/**
 * @package AF
 */
class AF_Datagrid_Edit_Algos_SelectionTextkeyExpressionController extends UI_Controller_Datagrid
{

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Datagrid::getelementsAction()
     * @Secure("editAF")
     */
    public function getelementsAction()
    {
        /** @var $af AF */
        $af = AF::load($this->getParam('id'));
        $algos = $af->getAlgos();
        foreach ($algos as $algo) {
            if ($algo instanceof ExpressionSelectionAlgo) {
                $data = [];
                $data['index'] = $algo->getId();
                $data['ref'] = $algo->getRef();
                $data['expression'] = $this->cellLongText(
                    'af/edit_algos/popup-expression/idAF/' . $af->getId() . '/algo/' . $algo->getId(),
                    'af/datagrid_edit_algos_selection-textkey-expression/get-expression/idAF/' . $af->getId()
                    . '/algo/' . $algo->getId(),
                    __('TEC', 'name', 'expression'),
                    'zoom-in'
                );
                $this->addLine($data);
            }
        }
        $this->send();
    }

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Datagrid::addelementAction()
     * @Secure("editAF")
     */
    public function addelementAction()
    {
        /** @var $af AF */
        $af = AF::load($this->getParam('id'));
        $ref = $this->getAddElementValue('ref');
        if (empty($ref)) {
            $this->setAddElementErrorMessage('ref', __('UI', 'formValidation', 'emptyRequiredField'));
        }
        // Pas d'erreurs
        if (empty($this->_addErrorMessages)) {
            $algo = new ExpressionSelectionAlgo();
            try {
                $algo->setRef($ref);
            } catch (Core_Exception_User $e) {
                $this->setAddElementErrorMessage('ref', $e->getMessage());
                $this->send();
                return;
            }
            try {
                $algo->setExpression($this->getAddElementValue('expression'));
            } catch (InvalidExpressionException $e) {
                $this->setAddElementErrorMessage('expression',
                                                 __('AF', 'configTreatmentMessage', 'invalidExpression')
                                                     . "<br>" . implode("<br>", $e->getErrors()));
                $this->send();
                return;
            }
            $algo->save();
            $af->addAlgo($algo);
            $af->save();
            try {
                $this->entityManager->flush();
            } catch (Core_ORM_DuplicateEntryException $e) {
                $this->setAddElementErrorMessage('ref', __('UI', 'formValidation', 'alreadyUsedIdentifier'));
                $this->send();
                return;
            }
            $this->message = __('UI', 'message', 'added');
        }
        $this->send();
    }

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Datagrid::updateelementAction()
     * @Secure("editAF")
     */
    public function updateelementAction()
    {
        /** @var $algo ExpressionSelectionAlgo */
        $algo = ExpressionSelectionAlgo::load($this->update['index']);
        $newValue = $this->update['value'];
        switch ($this->update['column']) {
            case 'ref':
                $algo->setRef($newValue);
                $this->data = $algo->getRef();
                break;
            case 'expression':
                try {
                    $algo->setExpression($newValue);
                } catch (InvalidExpressionException $e) {
                    throw new Core_Exception_User('AF', 'configTreatmentMessage', 'invalidExpressionWithErrors',
                                                  ['ERRORS' => implode("<br>", $e->getErrors())]);
                }
                $this->data = null;
                break;
        }
        $algo->save();
        try {
            $this->entityManager->flush();
        } catch (Core_ORM_DuplicateEntryException $e) {
            throw new Core_Exception_User('UI', 'formValidation', 'alreadyUsedIdentifier');
        }
        $this->message = __('UI', 'message', 'updated');
        $this->send();
    }

    /**
     * (non-PHPdoc)
     * @see UI_Controller_Datagrid::deleteelementAction()
     * @Secure("editAF")
     */
    public function deleteelementAction()
    {
        /** @var $algo ExpressionSelectionAlgo */
        $algo = ExpressionSelectionAlgo::load($this->getParam('index'));
        $algo->delete();
        $algo->getSet()->removeAlgo($algo);
        $algo->getSet()->save();
        try {
            $this->entityManager->flush();
        } catch (Core_ORM_ForeignKeyViolationException $e) {
            throw new Core_Exception_User('AF', 'configTreatmentMessage', 'algoUsed');
        }
        $this->message = __('UI', 'message', 'deleted');
        $this->send();
    }

    /**
     * Fonction permettant de récupérer la forme brute de l'expression
     * @Secure("editAF")
     */
    public function getExpressionAction()
    {
        /** @var $algo ExpressionSelectionAlgo */
        $algo = ExpressionSelectionAlgo::load($this->getParam('algo'));
        $this->data = $algo->getExpression();
        $this->send();
    }

}
