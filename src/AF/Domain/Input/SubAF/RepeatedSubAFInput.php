<?php

namespace AF\Domain\Input\SubAF;

use AF\Domain\Component\SubAF\RepeatedSubAF;
use AF\Domain\InputSet\InputSet;
use AF\Domain\Component\Component;
use AF\Domain\Input\SubAFInput;
use AF\Domain\InputSet\SubInputSet;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author matthieu.napoli
 * @author yoann.croizer
 */
class RepeatedSubAFInput extends SubAFInput
{
    /**
     * Array of SubSet
     * @var SubInputSet[]|Collection
     */
    protected $value;

    public function __construct(InputSet $inputSet, Component $component)
    {
        $this->value = new ArrayCollection();
        parent::__construct($inputSet, $component);
    }

    /**
     * Get the value of the repeated subAF element, it means an array of subSet.
     * @return SubInputSet[]
     */
    public function getValue()
    {
        return is_array($this->value) ? $this->value : $this->value->toArray();
    }

    /**
     * @param SubInputSet $subSet
     */
    public function addSubSet(SubInputSet $subSet)
    {
        $this->value->add($subSet);
    }

    /**
     * @param SubInputSet $subSet
     */
    public function removeSubSet(SubInputSet $subSet)
    {
        $this->value->removeElement($subSet);
    }

    /**
     * @return int Nombre de champs remplis dans le composant
     */
    public function getNbRequiredFieldsCompleted()
    {
        $nbRequiredFieldsCompleted = 0;
        if (!$this->isHidden()) {
            foreach ($this->value as $subSet) {
                $nbRequiredFieldsCompleted += $subSet->getNbRequiredFieldsCompleted();
            }
        }
        return $nbRequiredFieldsCompleted;
    }

    /**
     * {@inheritdoc}
     */
    public function hasValue()
    {
        return false;
    }

    /**
     * Ajoute une nouvelle répétition d'un sous-formulaire
     * @param string $freeLabel
     * @return SubInputSet
     */
    public function addRepeatedSubAf($freeLabel = null)
    {
        /** @var $component RepeatedSubAF */
        $component = $this->getComponent();
        $subInputSet = new SubInputSet($component->getCalledAF());
        $subInputSet->setFreeLabel($freeLabel);
        $this->addSubSet($subInputSet);

        return $subInputSet;
    }
}
