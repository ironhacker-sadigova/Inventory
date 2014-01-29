<?php

use AF\Domain\AF\Action\SetState;
use AF\Domain\AF\Action\SetAlgoValue;
use AF\Domain\AF\Action\SetValue\SetNumericFieldValue;
use AF\Domain\AF\Action\SetValue\SetCheckboxValue;
use AF\Domain\AF\Action\SetValue\Select\SetSelectSingleValue;
use AF\Domain\AF\AF;
use AF\Domain\AF\Action\Action;
use AF\Domain\AF\Component\AF_Model_Component_Group;
use AF\Domain\AF\Component\Select\SelectOption;
use AF\Domain\AF\Component\TextField;
use AF\Domain\AF\Component\Select;
use AF\Domain\AF\Component\NumericField;
use AF\Domain\AF\Component\Checkbox;
use AF\Domain\AF\Component\Field;
use AF\Domain\AF\Component\Select\SelectSingle;
use AF\Domain\AF\Component\Select\SelectMulti;
use AF\Domain\AF\Component\SubAF\RepeatedSubAF;
use AF\Domain\AF\Component\SubAF\NotRepeatedSubAF;
use AF\Domain\AF\Condition\Condition;
use AF\Domain\AF\Component;
use AF\Domain\AF\Category;
use AF\Domain\AF\Condition\ExpressionCondition;
use AF\Domain\AF\Condition\Elementary\NumericFieldCondition;
use AF\Domain\AF\Condition\Elementary\CheckboxCondition;
use AF\Domain\AF\Condition\Elementary\Select\SelectSingleCondition;
use AF\Domain\AF\Condition\Elementary\Select\SelectMultiCondition;
use AF\Domain\Algorithm\Condition\Elementary\NumericConditionAlgo;
use AF\Domain\Algorithm\Condition\Elementary\BooleanConditionAlgo;
use AF\Domain\Algorithm\Condition\Elementary\Select\SelectSingleConditionAlgo;
use AF\Domain\Algorithm\Condition\Elementary\Select\SelectMultiConditionAlgo;
use AF\Domain\Algorithm\Condition\ExpressionConditionAlgo;
use AF\Domain\Algorithm\Index\AlgoResultIndex;
use AF\Domain\Algorithm\Index\FixedIndex;
use AF\Domain\Algorithm\Numeric\NumericAlgo;
use Techno\Domain\Family\Family;

/**
 * Remplissage de la base de données avec des données de test
 */
class AF_Populate extends Core_Script_Action
{
    /**
     * {@inheritdoc}
     */
    public function runEnvironment($environment)
    {
        $entityManager = \Core\ContainerSingleton::getEntityManager();


        // Création des catégories.
        //  + createCategory : -
        // Params : ref
        // OptionalParams : Category parent=null

        // Création des af.
        //  + createAF : -
        // Params : Category, ref, label

        // Création des composants.
        // Params : AF, Group, ref, label
        //  + createGroup : -
        //  + createSubAF(Repeated) : AF calledAF
        //  + createShortTextInput : -
        //  + createLongTextInput : -
        //  + createNumericInput : refUnit
        //  + createSelectInput List|Radio|Multi|Boxes : [refOption => labelOption]
        //  + createBooleanInput : -
        // OptionalParams :
        //  + createGroup : foldaway=true
        //  + createSubAF : foldaway=true
        //  + createSubAFRepeated : foldaway=true, minimumRepetition=0, freeLabel=false
        //  + createShortTextInput : required=true, enabled=true
        //  + createLongTextInput : required=true, enabled=true
        //  + createNumericInput : defaultValue=null, defaultUncertainty=null, defaultReminder=true, required=true, enabled=true
        //  + createSelectInput List|Radio|Multi|Boxes : required=true, enabled=true
        //  + createBooleanInput : defaultValue=true
        //  help=null, visible=true

        // Création des Algos et indexation (ne renvoient rien).
        //  Tip : Pour récupérer un algo à partir de l'AF : $aF->getAlgoByRef();
        //   Donc, Pour récupérer l'algo d'un champs NumericInput : $aF->getAlgoByRef($input->getRef());
        // Param : AF
        //  + createAlgoNumericExpression : ref, label, expression, refUnit
        //  + createAlgoNumericExpression : ref, label, value, uncertainty, refUnit
        //  + createFixedIndexForAlgoNumeric : Numeric numeric, refContext, refIndicator, [refAxis => refMember]
        //  + createAlgoIndexForAlgoNumeric : Numeric numeric, refContext, refIndicator, [refAxis => Selection_TextKey algo]
        //  + createAlgoNumericParameter : ref, label, refFamily
        //  + createFixedCoordinateForAlgoParameter : Parameter parameter, [refDimension => refMember]
        //  + createAlgoCoordinateForAlgoParameter : Parameter parameter, [refDimension => Selection_TextKey algo]
        //  + createAlgoSelectTextkeyExpression : ref, expression
        //  + createAlgoSelectTextkeyContextValue : ref, name, defaultValue
        //  + createAlgoConditionElementary : Component input, ref
        //  + createAlgoConditionExpression : ref, expression
        // OptionalParams : -

        // Création des Condition.
        // Param: AF, ref
        //  + createConditionElementary : Field component
        //  + createConditionExpression : expression
        // OptionalParams : -

        // Création des Action.
        // Tip : state et type sont des constantes de la classe Action acceptant respectivement
        //  [ TYPE_DISABLE | TYPE_ENABLE | TYPE_HIDE | TYPE_SHOW ] et [ TYPE_SETVALUE | TYPE_SETALGOVALUE]
        // Param: Component component
        //  + createActionSetState : state
        //  + createActionSetValue : type, value
        // OptionalParams : Condition condition


        $entityManager->flush();

        echo "\t\tAF created".PHP_EOL;
    }

    /**
     * @param string $label
     * @param Category $parent
     * @return Category
     */
    protected function createCategory($label, Category $parent=null)
    {
        $category = new Category();
        $category->setLabel($label);
        if ($parent !== null) {
            $category->setParentCategory($parent);
        }
        $category->save();
        return $category;
    }

    /**
     * @param Category $category
     * @param $ref
     * @param $label
     * @return AF
     */
    protected function createAF(Category $category, $ref, $label)
    {
        $aF = new AF($ref);
        $aF->setLabel($label);
        $aF->save();
        $category->addAF($aF);
        return $aF;
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param $ref
     * @param $label
     * @param bool $foldaway
     * @param null $help
     * @param bool $visible
     * @return AF_Model_Component_Group
     */
    protected function createGroup(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label,
        $foldaway=true, $help=null, $visible=true)
    {
        $group = new AF_Model_Component_Group();
        $group->setFoldaway($foldaway);
        return $this->createComponent($group, $aF,$parentGroup ,$ref, $label, $help, $visible, $foldaway);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param AF $calledAF
     * @param $ref
     * @param $label
     * @param bool $foldaway
     * @param null $help
     * @param bool $visible
     * @return mixed
     */
    protected function createSubAF(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label, AF $calledAF,
        $foldaway=true, $help=null, $visible=true)
    {
        $subAF = new NotRepeatedSubAF();
        $subAF->setCalledAF($calledAF);
        $subAF->setFoldaway($foldaway);
        return $this->createComponent($subAF, $aF,$parentGroup ,$ref, $label, $help, $visible, $foldaway);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param AF $calledAF
     * @param $ref
     * @param $label
     * @param bool $foldaway
     * @param int $minimumRepetition
     * @param bool $freeLabel
     * @param null $help
     * @param bool $visible
     * @return mixed
     */
    protected function createSubAFRepeated(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label, AF $calledAF,
        $foldaway=true, $minimumRepetition=0, $freeLabel=false, $help=null, $visible=true)
    {
        $subAF = new RepeatedSubAF();
        $subAF->setCalledAF($calledAF);
        $subAF->setMinInputNumber($minimumRepetition);
        $subAF->setWithFreeLabel($freeLabel);
        $subAF->setFoldaway($foldaway);
        return $this->createComponent($subAF, $aF,$parentGroup ,$ref, $label, $help, $visible, $foldaway);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param $ref
     * @param $label
     * @param bool $required
     * @param bool $enabled
     * @param null $help
     * @param bool $visible
     * @return Component
     */
    protected function createShortTextInput(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label,
        $required=true, $enabled=true, $help=null, $visible=true)
    {
        $textInput = new TextField(TextField::TYPE_SHORT);
        $textInput->setRequired($required);
        $textInput->setEnabled($enabled);
        return $this->createComponent($textInput, $aF, $parentGroup ,$ref, $label, $help, $visible);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param $ref
     * @param $label
     * @param bool $required
     * @param bool $enabled
     * @param null $help
     * @param bool $visible
     * @return Component
     */
    protected function createLongTextInput(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label,
        $required=true, $enabled=true, $help=null, $visible=true)
    {
        $textInput = new TextField(TextField::TYPE_LONG);
        $textInput->setRequired($required);
        $textInput->setEnabled($enabled);
        return $this->createComponent($textInput, $aF, $parentGroup ,$ref, $label, $help, $visible);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param $ref
     * @param $label
     * @param $refUnit
     * @param null $defaultValue
     * @param null $defaultUncertainty
     * @param bool $defaultReminder
     * @param bool $required
     * @param bool $enabled
     * @param null $help
     * @param bool $visible
     * @return Component
     */
    protected function createNumericInput(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label, $refUnit,
        $defaultValue=null, $defaultUncertainty=null, $defaultReminder=true, $required=true, $enabled=true, $help=null, $visible=true)
    {
        $numericInput = new NumericField();
        $numericInput->setUnit(new \Unit\UnitAPI($refUnit));
        $numericInput->setRequired($required);
        $numericInput->setEnabled($enabled);
        if ($defaultValue !== null) {
            $calcValue = new Calc_Value($defaultValue, $defaultUncertainty);
            $numericInput->setDefaultValue($calcValue);
            $numericInput->setDefaultValueReminder($defaultReminder);
        }
        return $this->createComponent($numericInput, $aF, $parentGroup ,$ref, $label, $help, $visible);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param string $ref
     * @param string $label
     * @param array $options
     * @param bool $required
     * @param bool $enabled
     * @param string $help
     * @param bool $visible
     * @return Component
     */
    protected function createSelectInputList(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label, array $options,
        $required=true, $enabled=true, $help=null, $visible=true)
    {
        $selectInput = new SelectSingleCondition();
        $selectInput->setType(SelectSingleCondition::TYPE_LIST);
        return $this->createSelectInput($selectInput, $aF, $parentGroup ,$ref, $label, $options, $required, $enabled, $help, $visible);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param string $ref
     * @param string $label
     * @param array $options
     * @param bool $required
     * @param bool $enabled
     * @param string $help
     * @param bool $visible
     * @return Component
     */
    protected function createSelectInputRadio(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label, array $options,
        $required=true, $enabled=true, $help=null, $visible=true)
    {
        $selectInput = new SelectSingleCondition();
        $selectInput->setType(SelectSingleCondition::TYPE_RADIO);
        return $this->createSelectInput($selectInput, $aF, $parentGroup ,$ref, $label, $options, $required, $enabled, $help, $visible);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param string $ref
     * @param string $label
     * @param array $options
     * @param bool $required
     * @param bool $enabled
     * @param string $help
     * @param bool $visible
     * @return Component
     */
    protected function createSelectInputMulti(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label, array $options,
        $required=true, $enabled=true, $help=null, $visible=true)
    {
        $selectInput = new SelectMultiCondition();
        $selectInput->setType(SelectMultiCondition::TYPE_MULTISELECT);
        return $this->createSelectInput($selectInput, $aF, $parentGroup ,$ref, $label, $options, $required, $enabled, $help, $visible);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param string $ref
     * @param string $label
     * @param array $options
     * @param bool $required
     * @param bool $enabled
     * @param string $help
     * @param bool $visible
     * @return Component
     */
    protected function createSelectInputBoxes(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label, array $options,
        $required=true, $enabled=true, $help=null, $visible=true)
    {
        $selectInput = new SelectMultiCondition();
        $selectInput->setType(SelectMultiCondition::TYPE_MULTICHECKBOX);
        return $this->createSelectInput($selectInput, $aF, $parentGroup ,$ref, $label, $options, $required, $enabled, $help, $visible);
    }

    /**
     * @param Select $selectInput
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param string $ref
     * @param string $label
     * @param array $options
     * @param bool $required
     * @param bool $enabled
     * @param string $help
     * @param bool $visible
     * @return Component
     */
    private function createSelectInput(Select $selectInput, AF $aF, AF_Model_Component_Group $parentGroup,
        $ref, $label, array $options, $required=true, $enabled=true, $help=null, $visible=true)
    {
        $selectInput->setRequired($required);
        $selectInput->setEnabled($enabled);
        foreach ($options as $refOption => $labelOption) {
            $option = new SelectOption();
            $option->setSelect($selectInput);
            $option->setRef($refOption);
            $option->setLabel($labelOption);
        }
        return $this->createComponent($selectInput, $aF, $parentGroup ,$ref, $label, $help, $visible);
    }

    /**
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param string $ref
     * @param string $label
     * @param bool $defaultValue
     * @param bool $enabled
     * @param string $help
     * @param bool $visible
     * @return Component
     */
    protected function createBooleanInput(AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label,
        $defaultValue=true, $enabled=true, $help=null, $visible=true)
    {
        $boolean = new CheckboxCondition();
        $boolean->setDefaultValue($defaultValue);
        $boolean->setEnabled($enabled);
        return $this->createComponent($boolean, $aF, $parentGroup ,$ref, $label, $help, $visible);
    }

    /**
     * @param Component $component
     * @param AF $aF
     * @param AF_Model_Component_Group $parentGroup
     * @param $ref
     * @param $label
     * @param null $help
     * @param bool $visible
     * @return Component
     */
    private function createComponent(Component $component, AF $aF, AF_Model_Component_Group $parentGroup, $ref, $label,
        $help=null, $visible=true)
    {
        $component->setAf($aF);
        $component->setRef($ref);
        $component->setLabel($label);
        $component->setHelp($help);
        $component->setVisible($visible);
        $component->save();
        $parentGroup->addSubComponent($component);
        $aF->addComponent($component);
        return $component;
    }

    /**
     * @param AF $aF
     * @param string $ref
     * @param string $label
     * @param string $expression
     * @param string $refUnit
     */
    protected function createAlgoNumericExpression(AF $aF, $ref, $label, $expression, $refUnit)
    {
        $numericExpression = new Algo_Model_Numeric_Expression();
        $numericExpression->setExpression($expression);
        $numericExpression->setUnit(new \Unit\UnitAPI($refUnit));
        $this->createAlgoNumeric($aF, $numericExpression, $ref, $label);
    }

    /**
     * @param AF $aF
     * @param string $ref
     * @param string $label
     * @param int $value
     * @param int $uncertainty
     * @param string $refUnit
     */
    protected function createAlgoNumericConstant(AF $aF, $ref, $label, $value, $uncertainty, $refUnit)
    {
        $numericExpression = new Algo_Model_Numeric_Constant();
        $unitValue = new Calc_UnitValue(new \Unit\UnitAPI($refUnit), $value, $uncertainty);
        $numericExpression->setUnitValue($unitValue);
        $this->createAlgoNumeric($aF, $numericExpression, $ref, $label);
    }

    /**
     * @param AF $aF
     * @param \AF\Domain\Algorithm\Numeric\NumericAlgo $numeric
     * @param string $ref
     * @param string $label
     */
    private function createAlgoNumeric(AF $aF, NumericAlgo $numeric, $ref, $label)
    {
        $numeric->setRef($ref);
        $numeric->setLabel($label);
        $numeric->save();
        $aF->addAlgo($numeric);
    }

    /**
     * @param NumericAlgo $numeric
     * @param string $refContext
     * @param string $refIndicator
     * @param array $indexes Sous la forme [$refAxis =» $refMember]
     */
    protected function createFixedIndexForAlgoNumeric(NumericAlgo $numeric, $refContext, $refIndicator, $indexes)
    {
        $numeric->setContextIndicator(Classif_Model_ContextIndicator::loadByRef($refContext, $refIndicator));
        foreach ($indexes as $refAxis => $refMember) {
            $classifAxis = Classif_Model_Axis::loadByRef($refAxis);
            $index = new FixedIndex(Classif_Model_Axis::loadByRef($refAxis));
            $index->setClassifMember(Classif_Model_Member::loadByRefAndAxis($refMember, $classifAxis));
            $index->setAlgoNumeric($numeric);
            $index->save();
        }
    }

    /**
     * @param \AF\Domain\Algorithm\Numeric\NumericAlgo $numeric
     * @param string $refContext
     * @param string $refIndicator
     * @param array $indexes Sous la forme [$refAxis =» $algo]
     */
    protected function createAlgoIndexForAlgoNumeric(NumericAlgo $numeric, $refContext, $refIndicator, $indexes)
    {
        $numeric->setContextIndicator(Classif_Model_ContextIndicator::loadByRef($refContext, $refIndicator));
        foreach ($indexes as $refAxis => $algo) {
            $index = new AlgoResultIndex(Classif_Model_Axis::loadByRef($refAxis));
            $index->setAlgo($algo);
            $index->setAlgoNumeric($numeric);
            $index->save();
        }
    }

    /**
     * @param AF $aF
     * @param string $ref
     * @param string $label
     * @param string $refFamily
     */
    protected function createAlgoNumericParameter(AF $aF, $ref, $label, $refFamily)
    {
        $numericParameter = new Algo_Model_Numeric_Parameter();
        $numericParameter->setFamily(Family::loadByRef($refFamily));
        $this->createAlgoNumeric($aF, $numericParameter, $ref, $label);
    }

    /**
     * @param Algo_Model_Numeric_Parameter $parameter
     * @param array $indexes Sous la forme [$reDimension => $refMember]
     */
    protected function createFixedCoordinateForAlgoParameter(Algo_Model_Numeric_Parameter $parameter, $indexes)
    {
        foreach ($indexes as $dimensionRef => $memberRef) {
            $index = new Algo_Model_ParameterCoordinate_Fixed();
            $index->setDimensionRef($dimensionRef);
            $index->setMember($memberRef);
            $index->setAlgoParameter($parameter);
            $index->save();
        }
    }

    /**
     * @param Algo_Model_Numeric_Parameter $parameter
     * @param array $indexes Sous la forme [$refAxis =» $algo]
     */
    protected function createAlgoCoordinateForAlgoParameter(Algo_Model_Numeric_Parameter $parameter, $indexes)
    {
        foreach ($indexes as $dimensionRef => $algo) {
            $index = new Algo_Model_ParameterCoordinate_Algo();
            $index->setDimensionRef($dimensionRef);
            $index->setSelectionAlgo($algo);
            $index->setAlgoParameter($parameter);
            $index->save();
        }
    }

    /**
     * @param AF $aF
     * @param string $ref
     * @param string $expression
     */
    protected function createAlgoSelectTextkeyExpression(AF $aF, $ref, $expression)
    {
        $selectTextkeyExpression = new Algo_Model_Selection_TextKey_Expression();
        $selectTextkeyExpression->setRef($ref);
        $selectTextkeyExpression->setExpression($expression);
        $selectTextkeyExpression->save();
        $aF->addAlgo($selectTextkeyExpression);
    }

    /**
     * @param AF $aF
     * @param string      $ref
     * @param string      $name
     * @param string|null $defaultValue
     */
    protected function createAlgoSelectTextkeyContextValue(AF $aF, $ref, $name, $defaultValue = null)
    {
        $algo = new Algo_Model_Selection_TextKey_ContextValue();
        $algo->setRef($ref);
        $algo->setName($name);
        if ($defaultValue) {
            $algo->setDefaultValue($defaultValue);
        }
        $algo->save();
        $aF->addAlgo($algo);
    }

    /**
     * @param AF $aF
     * @param string $ref
     * @param string $expression
     */
    protected function createAlgoConditionExpression(AF $aF, $ref, $expression)
    {
        $conditionExpression = new ExpressionConditionAlgo();
        $conditionExpression->setRef($ref);
        $conditionExpression->setExpression($expression);
        $conditionExpression->save();
        $aF->addAlgo($conditionExpression);
    }

    protected function createAlgoConditionElementary(AF $aF, Component $component, $ref)
    {
        switch (get_class($component)) {
            case NumericField::class:
                $conditionElementary = new NumericConditionAlgo();
                break;
            case CheckboxCondition::class:
                $conditionElementary = new BooleanConditionAlgo();
                break;
            case SelectSingleCondition::class:
                $conditionElementary = new SelectSingleConditionAlgo();
                break;
            case SelectMultiCondition::class:
                $conditionElementary = new SelectMultiConditionAlgo();
                break;
            default:
                throw new Core_Exception("Unhandled field type");
        }
        $conditionElementary->setRef($ref);
        $conditionElementary->setInputRef($component->getRef());
        $conditionElementary->save();
        $aF->addAlgo($conditionElementary);
        return $conditionElementary;
    }

    /**
     * @param AF $aF
     * @param $ref
     * @param Field $component
     * @return ExpressionCondition
     * @throws Core_Exception
     */
    protected function createConditionElementary(AF $aF, $ref, Field $component)
    {
        switch (get_class($component)) {
            case NumericField::class:
                $condition = new NumericFieldCondition();
                break;
            case CheckboxCondition::class:
                $condition = new CheckboxCondition();
                break;
            case SelectSingleCondition::class:
                $condition = new SelectSingleCondition();
                break;
            case SelectMultiCondition::class:
                $condition = new SelectMultiCondition();
                break;
            default:
                throw new Core_Exception("Unhandled field type");
        }
        $condition->setField($component);
        return $this->createCondition($condition, $aF, $ref);
    }

    /**
     * @param AF $aF
     * @param $ref
     * @param $expression
     * @return ExpressionCondition
     */
    protected function createConditionExpression(AF $aF, $ref, $expression)
    {
        $condition = new ExpressionCondition();
        $condition->setExpression($expression);
        return $this->createCondition($condition, $aF, $ref);
    }

    /**
     * @param Condition $condition
     * @param AF $aF
     * @param $ref
     * @return ExpressionCondition
     */
    private function createCondition(Condition $condition, AF $aF, $ref)
    {
        $condition->setRef($ref);
        $condition->setAf($aF);
        $condition->save();
        return $condition;
    }

    /**
     * @param Component $component
     * @param string $state TYPE_DISABLE|TYPE_ENABLE|TYPE_HIDE|TYPE_SHOW
     * @param Condition $condition
     * @return Action
     */
    protected function createActionSetState(Component $component, $state,
        Condition $condition=null)
    {
        $action = new SetState();
        $action->setState($state);
        return $this->createAction($action, $component, $condition);
    }

    /**
     * @param Component $component
     * @param string $type TYPE_SETVALUE|TYPE_SETALGOVALUE
     * @param mixed $value
     * @param Condition $condition
     * @return Action
     * @throws Core_Exception
     */
    protected function createActionSetValue(Component $component, $type, $value,
        Condition $condition=null)
    {
        if ($type == Action::TYPE_SETVALUE) {
            switch (get_class($component)) {
                case NumericField::class:
                    $action = new NumericFieldCondition();
                    $action->setValue($value);
                    break;
                case CheckboxCondition::class:
                    $action = new CheckboxCondition();
                    $action->setChecked($value);
                    break;
                case SelectSingleCondition::class:
                    $action = new SelectSingleCondition();
                    $action->setOption($value);
                    break;
            }
        } else if ($type == Action::TYPE_SETALGOVALUE) {
            $action = new SetAlgoValue();
            $action->setAlgo($value);
        }
        return $this->createAction($action, $component, $condition);
    }

    /**
     * @param Action $action
     * @param Component $component
     * @param \AF\Domain\AF\Condition\Condition $condition
     * @return Action
     */
    private function createAction(Action $action, Component $component, Condition $condition=null)
    {
        $action->setTargetComponent($component);
        $component->addAction($action);
        if ($condition !== null) {
            $action->setCondition($condition);
        }
        $action->save();
        $component->save();
        return $action;
    }

}
