<?php
// Entity Manager
$entityManagers = Zend_Registry::get('EntityManagers');
/** @var $entityManager \Doctrine\ORM\EntityManager */
$entityManager = $entityManagers['default'];

foreach (DW_Model_Indicator::loadList() as $indicator) {
    if (strpos($indicator->getRef(), 'classif_')) {
        $indicator->setRef(substr($indicator->getRef(), 8));
    }
}

foreach (DW_Model_Axis::loadList() as $axis) {
    if (strpos($axis->getRef(), 'classif_')) {
        $axis->setRef('c_'.substr($axis->getRef(), 8));
    } elseif (strpos($axis->getRef(), 'orga_')) {
        $axis->setRef('o_'.substr($axis->getRef(), 5));
    }
}

foreach (DW_Model_Member::loadList() as $member) {
    if (strpos($member->getRef(), 'classif_')) {
        $member->setRef(substr($member->getRef(), 8));
    } elseif (strpos($member->getRef(), 'orga_')) {
        $member->setRef(substr($member->getRef(), 5));
    }
}

echo "\n".'-> Flush starting…'."\n";
$entityManager->flush();
echo "\n".'-> Flush ended !'."\n";