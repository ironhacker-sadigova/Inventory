<?php
/**
 * Formulaire d'édition d'un noeud
 * @author ronan.gorain
 * @author matthieu.napoli
 * @package Techno
 */

/**
 * @package Techno
 */
class Techno_Form_AddNode extends UI_Form
{
    /**
     * constructeur du formulaire
     * @param string $ref
     */
    public function __construct($ref)
    {
        parent::__construct($ref);

        $urlHelper = new Zend_View_Helper_Url();
        $this->setAction($urlHelper->url([
                                         'action'     => 'addnode',
                                         'controller' => 'tree_family-tree',
                                         'module'     => 'techno'
                                         ]));

        // Ajax
        $this->setAjax(true, 'onAddCategorySuccess');

        //remplissage des champs du formulaire
        $label = new UI_Form_Element_Text('label');
        $label->setLabel(__('UI', 'name', 'label'));

        //Liste des parents
        $parentSelection = new UI_Form_Element_Select('setParents');
        $parentSelection->setLabel(__('UI', 'name', 'parentCategory'));
        $parentSelection->addNullOption('');
        $allNodes = Tree_Model_Composite::loadAll('Techno_Model_Category');
        foreach ($allNodes as $node) {
            /** @var $nodeEntity Techno_Model_Category */
            $nodeEntity = $node->getEntity();
            $option = new UI_Form_Element_Option('parentSelection_' . $node->getId(),
                                                 $node->getId(), $nodeEntity->getLabel());
            $parentSelection->addOption($option);
        }

        //Choix de la position
        $position = new UI_Form_Element_Radio('position');
        $optionFirst = new UI_Form_Element_Option('first', 'first', 'En début de liste');
        $position->addOption($optionFirst);
        $optionLast = new UI_Form_Element_Option('last', 'last', 'En fin de liste');
        $position->addOption($optionLast);
        $position->setValue($optionLast->value);

        $optionAfter = new UI_Form_Element_Option('after', 'position', 'Après');
        $position->addOption($optionAfter);

        $brothers = new UI_Form_Element_Select('brothers');
        $rootNodes = Tree_Model_Composite::loadRootComposites('Techno_Model_Category');
        $brothers->addNullOption('', null);
        $brothers->addNullOption('', null);
        foreach ($rootNodes as $node) {
            if ($node instanceof Tree_Model_Composite) {
                $option = new UI_Form_Element_Option($node->getId(), $node->getId(),
                                                     $node->getEntity()->getLabel());
                $brothers->addOption($option);
            }
        }

        //Condition si le parent selectionné change
        $yesCondition = new UI_Form_Condition_Elementary('showOptionCondition');
        $yesCondition->element = $parentSelection;
        $yesCondition->relation = UI_Form_Condition::NEQUAL;
        $yesCondition->value = null;

        //Action ajax
        $action = new UI_Form_Action_SetOptions('showOption');
        $action->condition = $yesCondition;
        $action->request = 'techno/Forms_Treeactions/familytreeasync';

        //Condition si le frère selectionné change
        $yesBrotherCondition = new UI_Form_Condition_Elementary('setPositionCondition');
        $yesBrotherCondition->element = $brothers;
        $yesBrotherCondition->relation = UI_Form_Condition::NEQUAL;
        $yesBrotherCondition->value = null;

        //Action ajax
        $actionPosition = new UI_Form_Action_SetValue('setPosition');
        $actionPosition->condition = $yesBrotherCondition;
        $actionPosition->request = 'techno/Forms_Treeactions/familytreepositionasync';
        $actionPosition->request .= '/first/' . $optionFirst->label;
        $actionPosition->request .= '/last/' . $optionLast->label;
        $actionPosition->request .= '/after/' . $optionAfter->label;

        //Ajout des actions
        $brothers->getElement()->addAction($action);
        $position->getElement()->addAction($actionPosition);

        $this->addElement($label);
        $this->addElement($parentSelection);
        $this->addElement($position);
        $this->addElement($brothers);
    }

}
