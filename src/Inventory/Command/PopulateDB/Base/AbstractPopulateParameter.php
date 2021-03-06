<?php

namespace Inventory\Command\PopulateDB\Base;

use Calc_Value;
use Core\Translation\TranslatedString;
use Parameter\Domain\ParameterLibrary;
use Symfony\Component\Console\Output\OutputInterface;
use Parameter\Domain\Family\Family;
use Parameter\Domain\Family\Dimension;
use Parameter\Domain\Family\Member;
use Parameter\Domain\Category;
use Unit\UnitAPI;

/**
 * Remplissage de la base de données avec des données de test
 */
abstract class AbstractPopulateParameter
{
    // Création des catégories.
    //  + createCategory : -
    // Params : ref
    // OptionalParams : Category parent=null

    // Création des familles.
    //  + createFamily : -
    // Params : Category, ref, label, refUnit, refBaseUnit
    // OptionalParams : documentation=''

    // Création des dimensions.
    //  + createVerticalDimension : -
    //  + createHorizontalDimension : -
    // Params: Family, ref, label, refMembers[]

    // Création des paramètres.
    //  + createParameter : -
    // Params : Family, refMembers[], value
    // OptionalParams : uncertainty=0

    abstract public function run(OutputInterface $output);

    /**
     * @param ParameterLibrary $library
     * @param string           $label
     * @param Category         $parent
     * @return Category
     */
    protected function createCategory(ParameterLibrary $library, $label, Category $parent = null)
    {
        $category = new Category($library, new TranslatedString($label, 'fr'));
        if ($parent !== null) {
            $category->setParentCategory($parent);
        }
        $category->save();
        $library->addCategory($category);
        return $category;
    }

    /**
     * @param ParameterLibrary $library
     * @param Category         $category
     * @param                  $ref
     * @param                  $label
     * @param                  $refUnit
     * @param string           $documentation
     * @return Family
     */
    protected function createFamily(
        ParameterLibrary $library,
        Category $category,
        $ref,
        $label,
        $refUnit,
        $documentation = ''
    ) {
        $family = new Family($library, $ref, new TranslatedString($label, 'fr'));

        $family->setCategory($category);
        $family->setUnit(new UnitAPI($refUnit));
        $family->getDocumentation()->set($documentation, 'fr');
        $family->save();
        $library->addFamily($family);
        return $family;
    }

    /**
     * @param Family $family
     * @param string $ref
     * @param string $label
     * @param string[] $members
     */
    protected function createHorizontalDimension(Family $family, $ref, $label, array $members)
    {
        $this->createDimension($family, $ref, $label, Dimension::ORIENTATION_HORIZONTAL, $members);
    }

    /**
     * @param Family $family
     * @param string $ref
     * @param string $label
     * @param string[] $members
     */
    protected function createVerticalDimension(Family $family, $ref, $label, array $members)
    {
        $this->createDimension($family, $ref, $label, Dimension::ORIENTATION_VERTICAL, $members);
    }

    /**
     * @param Family $family
     * @param string $ref
     * @param string $label
     * @param int $orientation
     * @param string[] $members
     */
    protected function createDimension(Family $family, $ref, $label, $orientation, array $members)
    {
        $dimension = new Dimension($family, $ref, new TranslatedString($label, 'fr'), $orientation);
        foreach ($members as $memberRef => $memberLabel) {
            $member = new Member($dimension, $memberRef, new TranslatedString($memberLabel, 'fr'));
            $member->save();
            $dimension->addMember($member);
        }
        $dimension->save();
    }

    protected function setParameter(Family $family, array $refMembers, $value, $uncertainty = 0)
    {
        // Récupère la cellule
        $members = [];
        foreach ($family->getDimensions() as $dimension) {
            foreach ($dimension->getMembers() as $member) {
                if (in_array($member->getRef(), $refMembers)) {
                    $members[] = $member;
                }
            }
        }
        $cell = $family->getCell($members);

        $cell->setValue(new Calc_Value($value, $uncertainty));

        $cell->save();
    }
}
