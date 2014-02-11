<?php

namespace AF\Domain\Algorithm;

use AF\Domain\Algorithm\Numeric\NumericAlgo;
use Calc_UnitValue;
use Calc_Value;
use Classif\Domain\AxisMember;

/**
 * This class is used to index a value output from numeric algo
 *
 * @author matthieu.napoli
 * @author yoann.croizer
 * @author benjamin.bertin
 */
class Output
{
    /**
     * Result's value normalized with the indicator unit
     * @var Calc_Value
     */
    protected $value;

    /**
     * Result's source value with it's unit (i.e.: before the normalization with the indicator's unit)
     * @var Calc_UnitValue
     */
    protected $sourceValue;

    /**
     * @var NumericAlgo
     */
    protected $algo;

    /**
     * Members indexing the value
     * @var \Classif\Domain\AxisMember[]
     */
    protected $classifMembers = [];


    /**
     * Create a new output with an indicator and (possibly) indexing members as an array.
     *
     * @param Calc_UnitValue         $value
     * @param NumericAlgo            $algo
     * @param \Classif\Domain\AxisMember[] $classifMembers
     */
    public function __construct(Calc_UnitValue $value, NumericAlgo $algo, array $classifMembers)
    {
        $this->sourceValue = clone $value;
        $this->algo = $algo;
        $unit = $algo->getContextIndicator()->getIndicator()->getUnit();
        // Get the value using the conversionFactor
        // TODO use Unit conversion API
        $conversionFactor = $unit->getConversionFactor($this->sourceValue->getUnit()->getRef());
        $this->value = new Calc_Value(
            $value->getDigitalValue() * $conversionFactor,
            $value->getRelativeUncertainty()
        );
        foreach ($classifMembers as $member) {
            $this->classifMembers[] = $member;
        }
    }

    /**
     * Return the value
     * @return Calc_Value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value
     * @param Calc_Value $value
     */
    public function setValue(Calc_Value $value)
    {
        $this->value = $value;
    }

    /**
     * Return the source value (i.e.: before the normalization with the indicator's unit)
     * @return Calc_UnitValue
     */
    public function getSourceValue()
    {
        return $this->sourceValue;
    }

    /**
     * Set the source value
     * @param Calc_UnitValue $value
     */
    public function setSourceValue(Calc_UnitValue $value)
    {
        $this->sourceValue = $value;
    }

    /**
     * Add a member to the value index
     * @param AxisMember $member
     */
    public function addMember(AxisMember $member)
    {
        $this->classifMembers[] = $member;
    }

    /**
     * Return the members indexing the value
     * @return AxisMember[]
     */
    public function getClassifMembers()
    {
        return $this->classifMembers;
    }

    /**
     * @return NumericAlgo
     */
    public function getAlgo()
    {
        return $this->algo;
    }
}
