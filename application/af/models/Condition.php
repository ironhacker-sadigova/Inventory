<?php
/**
 * @author  matthieu.napoli
 * @author  thibaud.rolland
 * @author  hugo.charbonnier
 * @author  yoann.croizer
 * @package AF
 */

/**
 * @package    AF
 * @subpackage Condition
 */
abstract class AF_Model_Condition extends Core_Model_Entity
{

    const QUERY_AF = 'af';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $ref;

    /**
     * Form where the condition is effective.
     * @var AF_Model_AF
     */
    protected $af;


    /**
     * Génère une condition UI
     * @param AF_GenerationHelper $generationHelper
     * @return UI_Form_Condition
     */
    abstract public function getUICondition(AF_GenerationHelper $generationHelper);

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AF_Model_AF
     */
    public function getAf()
    {
        return $this->af;
    }

    /**
     * @param AF_Model_AF $af
     */
    public function setAf(AF_Model_AF $af)
    {
        $this->af = $af;
    }

    /**
     * @return string
     */
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @param string $ref
     */
    public function setRef($ref)
    {
        Core_Tools::checkRef($ref);
        $this->ref = (string) $ref;
    }

    /**
     * Retourne la condition correspondant à la référence.
     * @param string $ref
     * @return AF_Model_Condition
     */
    public static function loadByRef($ref)
    {
        return self::getEntityRepository()->loadBy(array('ref' => $ref));
    }

    /**
     * Permet de charger une Condition élémentaire par son ref et son AF
     * @param string      $ref
     * @param AF_Model_AF $af
     * @return AF_Model_Condition_Elementary
     */
    public static function loadByRefAndAF($ref, $af)
    {
        return self::getEntityRepository()->loadBy(array('ref' => $ref, 'af' => $af));
    }

}
