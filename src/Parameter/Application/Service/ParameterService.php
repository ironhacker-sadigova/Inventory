<?php

namespace Parameter\Application\Service;

use Calc_UnitValue;
use Core_Exception_InvalidArgument;
use Core_Exception_NotFound;
use Parameter\Domain\Family\Family;
use Parameter\Domain\Family\FamilyReference;
use Parameter\Domain\ParameterLibrary;

/**
 * Service haut niveau pour accéder en lecture aux paramètres.
 *
 * @author matthieu.napoli
 */
class ParameterService
{
    /**
     * Retourne une famille.
     *
     * @param FamilyReference $familyReference Identifiant de la famille
     *
     * @throws Core_Exception_NotFound Famille inconnue
     * @return Family
     */
    public function getFamily(FamilyReference $familyReference)
    {
        $library = ParameterLibrary::load($familyReference->getLibraryId());

        return $library->getFamily($familyReference->getFamilyRef());
    }

    /**
     * Retourne la valeur dans une famille aux coordonnées spécifiées.
     *
     * @param Family   $family
     * @param string[] $membersRef Ref des membres indexés par le ref des dimensions
     *
     * @throws Core_Exception_NotFound No value defined for this coordinate.
     * @throws Core_Exception_InvalidArgument Not enough/too many members given.
     * @return null|Calc_UnitValue
     */
    public function getFamilyValueByCoordinates(Family $family, array $membersRef)
    {
        if (count($membersRef) != count($family->getDimensions())) {
            throw new Core_Exception_InvalidArgument(sprintf(
                'The family has %s dimensions, %s members given',
                count($family->getDimensions()),
                count($membersRef)
            ));
        }

        $members = [];
        foreach ($membersRef as $dimensionRef => $memberRef) {
            $dimension = $family->getDimension($dimensionRef);
            $members[] = $dimension->getMember($memberRef);
        }

        $cell = $family->getCell($members);

        $value = $cell->getValue();

        if ($value === null) {
            throw new Core_Exception_NotFound(sprintf(
                'No value is defined in family "%s" for the coordinates "%s"',
                $family->getRef(),
                implode(', ', $membersRef)
            ));
        }

        return new Calc_UnitValue(
            $family->getValueUnit(),
            $value->getDigitalValue(),
            $value->getRelativeUncertainty()
        );
    }
}
