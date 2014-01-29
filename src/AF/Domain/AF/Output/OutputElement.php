<?php

namespace AF\Domain\AF\Output;

use AF\Domain\AF\InputSet\InputSet;
use AF\Domain\Algorithm\Numeric\NumericAlgo;
use AF\Domain\Algorithm\Output;
use Calc_Value;
use Classif_Model_Axis;
use Classif_Model_ContextIndicator;
use Core_Exception_NotFound;
use Core_Model_Entity;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author matthieu.napoli
 * @author hugo.charbonnier
 * @author thibaud.rolland
 * @author yoann.croizer
 */
class OutputElement extends Core_Model_Entity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var OutputSet
     */
    protected $outputSet;

    /**
     * Identifiant du set de l'AF auquel est relié l'outputElement.
     * @var InputSet
     */
    protected $inputSet;

    /**
     * @var string
     */
    protected $refContext;

    /**
     * @var string
     */
    protected $refIndicator;

    /**
     * @var OutputIndex[]|Collection
     */
    protected $indexes;

    /**
     * @var \AF\Domain\Algorithm\NumericAlgo
     */
    protected $algo;

    /**
     * @var Calc_Value
     */
    protected $value;


    /**
     * Prend en paramètre un Algo_Model_Output_Elment et récupère ses attributs.
     *
     * @param OutputSet $outputSet
     * @param \AF\Domain\Algorithm\Output         $algoOutput
     * @param InputSet                  $inputSet
     */
    public function __construct(
        OutputSet $outputSet,
        Output $algoOutput = null,
        InputSet $inputSet = null
    ) {
        $this->indexes = new ArrayCollection();
        $this->outputSet = $outputSet;
        $this->inputSet = $inputSet;
        if ($algoOutput) {
            $this->algo = $algoOutput->getAlgo();
            $this->value = $algoOutput->getValue();

            foreach ($algoOutput->getClassifMembers() as $member) {
                $index = new OutputIndex($member->getAxis(), $member);
                $this->indexes->add($index);
            }
        } else {
            $this->value = new Calc_Value();
        }
    }

    /**
     * @return Classif_Model_ContextIndicator
     */
    public function getContextIndicator()
    {
        return $this->algo->getContextIndicator();
    }

    /**
     * @return OutputIndex[]
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @param Classif_Model_Axis $axis
     * @throws Core_Exception_NotFound
     * @return OutputIndex
     */
    public function getIndexForAxis(Classif_Model_Axis $axis)
    {
        foreach ($this->indexes as $index) {
            if ($index->getAxis() === $axis) {
                return $index;
            } else {
                if ($index->getAxis()->isNarrowerThan($axis)) {
                    return $index;
                }
            }
        }
        throw new Core_Exception_NotFound("Index not found for axis " . $axis->getRef());
    }

    /**
     * Retourne le libellé de l'algo
     * @return string
     */
    public function getLabel()
    {
        return $this->algo->getLabel();
    }

    /**
     * @return InputSet
     */
    public function getInputSet()
    {
        return $this->inputSet;
    }

    /**
     * @param InputSet $set
     */
    public function setInputSet($set)
    {
        $this->inputSet = $set;
    }

    /**
     * @return Calc_Value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param OutputSet $outputSet
     */
    public function setOutputSet($outputSet)
    {
        $this->outputSet = $outputSet;
    }
}
