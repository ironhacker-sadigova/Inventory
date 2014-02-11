<?php

namespace AF\Domain\Output;

use Classif\Domain\IndicatorAxis;
use Classif\Domain\AxisMember;
use Core_Model_Entity;

/**
 * @author matthieu.napoli
 */
class OutputIndex extends Core_Model_Entity
{
    /**
     * @var int
     */
    protected $id;

    /**
     * Reference of the classif axis
     * @var string
     */
    protected $refAxis;

    /**
     * Reference of the classif member
     * @var string
     */
    protected $refMember;

    /**
     * Variable nécessaire pour faire la relation inverse et faire marcher le delete cascade
     * À supprimer quand le bug dans Doctrine aura disparu
     * @var OutputElement[]
     */
    protected $outputElements;


    /**
     * @param \Classif\Domain\IndicatorAxis   $axis
     * @param AxisMember $member
     */
    public function __construct(IndicatorAxis $axis, AxisMember $member)
    {
        $this->setAxis($axis);
        $this->setMember($member);
    }

    /**
     * @return \Classif\Domain\IndicatorAxis
     */
    public function getAxis()
    {
        return IndicatorAxis::loadByRef($this->refAxis);
    }

    /**
     * @param IndicatorAxis $classifAxis
     */
    public function setAxis(IndicatorAxis $classifAxis)
    {
        $this->refAxis = $classifAxis->getRef();
    }

    /**
     * @return AxisMember
     */
    public function getMember()
    {
        return AxisMember::loadByRefAndAxis($this->refMember, $this->getAxis());
    }

    /**
     * @param AxisMember $classifMember
     */
    public function setMember(AxisMember $classifMember)
    {
        $this->refMember = $classifMember->getRef();
    }

    /**
     * @return string
     */
    public function getRefAxis()
    {
        return $this->refAxis;
    }

    /**
     * @return string
     */
    public function getRefMember()
    {
        return $this->refMember;
    }
}
