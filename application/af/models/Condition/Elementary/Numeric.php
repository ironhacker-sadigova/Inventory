<?php
/**
 * @author  matthieu.napoli
 * @author  yoann.croizer
 * @package AF
 */

/**
 * @package    AF
 * @subpackage Condition
 */
class AF_Model_Condition_Elementary_Numeric extends AF_Model_Condition_Elementary
{

    /**
     * Valeur numérique pour laquelle la condition est effective.
     * @var float|null
     */
    protected $value;


    /**
     * {@inheritdoc}
     */
    public function getUICondition(AF_GenerationHelper $generationHelper)
    {
        $uiCondition = new UI_Form_Condition_Elementary($this->ref);
        $uiCondition->element = $generationHelper->getUIElement($this->field);
        switch ($this->getRelation()) {
            case self::RELATION_EQUAL :
                $uiCondition->relation = UI_Form_Condition_Elementary::EQUAL;
                break;
            case self::RELATION_NEQUAL :
                $uiCondition->relation = UI_Form_Condition_Elementary::NEQUAL;
                break;
            case self::RELATION_GT :
                $uiCondition->relation = UI_Form_Condition_Elementary::GT;
                break;
            case self::RELATION_LT :
                $uiCondition->relation = UI_Form_Condition_Elementary::LT;
                break;
            case self::RELATION_GE :
                $uiCondition->relation = UI_Form_Condition_Elementary::GE;
                break;
            case self::RELATION_LE :
                $uiCondition->relation = UI_Form_Condition_Elementary::LE;
                break;
            default :
                throw new Core_Exception("The relation '{$this->getRelation()}'' is invalid or undefined");
        }
        $uiCondition->value = $this->value;
        return $uiCondition;
    }

    /**
     * Set the relation Param.
     * @param int $relation
     * @throws Core_Exception_InvalidArgument
     * @return void
     */
    public function setRelation($relation)
    {
        switch ($relation) {
            case self::RELATION_EQUAL :
            case self::RELATION_NEQUAL :
            case self::RELATION_GT :
            case self::RELATION_LT :
            case self::RELATION_GE :
            case self::RELATION_LE :
                break;
            default :
                throw new Core_Exception_InvalidArgument("The relation '$relation' does not exist");
        }
        $this->relation = $relation;
    }

    /**
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float|null $value
     */
    public function setValue($value)
    {
        if ($value === null) {
            $this->value = null;
        } else {
            $this->value = (float) $value;
        }
    }

}
