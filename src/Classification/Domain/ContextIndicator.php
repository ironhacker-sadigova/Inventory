<?php

namespace Classification\Domain;

use Core\Translation\TranslatedString;
use Core_Exception_InvalidArgument;
use Core_Model_Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine;

/**
 * Indicateur de classification contextualisé.
 *
 * @author valentin.claras
 */
class ContextIndicator extends Core_Model_Entity
{
    // Constantes de tris et de filtres.
    const QUERY_CONTEXT = 'context';
    const QUERY_INDICATOR = 'indicator';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Indicator
     */
    protected $indicator;

    /**
     * @var Collection|Axis[]
     */
    protected $axes;

    /**
     * @var ClassificationLibrary
     */
    protected $library;


    public function __construct(ClassificationLibrary $library, Context $context, Indicator $indicator)
    {
        $this->library = $library;
        $this->context = $context;
        $this->indicator = $indicator;

        $this->axes = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ClassificationLibrary
     */
    public function getLibrary()
    {
        return $this->library;
    }

    /**
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @return Indicator
     */
    public function getIndicator()
    {
        return $this->indicator;
    }

    /**
     * @return TranslatedString
     */
    public function getLabel()
    {
        return $this->indicator->getLabel()->concat(' - ', $this->context->getLabel());
    }

    /**
     * @param Axis $axis
     * @throws Core_Exception_InvalidArgument
     */
    public function addAxis(Axis $axis)
    {
        if (!($this->hasAxis($axis))) {
            foreach ($this->getAxes() as $existentAxis) {
                if ($existentAxis->isBroaderThan($axis) || $existentAxis->isNarrowerThan($axis)) {
                    throw new Core_Exception_InvalidArgument('Axis must be transverse');
                }
            }

            $this->axes->add($axis);
        }
    }

    /**
     * @param Axis $axis
     * @return boolean
     */
    public function hasAxis(Axis $axis)
    {
        return $this->axes->contains($axis);
    }

    /**
     * @param Axis $axis
     */
    public function removeAxis($axis)
    {
        if ($this->hasAxis($axis)) {
            $this->axes->removeElement($axis);
        }
    }

    /**
     * @return bool Est-ce que l'indicateur contextualisé possède des axes ?
     */
    public function hasAxes()
    {
        return !$this->axes->isEmpty();
    }

    /**
     * @return Axis[]
     */
    public function getAxes()
    {
        return $this->axes->toArray();
    }
}
