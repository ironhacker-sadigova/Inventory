<?php
/**
 * @package Classif
 */


/**
 * Remplissage de la base de données avec des données de test
 * @package Classif
 */
class Classif_PopulateTest extends Core_Script_Action
{

    /**
     * {@inheritdoc}
     */
    public function runEnvironment($environment)
    {
        $entityManagers = Zend_Registry::get('EntityManagers');
        /** @var $entityManager \Doctrine\ORM\EntityManager */
        $entityManager = $entityManagers['default'];

        // Création des axes.
        // Params : ref, label
        // OptionalParams : Axis parent=null

        $axis_gaz = $this->createAxis('gaz', 'Gaz');
        $axis_poste_article_75 = $this->createAxis('poste_article_75', 'Poste article 75');
        $axis_scope = $this->createAxis('scope', 'Scope', $axis_poste_article_75);

        // Création des membres.
        // Params : Axis, ref, label
        // OptionalParams : [Member] parents=[]

        $member_gaz_co2 = $this->createMember($axis_gaz, 'co2', 'CO2');
        $member_gaz_ch4 = $this->createMember($axis_gaz, 'ch4', 'CH4');

        $member_scope_1 = $this->createMember($axis_scope, '1', '1');
        $member_scope_2 = $this->createMember($axis_scope, '2', '2');

        $member_poste_article_75_source_fixe_combustion = $this->createMember($axis_poste_article_75, 'source_fixe_combustion', '1 - Sources fixes de combustion', [$member_scope_1]);
        $member_poste_article_75_electricite = $this->createMember($axis_poste_article_75, 'membre_sans_parent', 'Membre sans parent');

        // Création des indicateurs.
        // Params : ref, label, unitRef
        // OptionalParams : ratioUnitRef=unitRef

        // $indicator_ges = $this->createIndicator('ges', 'GES', 't_co2e', 'kg_co2e');

        // Création des contextes.
        // Params : ref, label

        // $context_general = $this->createContext('general', 'Général');

        $entityManager->flush();

        // Création des contexte-indicateurs.
        // Params : Context, Indicator
        // OptionalParams : [Axis]=[]

        // $contextIndicator_ges_general = $this->createContextIndicator($context_general, $indicator_ges, [$axis_gaz, $axis_poste_article_75]);

        $entityManager->flush();

        echo "\t\tClassif created".PHP_EOL;
    }

    /**
     * @param string $ref
     * @param string $label
     * @param Classif_Model_Axis|null $narrower
     * @return Classif_Model_Axis
     */
    protected function createAxis($ref, $label, Classif_Model_Axis $narrower=null)
    {
        $axis = new Classif_Model_Axis();
        $axis->setRef($ref);
        $axis->setLabel($label);
        if ($narrower !== null) {
            $axis->setDirectNarrower($narrower);
        }
        $axis->save();
        return $axis;
    }

    /**
     * @param Classif_Model_Axis $axis
     * @param string $ref
     * @param string $label
     * @param array $parents
     * @return Classif_Model_Member
     */
    protected function createMember(Classif_Model_Axis $axis, $ref, $label, array $parents=[])
    {
        $member = new Classif_Model_Member();
        $member->setAxis($axis);
        $member->setRef($ref);
        $member->setLabel($label);
        foreach ($parents as $directParent)
        {
            $member->addDirectParent($directParent);
        }
        $member->save();
        return $member;
    }

    /**
     * @param string $ref
     * @param string $label
     * @param string $unitRef
     * @param string|null $ratioUnitRef
     * @return Classif_Model_Indicator
     */
    protected function createIndicator($ref, $label, $unitRef, $ratioUnitRef=null)
    {
        $indicator = new Classif_Model_Indicator();
        $indicator->setRef($ref);
        $indicator->setLabel($label);
        $indicator->setUnit(new Unit_API($unitRef));
        if ($ratioUnitRef !== null) {
            $indicator->setRatioUnit(new Unit_API($ratioUnitRef));
        } else {
            $indicator->setRatioUnit($indicator->getUnit());
        }
        $indicator->save();
        return $indicator;
    }

    /**
     * @param string $ref
     * @param string $label
     * @return Classif_Model_Context
     */
    protected function createContext($ref, $label)
    {
        $context = new Classif_Model_Context();
        $context->setRef($ref);
        $context->setLabel($label);
        $context->save();
        return $context;
    }

    /**
     * @param Classif_Model_Context $context
     * @param Classif_Model_Indicator $indicator
     * @param Classif_Model_Axis[] $axes
     * @return Classif_Model_ContextIndicator
     */
    protected function createContextIndicator(Classif_Model_Context $context, Classif_Model_Indicator $indicator, array $axes=[])
    {
        $contextIndicator = new Classif_Model_ContextIndicator();
        $contextIndicator->setContext($context);
        $contextIndicator->setIndicator($indicator);
        foreach ($axes as $axis) {
            $contextIndicator->addAxis($axis);
        }
        $contextIndicator->save();
        return $contextIndicator;
    }

}
