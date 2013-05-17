<?php
/**
 * @package Social
 */

/**
 * @author  joseph.rouffet
 * @author  matthieu.napoli
 * @package Social
 */
class Social_Model_ActionKeyFigure extends Core_Model_Entity
{

    use Core_Strategy_Ordered;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $label;

    /**
     * Unité associé
     * @var string (ref)
     */
    protected $unitRef;


    /**
     * @param Unit_API $unit
     * @param string $label
     */
    public function __construct(Unit_API $unit, $label)
    {
        $this->setUnit($unit);
        $this->setLabel($label);
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = (string) $label;
    }

    /**
     * @return Unit_API
     */
    public function getUnit()
    {
        return new Unit_API($this->unitRef);
    }

    /**
     * @param Unit_API $unit
     */
    public function setUnit(Unit_API $unit)
    {
        $this->unitRef = $unit->getRef();
    }

    /**
     * Fonction appelé avant un persist de l'objet (défini dans le mapper).
     */
    public function preSave()
    {
        try {
            $this->checkHasPosition();
        } catch (Core_Exception_UndefinedAttribute $e) {
            $this->setPosition();
        }
    }

    /**
     * Fonction appelé avant un update de l'objet (défini dans le mapper).
     */
    public function preUpdate()
    {
        $this->checkHasPosition();
    }

    /**
     * Fonction appelé avant un delete de l'objet (défini dans le mapper).
     */
    public function preDelete()
    {
        $this->deletePosition();
    }

    /**
     * Fonction appelé après un load de l'objet (défini dans le mapper).
     */
    public function postLoad()
    {
        $this->updateCachePosition();
    }

}
