<?php

namespace Tests\Parameter\Family;

use Core\Test\TestCase;
use Core_Tools;
use Doctrine\ORM\UnitOfWork;
use Parameter\Domain\Family\Dimension;
use Parameter\Domain\Family\Family;
use Tests\Parameter\FamilyTest;

/**
 * @covers \Parameter\Domain\Family\Dimension
 */
class DimensionTest extends TestCase
{
    /**
     * @return Dimension
     */
    public static function generateObject()
    {
        // Fixtures
        $family = FamilyTest::generateObject();
        $o = new Dimension($family, Core_Tools::generateRef(), 'Dimension', Dimension::ORIENTATION_HORIZONTAL);
        $o->save();
        self::getEntityManager()->flush();
        return $o;
    }

    public static function deleteObject(Dimension $o)
    {
        FamilyTest::deleteObject($o->getFamily());
        self::getEntityManager()->flush();
    }

    public static function setUpBeforeClass()
    {
        foreach (Dimension::loadList() as $o) {
            $o->delete();
        }
        foreach (Family::loadList() as $o) {
            $o->delete();
        }
        self::getEntityManager()->flush();
    }

    /**
     * Teste l'association à sa famille
     */
    public function testBidirectionalFamilyAssociation()
    {
        // Fixtures
        $family = FamilyTest::generateObject();

        // Charge la collection pour éviter le lazy-loading en dessous
        // (le lazy loading entrainerait le chargement depuis la BDD et donc la prise en compte
        // de l'association BDD même si elle n'était pas faite au niveau PHP)
        $family->getDimensions();

        $o = new Dimension($family, Core_Tools::generateRef(), 'Dimension', Dimension::ORIENTATION_HORIZONTAL);

        // Vérifie que l'association a été affectée bidirectionnellement
        $this->assertTrue($family->hasDimension($o));

        FamilyTest::deleteObject($family);
    }

    /**
     * Teste la persistence en cascade depuis la famille
     */
    public function testCascadeFromFamily()
    {
        // Fixtures
        $family = FamilyTest::generateObject();

        $o = new Dimension($family, Core_Tools::generateRef(), 'Dimension', Dimension::ORIENTATION_HORIZONTAL);

        // Vérification de la cascade de la persistence
        $family->save();
        $this->entityManager->flush();
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->entityManager->getUnitOfWork()->getEntityState($o));

        // Vérification de la cascade de la suppression
        FamilyTest::deleteObject($family);
        $this->assertEquals(UnitOfWork::STATE_NEW, $this->entityManager->getUnitOfWork()->getEntityState($o));
    }

    /**
     * Test de la position
     */
    public function testPosition()
    {
        // Fixtures
        $family = FamilyTest::generateObject();

        $o1 = new Dimension($family, Core_Tools::generateRef(), 'Dimension', Dimension::ORIENTATION_HORIZONTAL);
        $o1->save();
        $o2 = new Dimension($family, Core_Tools::generateRef(), 'Dimension', Dimension::ORIENTATION_HORIZONTAL);
        $o2->save();
        $o3 = new Dimension($family, Core_Tools::generateRef(), 'Dimension', Dimension::ORIENTATION_VERTICAL);
        $o3->save();
        $o4 = new Dimension($family, Core_Tools::generateRef(), 'Dimension', Dimension::ORIENTATION_VERTICAL);
        $o4->save();
        $this->entityManager->flush();

        $this->assertEquals(1, $o1->getPosition());
        $this->assertEquals(2, $o2->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        // setPosition
        $o2->setPosition(1);
        $o2->save();
        $this->entityManager->flush();
        $this->assertEquals(2, $o1->getPosition());
        $this->assertEquals(1, $o2->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        // up
        $o1->goUp();
        $o1->save();
        $this->entityManager->flush();
        $this->assertEquals(1, $o1->getPosition());
        $this->assertEquals(2, $o2->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        // down
        $o1->goDown();
        $o1->save();
        $this->entityManager->flush();
        $this->assertEquals(2, $o1->getPosition());
        $this->assertEquals(1, $o2->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        // Delete
        $o2->delete();
        $this->assertEquals(1, $o1->getPosition());
        $this->assertEquals(1, $o3->getPosition());
        $this->assertEquals(2, $o4->getPosition());
        FamilyTest::deleteObject($family);
    }
}