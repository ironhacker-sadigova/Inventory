<?php

use DeepCopy\DeepCopy;
use DeepCopy\Filter\Doctrine\DoctrineCollectionFilter;
use DeepCopy\Filter\KeepFilter;
use DeepCopy\Filter\SetNullFilter;
use DeepCopy\Matcher\PropertyMatcher;
use DeepCopy\Matcher\PropertyNameMatcher;
use DeepCopy\Matcher\PropertyTypeMatcher;
use Doctrine\Common\Collections\Collection;

/**
 * AF copy service
 */
class AF_Service_AFCopyService
{
    /**
     * @param AF_Model_AF $af
     * @param string      $newRef
     * @param string      $newLabel
     * @return AF_Model_AF
     */
    public function copyAF(AF_Model_AF $af, $newRef, $newLabel)
    {
        $deepCopy = new DeepCopy();

        // ID null
        $deepCopy->addFilter(new SetNullFilter(), new PropertyNameMatcher('id'));
        // Position
        $deepCopy->addFilter(new SetNullFilter(), new PropertyNameMatcher('position'));
        // Keep AF category
        $deepCopy->addFilter(new KeepFilter(), new PropertyMatcher(AF_Model_AF::class, 'category'));
        // Doctrine collections
        $deepCopy->addFilter(new DoctrineCollectionFilter(), new PropertyTypeMatcher(Collection::class));
        // SubAF
        $deepCopy->addFilter(new KeepFilter(), new PropertyMatcher(AF_Model_Component_SubAF::class, 'calledAF'));

        /** @var AF_Model_AF $newAF */
        $newAF = $deepCopy->copy($af);

        $newAF->setRef($newRef);
        $newAF->setLabel($newLabel);

        return $newAF;
    }
}
